<?php
defined('COT_CODE') or die('Wrong URL.');

/**
 * Модель File
 *
 * Описание модели
 *
 * @method static files_model_File getById($pk);
 * @method static files_model_File fetchOne($conditions = array(), $order = '')
 * @method static files_model_File[] find($conditions = array(), $limit = 0, $offset = 0, $order = '');
 *
 * @property int    $file_id
 * @property int    $user_id        id пользователя - владельца или 0 - если это site file space
 * @property string $file_source    Источник / модуль - владелец
 * @property int    $file_item      id элемента, к которому привязан файл
 * @property string $file_field     Поле элемента (поционально), например 'logo'
 * @property string $file_path      Имя файла с путем относительно корня сервера
 * @property string $file_name  Исходное имя файла
 * @property string $file_ext       Расширение
 * @property bool   $file_img       Является ли изображением
 * @property int    $file_size      Размер
 * @property string $file_title     Название
 * @property int    $file_count     Количество скачиваний
 * @property int    $file_order     Порядок для отображения
 * @property string $file_updated   Дата последнего изменения
 * @property string $file_unikey    Ключ формы для хранения временных файлов от несуществующих объектов
 *
 * @property string $icon   File icon url
 *
 */
class files_model_File extends Som_Model_Abstract
{
    /**
     * @var Som_Model_Mapper_Abstract
     */
    protected  static $_db = null;
    protected  static $_tbname = '';
    protected  static $_primary_key = 'file_id';

    /**
     * Static constructor
     */
    public static function __init($db = 'db'){
        global $db_files;

        static::$_tbname = $db_files;
        parent::__init($db);
    }

    public function getIcon(){
        return files_model_File::typeIcon($this->_data['file_ext']);
    }

    public static function typeIcon($ext, $size = 48){
        $iconUrl = '';
        if(isset(cot::$R["files_icon_type_{$size}_{$ext}"])){
            $iconUrl = cot::$R["files_icon_type_{$size}_{$ext}"];
        }elseif(isset(cot::$R["files_icon_type_48_{$ext}"])){
            $iconUrl = cot::$R["files_icon_type_48_{$ext}"];
        }

        if(!empty($iconUrl)) return $iconUrl;

        if (!file_exists(cot::$cfg['modules_dir'] . "/files/img/types/$size")){
            $size = 48;
        }
        if (file_exists(cot::$cfg['modules_dir'] . "/files/img/types/$size/{$ext}.png")){
            return cot::$cfg['modules_dir'] . "/files/img/types/$size/{$ext}.png";

        }else{
            return cot::$cfg['modules_dir'] . "/files/img/types/$size/archive.png";
        }
    }

    public function makeAvatar(){
        global $db_users;

        if($this->_data['file_source'] != 'pfs' || $this->_data['file_img'] == 0 || !$this->_data['user_id']) return false;

        static::$_db->update($db_users, array('user_avatar' => $this->_data['file_id']), 'user_id=?', array($this->_data['user_id']));
    }

    protected function beforeInsert(){
        if(empty($this->_data['file_updated'])){
            $this->_data['file_updated'] = date('Y-m-d H:i:s', cot::$sys['now']);
        }

        if(empty($this->_data['file_order'])){
            $this->_data['file_order'] = ((int)static::$_db->query("SELECT MAX(file_order) FROM ".static::$_tbname."
                    WHERE file_source = ? AND file_item = ?",
                    array($this->_data['file_source'], $this->_data['file_item']))->fetchColumn()) + 1;
        }

        return true;
    }

    protected function afterInsert(){
        if(in_array($this->_data['file_source'], array('pfs', 'sfs')) && $this->_data['file_item'] > 0){
            $field = $this->_data['file_field'];
//            if($field === null) $field = '';
            $condition = array(
                array('file_source', $this->_data['file_source']),
                array('file_item', $this->_data['file_item']),
//                array('file_field', $field)
            );

            $folder = files_model_Folder::getById($this->_data['file_item']);
            if($folder){
                $folder->ff_count = files_model_File::count($condition);
                $folder->save();
            }

        }
    }

