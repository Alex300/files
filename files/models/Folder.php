<?php

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL.');

if(empty($GLOBALS['db_files_folders'])) {
    Cot::$db->registerTable('files_folders');
    cot_extrafields_register_table('files_folders');
}

/**
 * Модель File Folder
 *
 * @method static files_models_Folder getById($pk, $staticCache = true);
 * @method static files_models_Folder fetchOne($conditions = array(), $order = '')
 * @method static files_models_Folder[] findByCondition($conditions = array(), $limit = 0, $offset = 0, $order = '')
 *
 * @property int    $ff_id
 * @property int    $user_id     id пользователя - владельца или 0 - если это site file space
 * @property string $ff_title    Название папки/альбома
 * @property string $ff_desc     Описание папки/альбома
 * @property bool   $ff_public   Публичный?
 * @property bool   $ff_album    Галерея?
 * @property int    $ff_count    Количество элементов
 * @property string $ff_created  Дата создания
 * @property string $ff_updated  Дата последнего изменения
 *
 */
class files_models_Folder extends Som_Model_ActiveRecord
{
    protected  static $_db = null;
    protected  static $_tbname = '';
    protected  static $_primary_key = 'ff_id';


    /**
     * Static constructor
     * @param string $db Data base connection config name
     */
    public static function __init($db = 'db')
    {
        static::$_tbname = Cot::$db->files_folders;
        parent::__init($db);
    }

    protected function beforeInsert()
    {
        if(empty($this->_data['ff_created'])){
            $this->_data['ff_created'] = date('Y-m-d H:i:s', Cot::$sys['now']);
        }

        if(empty($this->_data['ff_updated'])){
            $this->_data['ff_updated'] = date('Y-m-d H:i:s', Cot::$sys['now']);
        }

        return parent::beforeInsert();
    }

    protected function beforeUpdate()
    {
        $this->_data['ff_updated'] = date('Y-m-d H:i:s', Cot::$sys['now']);

        // Update files count in this folder
        if (!array_key_exists('ff_count', $this->_oldData)) {
            $source = ($this->_data['user_id'] > 0) ? 'pfs' : 'sfs';
            $condition = [
                ['source', $source],
                ['source_id', $this->_data['ff_id']],
            ];
            $this->_data['ff_count'] = File::count($condition);
        }
        return parent::beforeUpdate();
    }

    /**
     * Delete
     * @return bool
     */
    public function delete()
    {
        $uid = (int) $this->_data['user_id'];
        $isSFS = false;                             // is Site File Space

        if ($uid == 0) {
            $isSFS = true;
        }
        $source = $isSFS ? 'sfs' : 'pfs';

        // Remove all files
        $files = File::findByCondition(
            [
                ['source', $source],
                ['file_item', $this->_data['ff_id']],
            ]
        );

        if (!empty($files)) {
            foreach($files as $fileRow){
                $fileRow->delete();
            }
        }

        return parent::delete();
    }

    public static function fieldList() {
        return array(
            'ff_id'  =>
                array(
                    'name'    => 'ff_id',
                    'type'    => 'bigint',
                    'primary' => true,
                    'description' => 'id'
                ),
            'user_id'  =>
                array(
                    'name'      => 'user_id',
                    'type'      => 'int',
                    'nullable'  => true,
                    'description' => 'id пользователя - владельца или 0 - если это site file space'
                ),
            'ff_title'  =>
                array(
                    'name'      => 'ff_title',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'description' => 'Название папки/альбома'
                ),
            'ff_desc'  =>
                array(
                    'name'      => 'ff_desc',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'nullable'  => true,
                    'default'   => '',
                    'description' => 'Описание папки/альбома'
                ),
            'ff_public'  =>
                array(
                    'name'      => 'ff_public',
                    'type'      => 'bool',
                    'nullable'  => true,
                    'default'   => 1,
                    'description' => 'Публичный?'
                ),
            'ff_album'  =>
                array(
                    'name'      => 'ff_album',
                    'type'      => 'bool',
                    'nullable'  => true,
                    'default'   => 0,
                    'description' => 'Галерея?'
                ),
            'ff_count'  =>
                array(
                    'name'    => 'ff_count',
                    'type'    => 'int',
                    'nullable'  => true,
                    'default'   => 0,
                    'description' => 'Количество элементов'
                ),
            'ff_created'  =>
                array(
                    'name'      => 'ff_created',
                    'type'      => 'datetime',
                    'nullable'  => true,
                    'default'   => NULL,
                    'description' => 'Дата создания'
                ),

            'ff_updated'  =>
                array(
                    'name'      => 'ff_updated',
                    'type'      => 'datetime',
                    'nullable'  => true,
                    'default'   => NULL,
                    'description' => 'Дата последней модификации'
                ),

        );
    }

