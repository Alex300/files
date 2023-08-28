<?php

namespace cot\modules\files\model;

use cot\modules\files\services\FileService;
use image\Image;

defined('COT_CODE') or die('Wrong URL.');

if (empty($GLOBALS['db_files'])) {
    \Cot::$db->registerTable('files');
    cot_extrafields_register_table('files');
}

/**
 * Модель File
 *
 * @method static File getById($pk, $staticCache = true);
 * @method static File fetchOne($conditions = array(), $order = '')
 * @method static File[] findByCondition($conditions = array(), $limit = 0, $offset = 0, $order = '')
 *
 * @property int    $id
 * @property int    $user_id        id пользователя - владельца или 0 - если это site file space
 * @property string $source         Источник / модуль - владелец
 * @property int    $source_id      id элемента, к которому привязан файл
 * @property string $source_field   Поле элемента (опционально), например 'logo'
 * @property string $path           Путь к файлу относительно корневой директории для файлов (Cot::$cfg['files']['folder'])
 * @property string $file_name      Имя файла
 * @property string $original_name  Исходное имя файла
 * @property string $ext            Расширение файла
 * @property bool   $is_img         Является ли изображением
 * @property int    $size           Размер
 * @property string $title          Название
 * @property int    $downloads_count Количество скачиваний
 * @property int    $sort_order     Порядок для отображения
 * @property string $unikey         Ключ формы для хранения временных файлов от несуществующих объектов
 * @property string $created        Дата создания (загрузки на сервер)
 * @property int    $created_by     ID пользователя, создавшего файл
 * @property string $updated        Дата последнего изменения
 * @property int    $updated_by     ID пользователя, обновившего файл
 *
 * @property-read string $fullName The filename with path relative to the Files module root directory for files (Cot::$cfg['files']['folder'])
 * @property-read string $icon File icon url
 */
class File extends \Som_Model_ActiveRecord
{
    /**
     * @var \Som_Model_Mapper_Abstract
     */
    protected  static $_db = null;
    protected  static $_tbname = '';
    protected  static $_primary_key = 'id';

    /**
     * Static constructor
     * @param string $db Database connection config name
     */
    public static function __init($db = 'db')
    {
        static::$_tbname = \Cot::$db->files;
        parent::__init($db);
    }

    /**
     * @return string
     * @todo перенести в сервис (лучше убрать)
     */
    public function getIcon()
    {
        return FileService::typeIcon($this->_data['ext']);
    }

    public function makeAvatar()
    {
        if ($this->_data['source'] !== 'pfs' || $this->_data['is_img'] === 0 || !$this->_data['user_id']) {
            return false;
        }

        static::$_db->update(
            \Cot::$db->users,
            ['user_avatar' => $this->_data['id']],
            'user_id = ?',
            [$this->_data['user_id']]
        );
        return true;
    }

    /**
     * The filename with path relative to the Files module root directory for files (Cot::$cfg['files']['folder'])
     * @return string
     */
    public function getFullName()
    {
        return $this->_data['path'] . '/' . $this->_data['file_name'];
    }

    protected function beforeInsert()
    {
//        if (empty($this->_data['file_updated'])) {
//            $this->_data['file_updated'] = date('Y-m-d H:i:s', \Cot::$sys['now']);
//        }

        if (empty($this->_data['sort_order'])) {
            $this->_data['sort_order'] = ((int) static::$_db->query(
                "SELECT MAX(sort_order) FROM " . static::$_tbname . " WHERE source = ? AND source_id = ?",
                [$this->_data['source'], $this->_data['source_id']]
            )->fetchColumn()) + 1;
        }

        return parent::beforeInsert();
    }

    protected function afterInsert()
    {
        if (in_array($this->_data['source'], ['pfs', 'sfs']) && $this->_data['source_id'] > 0){
            $condition = array(
                ['source', $this->_data['source']],
                ['source_id', $this->_data['source_id']],
            );

            $folder = \files_model_Folder::getById($this->_data['source_id']);
            if ($folder) {
                $folder->ff_count = File::count($condition);
                $folder->save();
            }
        }

        return parent::afterInsert();
    }

    protected function beforeUpdate()
    {
        //$this->_data['file_updated'] = date('Y-m-d H:i:s', \Cot::$sys['now']);

        return parent::beforeUpdate();
    }