    protected function beforeUpdate(){
        $this->_data['file_updated'] = date('Y-m-d H:i:s', cot::$sys['now']);
        return true;
    }

    protected function beforeDelete(){

        $res = true;

        $path_parts = pathinfo($this->_data['file_path']);
        $res &= @unlink($this->_data['file_path']);
        $fCnt = array_sum(array_map('is_file', glob($path_parts['dirname'].'/*')));
        // Delete folder if it empty
        if($fCnt === 0)  @rmdir($path_parts['dirname']);
        $res &= $this->remove_thumbs();
        @rmdir(cot::$cfg['files']['folder'] . '/_thumbs/' . $this->_data['file_id']);

        return true;
    }

    protected function afterDelete(){
        if(in_array($this->_data['file_source'], array('pfs', 'sfs')) && $this->_data['file_item'] > 0){
            $condition = array(
                array('file_source', $this->_data['file_source']),
                array('file_item', $this->_data['file_item']),
            );

            $folder = files_model_Folder::getById($this->_data['file_item']);
            if($folder){
                $folder->ff_count = files_model_File::count($condition);
                $folder->save();
            }
        }
    }

    /**
     * Removes thumbnails matching the arguments.
     * @return boolean       true on success, false on error
     */
    public function remove_thumbs(){
        $res = true;

        $thumbs_folder = cot::$cfg['files']['folder'] . '/_thumbs/' . $this->_data['file_id'];
        $path = $thumbs_folder . '/' . cot::$cfg['files']['prefix'] . $this->_data['file_id'];
        $thumbPaths =  glob($path . '-*', GLOB_NOSORT);

        if(!empty($thumbPaths) && is_array($thumbPaths)){
            foreach ($thumbPaths as $thumb) {
                $res &= @unlink($thumb);
            }
        }

        return $res;

    }

    public static function fieldList() {
        return array(
            'file_id'  =>
                array(
                    'name'    => 'file_id',
                    'type'    => 'bigint',
                    'primary' => true,
                    'description' => 'id'
                ),
            'user_id'  =>
                array(
                    'name'      => 'user_id',
                    'type'      => 'int',
                    'description' => 'id пользователя - владельца или 0 - если это site file space'
                ),
            'file_source'  =>
                array(
                    'name'      => 'file_source',
                    'type'      => 'varchar',
                    'length'    => '64',
                    'description' => 'Источник / модуль - владелец'
                ),
            'file_item'  =>
                array(
                    'name'      => 'file_item',
                    'type'      => 'int',
                    'description' => 'id элемента, к которому привязан файл'
                ),
            'file_field'  =>
                array(
                    'name'      => 'file_field',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'nullable'  => true,
                    'default'   => '',
                    'description' => 'Публичный?'
                ),
            'file_path'  =>
                array(
                    'name'      => 'file_path',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'description' => 'Имя файла с путем относительно корня сервера'
                ),
            'file_name'  =>
                array(
                    'name'      => 'file_name',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'description' => 'Исходное имя файла',
                ),
            'file_ext'  =>
                array(
                    'name'    => 'file_ext',
                    'type'      => 'varchar',
                    'length'    => '16',
                    'description' => 'Расширение'
                ),
            'file_img'  =>
                array(
                    'name'      => 'file_img',
                    'type'      => 'bool',
                    'nullable'  => true,
                    'default'   => 0,
                    'description' => 'Является ли изображением'
                ),
            'file_size'  =>
                array(
                    'name'      => 'file_size',
                    'type'      => 'int',
                    'description' => 'Размер'
                ),
            'file_title'  =>
                array(
                    'name'      => 'file_title',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'nullable'  => true,
                    'default'   => '',
                    'description' => 'Название',
                ),
            'file_count'  =>
                array(
                    'name'      => 'file_count',
                    'type'      => 'int',
                    'nullable'  => true,
                    'default'   => 0,
                    'description' => 'Количество скачиваний',
                ),
            'file_order'  =>
                array(
                    'name'      => 'file_order',
                    'type'      => 'int',
                    'nullable'  => true,
                    'default'   => 0,
                    'description' => 'Порядок для отображения',
                ),
            'file_updated'  =>
                array(
                    'name'      => 'file_updated',
                    'type'      => 'datetime',
                    'description' => 'Дата последнего изменения',
                ),
            'file_unikey'  =>
                array(
                    'name'      => 'file_unikey',
                    'type'      => 'varchar',
                    'length'    => '255',
                    'nullable'  => true,
                    'default'   => '',
                    'description' => 'Ключ формы для хранения временных файлов от несуществующих объектов',
                ),
        );
    }