    // === Методы для работы с шаблонами ===
    /**
     * Returns all Group tags for coTemplate
     *
     * @param files_models_Folder|int $item vuz_model_Vuz object or ID
     * @param string $tagPrefix Prefix for tags
     * @param array $urlParams
     * @param bool $cacheitem Cache tags
     * @return array|void
     */
    public static function generateTags($item, $tagPrefix = '', $urlParams = array(), $cacheitem = true){
        global $cfg, $L, $usr, $cot_countries;

        static $extp_first = null, $extp_main = null;
        static $cacheArr = array();

        if (is_null($extp_first)){
            $extp_first = cot_getextplugins('files.folder.tags.first');
            $extp_main  = cot_getextplugins('files.folder.tags.main');
        }

        /* === Hook === */
        foreach ($extp_first as $pl){
            include $pl;
        }
        /* ===== */

        if(empty($urlParams)) $urlParams = array('m' => 'pfs');

        [Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']] = cot_auth('files', 'a');

        if (($item instanceof files_models_Folder) && isset($cacheArr[$item->ff_id]) && is_array($cacheArr[$item->ff_id])) {
            $temp_array = $cacheArr[$item->ff_id];
        } elseif (is_int($item) && is_array($cacheArr[$item])) {
            $temp_array = $cacheArr[$item];
        } else {
            if (is_int($item) && $item > 0) {
                $item = files_models_Folder::getById($item);
            }
            /** @var files_models_Folder $item  */
            if ($item) {
                $itemUrl = cot_url('files', array('f' => $item->ff_id));

                $itemEditUrl = '';
                $itemDelUrl = '';
                $itemPfsUrl = '';
                if ($usr['isadmin'] || ($usr['id'] > 0 && $usr['id'] == $item->user_id)) {
                    $urlParams['f'] = $item->ff_id;
                    $itemPfsUrl = cot_url('files',$urlParams);

                    $tmp = $urlParams;
                    $tmp['a'] = 'editFolder';
                    $itemEditUrl = cot_url('files', $tmp);

                    $tmp['a'] = 'deleteFolder';
                    $itemDelUrl  = cot_confirm_url(cot_url('files', $tmp));
                }

                $date_format = 'datetime_medium';
                $temp_array = array(
                    'URL' => $itemUrl,
                    'EDIT_URL' => $itemEditUrl,
                    'PFS_URL' => $itemPfsUrl,
                    'DELETE_URL' => $itemDelUrl,
                    'ID' => $item->ff_id,
                    'TITLE' => htmlspecialchars($item->ff_title),
                    'DESC'  => htmlspecialchars($item->ff_desc),
                    'ITEMS_COUNT' => $item->ff_count,
                    'PUBLIC' => (bool)$item->ff_public ? Cot::$L['Yes'] : Cot::$L['No'],
                    'ALBUM'  => (bool)$item->ff_album  ? Cot::$L['Yes'] : Cot::$L['No'],
                    'ISPUBLIC' => $item->ff_public,
                    'ISALBUM' => $item->ff_album,
                    'CREATE_DATE' => cot_date($date_format, strtotime($item->ff_created)),
                    'UPDATE_DATE' => cot_date($date_format, strtotime($item->ff_updated)),
                    'CREATED' => $item->ff_created,
                    'UPDATED' => $item->ff_updated,
                    'CREATED_RAW' => strtotime($item->ff_created),
                    'UPDATED_RAW' => strtotime($item->ff_updated),
                    'ICON' => $item->ff_album ? Cot::$R['files_icon_gallery'] : Cot::$R['files_icon_folder'],
                );

                /* === Hook === */
                foreach ($extp_main as $pl)
                {
                    include $pl;
                }
                /* ===== */
                $cacheitem && $cacheArr[$item->ff_id] = $temp_array;
            }else{

            }
        }

        $return_array = array();
        foreach ($temp_array as $key => $val){
            $return_array[$tagPrefix . $key] = $val;
        }

        return $return_array;
    }

}

files_models_Folder::__init();