    protected function beforeDelete()
    {
        $res = true;

        $filePath = \Cot::$cfg['files']['folder']. '/' . $this->_data['path'] . '/' . $this->_data['file_name'] ;

        $path_parts = pathinfo($filePath);
        $res &= @unlink($filePath);
        $fCnt = array_sum(array_map('is_file', glob($path_parts['dirname'] . '/*')));
        // Delete folder if it is empty
        if ($fCnt === 0)  {
            @rmdir($path_parts['dirname']);
        }

        // Delete user's folder in pfs if it is empty
        if ($this->_data['source'] == 'pfs') {
            $path = \Cot::$cfg['files']['folder'] . '/pfs/' . $this->_data['user_id'];
            $fCnt = array_sum(array_map('is_file', glob($path . '/*')));

            if ($fCnt === 0) {
                @rmdir($path);
            }
        }

        $res &= $this->remove_thumbs();
        @rmdir(\Cot::$cfg['files']['folder'] . '/_thumbs/' . $this->_data['id']);

        return parent::beforeDelete();
    }

    protected function afterDelete()
    {
        if (in_array($this->_data['source'], ['pfs', 'sfs']) && $this->_data['source_id'] > 0) {
            $condition = [
                ['source', $this->_data['source']],
                ['source_id', $this->_data['source_id']],
            ];

            $folder = \files_model_Folder::getById($this->_data['source_id']);
            if ($folder) {
                $folder->ff_count = File::count($condition);
                $folder->save();
            }
        }

        return parent::afterDelete();
    }

    /**
     * Removes thumbnails matching the arguments.
     * @return boolean       true on success, false on error
     */
    public function remove_thumbs()
    {
        $res = true;

        $thumbs_folder = \Cot::$cfg['files']['folder'] . '/_thumbs/' . $this->_data['id'];
        $path = $thumbs_folder . '/' . \Cot::$cfg['files']['prefix'] . $this->_data['id'];
        $thumbPaths =  glob($path . '-*', GLOB_NOSORT);

        if (!empty($thumbPaths) && is_array($thumbPaths)) {
            foreach ($thumbPaths as $thumb) {
                $res &= @unlink($thumb);
            }
        }

        return $res;
    }