    // === Методы для работы с шаблонами ===
    /**
     * Returns all Group tags for coTemplate
     *
     * @param files_model_File|int $item vuz_model_Vuz object or ID
     * @param string $tagPrefix Prefix for tags
     * @param bool $cacheitem Cache tags
     * @return array|void
     *
     */
    public static function generateTags($item, $tagPrefix = '', $cacheitem = true){
        global $usr, $cot_extrafields;

        static $extp_first = null, $extp_main = null;
        static $cacheArr = array();

        if (is_null($extp_first)){
            $extp_first = cot_getextplugins('files.files.tags.first');
            $extp_main  = cot_getextplugins('files.files.tags.main');
        }

        /* === Hook === */
        foreach ($extp_first as $pl){
            include $pl;
        }
        /* ===== */

        list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');

        if ( ($item instanceof files_model_File) && is_array($cacheArr[$item->file_id]) ) {
            $temp_array = $cacheArr[$item->file_id];
        }elseif (is_int($item) && is_array($cacheArr[$item])){
            $temp_array = $cacheArr[$item];
        }else{
            if (is_int($item) && $item > 0){
                $item = files_model_File::getById($item);
            }
            /** @var files_model_File $item  */
            if ($item && $item->file_id > 0){
                $itemUrl = $item->file_img ? $item->file_path : cot_url('files',
                    array('m' => 'files', 'a'=>'download', 'id' => $item->file_id));

                $date_format = 'datetime_medium';
                $temp_array = array(
                    'ID' => $item->file_id,
                    'URL' => $itemUrl,
                    'SOURCE'=> $item->file_source,
                    'ITEM' => $item->file_item,
                    'FILED'=> $item->file_field,
                    'USER' => $item->user_id,
                    'PATH' => $item->file_path,
                    'NAME' => htmlspecialchars($item->file_name),
                    'EXT'  => htmlspecialchars($item->file_ext),
                    'IMG'  => $item->file_img,
                    'SIZE' => cot_build_filesize($item->file_size),
                    'SIZE_RAW' => $item->file_size,
                    'TITLE' => htmlspecialchars($item->file_title),
                    'TITLE_OR_NAME' => !empty($item->file_title) ? htmlspecialchars($item->file_title) : htmlspecialchars($item->file_name),
                    'COUNT' => $item->file_count,
                    'UPDATED' => $item->file_updated,
                    'UPDATE_DATE' => cot_date($date_format, strtotime($item->file_updated)),
                    'UPDATED_RAW' => strtotime($item->file_updated),
                    'ICON' => $item->icon,
                );

                // Extrafields
                if (isset($cot_extrafields[static::$_tbname])) {
                    foreach ($cot_extrafields[static::$_tbname] as $exfld) {
                        $tag = mb_strtoupper($exfld['field_name']);
                        $field = 'file_'.$exfld['field_name'];
                        $temp_array[$tag.'_TITLE'] = isset(cot::$L['files_'.$exfld['field_name'].'_title']) ?
                            cot::$L['files_'.$exfld['field_name'].'_title'] : $exfld['field_description'];
                        $temp_array[$tag] = cot_build_extrafields_data('files', $exfld, $item->{$field});
                        $temp_array[$tag.'_VALUE'] = $item->{$field};
                    }
                }

                /* === Hook === */
                foreach ($extp_main as $pl)
                {
                    include $pl;
                }
                /* ===== */
                $cacheitem && $cacheArr[$item->file_id] = $temp_array;
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
files_model_File::__init();