    public static function fieldList()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => 'bigint',
                'primary' => true,
                'description' => 'id',
            ],
            'user_id' => [
                'name' => 'user_id',
                'type' => 'int',
                'description' => 'id пользователя - владельца или 0 - если это site file space',
            ],
            'source' => [
                'name' => 'source',
                'type' => 'varchar',
                'length' => '128',
                'description' => 'Источник / модуль - владелец',
             ],
            'source_id' => [
                'name' => 'source_id',
                'type' => 'int',
                'description' => 'id элемента, к которому привязан файл',
            ],
            'source_field' => [
                'name' => 'source_field',
                'type' => 'varchar',
                'length' => '255',
                //'nullable'  => true,
                'default'   => '',
                //'description' => 'Публичный?'
                'description' => 'Поле элемента (опционально), например \'logo\'',
            ],
            'path' => [
                'name' => 'path',
                'type' => 'varchar',
                'length' => '255',
                'description' => 'Путь к файлу относительно корневой директории для файлов (Cot::$cfg[\'files\'][\'folder\'])',
            ],
            'file_name' => [
                'name' => 'file_name',
                'type' => 'varchar',
                'length' => '255',
                'description' => 'Имя файла',
            ],
            'original_name' => [
                'name' => 'original_name',
                'type' => 'varchar',
                'length' => '255',
                'description' => 'Исходное имя файла',
            ],
            'ext' => [
                'name' => 'ext',
                'type' => 'varchar',
                'length' => '16',
                'description' => 'Расширение файла'
            ],
            'is_img' => [
                'name' => 'is_img',
                'type' => 'bool',
                //'nullable' => true,
                'default' => 0,
                'description' => 'Является ли изображением'
            ],
            'size' => [
                'name' => 'size',
                'type' => 'int',
                'description' => 'Размер'
            ],
            'title' => [
                'name' => 'title',
                'type' => 'varchar',
                'length' => '255',
                //'nullable' => true,
                'default' => '',
                'description' => 'Название',
            ],
            'downloads_count' => [
                'name' => 'downloads_count',
                'type' => 'int',
                //'nullable' => true,
                'default' => 0,
                'description' => 'Количество скачиваний',
            ],
            'sort_order' => [
                'name' => 'sort_order',
                'type' => 'int',
//                'nullable' => true,
                'default' => 0,
                'description' => 'Порядок для отображения',
            ],
            'unikey' => [
                'name' => 'unikey',
                'type' => 'varchar',
                'length' => '255',
//                'nullable' => true,
                'default' => '',
                'description' => 'Ключ формы для хранения временных файлов от несуществующих объектов',
            ],
            'created' => [
                'name' => 'created',
                'type' => 'datetime',
                'description' => 'Дата создания (загрузки на сервер)',
            ],
            'created_by' => [
                'name' => 'created_by',
                'type' => 'int',
                'default' => 0,
                'description' => 'ID пользователя, создавшего файл',
            ],
            'updated' => [
                'name' => 'updated',
                'type' => 'datetime',
                'description' => 'Дата последнего изменения',
            ],
            'updated_by' => [
                'name' => 'updated_by',
                'type' => 'int',
                'default' => 0,
                'description' => 'ID пользователя, последним обновившего файл',
            ],
        ];
    }

    // === Методы для работы с шаблонами ===
    /**
     * Returns all Group tags for coTemplate
     *
     * @param File|int $item vuz_model_Vuz object or ID
     * @param string $tagPrefix Prefix for tags
     * @param bool $cacheitem Cache tags
     * @return array
     */
    public static function generateTags($item, $tagPrefix = '', $cacheitem = true)
    {
        global $usr, $cot_extrafields;

        static $extp_first = null, $extp_main = null;
        static $cacheArr = array();

        if (is_null($extp_first)) {
            $extp_first = cot_getextplugins('files.files.tags.first');
            $extp_main  = cot_getextplugins('files.files.tags.main');
        }

        /* === Hook === */
        foreach ($extp_first as $pl) {
            include $pl;
        }
        /* ===== */

        list(\Cot::$usr['auth_read'], \Cot::$usr['auth_write'], \Cot::$usr['isadmin']) = cot_auth('files', 'a');

        if (
            ($item instanceof File)
            && isset($cacheArr[$item->id])
            && is_array($cacheArr[$item->id])
        ) {
            $temp_array = $cacheArr[$item->id];

        } elseif (is_int($item) && is_array($cacheArr[$item])) {
            $temp_array = $cacheArr[$item];

        } else {
            if (is_int($item) && $item > 0) {
                $item = File::getById($item);
            }
            /** @var File $item  */
            if ($item && $item->id > 0) {
                $itemUrl = $item->is_img
                    ? \Cot::$cfg['files']['folder'] . '/' . $item->fullName
                    : cot_url('files', ['m' => 'files', 'a' => 'download', 'id' => $item->id]);

                $date_format = 'datetime_medium';
                $temp_array = [
                    'ID' => $item->id,
                    'URL' => $itemUrl,
                    'SOURCE' => $item->source,
                    'ITEM' => $item->source_id,
                    'FILED'=> $item->source_field,
                    'USER' => $item->user_id,
                    'PATH' => $item->fullName,
                    'NAME' => htmlspecialchars($item->original_name),
                    'EXT'  => htmlspecialchars($item->ext),
                    'IMG'  => $item->is_img && in_array(mb_strtolower($item->ext), Image::supportedFormats()),
                    'SIZE' => cot_build_filesize($item->size),
                    'SIZE_RAW' => $item->size,
                    'TITLE' => htmlspecialchars($item->title),
                    'TITLE_OR_NAME' => !empty($item->title) ? htmlspecialchars($item->title) : htmlspecialchars($item->original_name),
                    'COUNT' => $item->downloads_count,
                    'UPDATED' => $item->updated,
                    'UPDATE_DATE' => cot_date($date_format, strtotime($item->updated)),
                    'UPDATED_RAW' => strtotime($item->updated),
                    'ICON' => $item->icon,
                ];

                // Extrafields
                if (isset($cot_extrafields[static::$_tbname])) {
                    foreach ($cot_extrafields[static::$_tbname] as $exfld) {
                        $tag = mb_strtoupper($exfld['field_name']);
                        $field = 'file_'.$exfld['field_name'];
                        $temp_array[$tag.'_TITLE'] = isset(\Cot::$L['files_'.$exfld['field_name'].'_title']) ?
                            \Cot::$L['files_'.$exfld['field_name'].'_title'] : $exfld['field_description'];
                        $temp_array[$tag] = cot_build_extrafields_data('files', $exfld, $item->{$field});
                        $temp_array[$tag.'_VALUE'] = $item->{$field};
                    }
                }

                /* === Hook === */
                foreach ($extp_main as $pl) {
                    include $pl;
                }
                /* ===== */
                $cacheitem && $cacheArr[$item->id] = $temp_array;
            } else {

            }
        }

        $return_array = [];
        foreach ($temp_array as $key => $val){
            $return_array[$tagPrefix . $key] = $val;
        }

        return $return_array;
    }

}
File::__init();