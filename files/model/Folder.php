<?php
defined('COT_CODE') or die('Wrong URL.');

/**
 * Модель File Folder
 *
 * Описание модели
 *
 * @method static files_model_Folder getById($pk);
 * @method static files_model_Folder fetchOne($conditions = array(), $order = '')
 * @method static files_model_Folder[] find($conditions = array(), $limit = 0, $offset = 0, $order = '');
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
class files_model_Folder extends Som_Model_Abstract
{
    protected  static $_db = null;
    protected  static $_tbname = '';
    protected  static $_primary_key = 'ff_id';


    /**
     * Static constructor
     */
    public static function __init($db = 'db'){
        global $db_files_folders;

        static::$_tbname = $db_files_folders;
        parent::__init($db);
    }

    protected function beforeInsert(){

        if(empty($this->_data['ff_created'])){
            $this->_data['ff_created'] = date('Y-m-d H:i:s', cot::$sys['now']);
        }

        if(empty($this->_data['ff_updated'])){
            $this->_data['ff_updated'] = date('Y-m-d H:i:s', cot::$sys['now']);
        }

        return true;
    }

    protected function beforeUpdate(){
        $this->_data['ff_updated'] = date('Y-m-d H:i:s', cot::$sys['now']);

        return true;
    }

    /**
     * Delete
     * @return bool
     */
    public function delete(){

        $uid = (int)$this->_data['user_id'];
        $isSFS = false;                             // is Site File Space

        if($uid == 0) $isSFS = true;
        $source = $isSFS ? 'sfs' : 'pfs';

        // Remove all files
        $files = files_model_File::find(array(
            array('file_source', $source),
            array('file_item', $this->_data['ff_id'])
        ));
        if(!empty($files)){
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
     * @param files_model_Folder|int $item vuz_model_Vuz object or ID
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

        list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');

        if ( ($item instanceof files_model_Folder) && is_array($cacheArr[$item->ff_id]) ) {
            $temp_array = $cacheArr[$item->ff_id];
        }elseif (is_int($item) && is_array($cacheArr[$item])){
            $temp_array = $cacheArr[$item];
        }else{
            if (is_int($item) && $item > 0){
                $item = files_model_Folder::getById($item);
            }
            /** @var files_model_Folder $item  */
            if ($item){

                $itemUrl = cot_url('files', array('f' => $item->ff_id));

                $itemEditUrl = '';
                $itemDelUrl = '';
                $itemPfsUrl = '';
                if($usr['isadmin'] || ($usr['id'] > 0 && $usr['id'] == $item->user_id)){
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
                    'PUBLIC' => (bool)$item->ff_public ? cot::$L['Yes'] : cot::$L['No'],
                    'ALBUM'  => (bool)$item->ff_album  ? cot::$L['Yes'] : cot::$L['No'],
                    'ISPUBLIC' => $item->ff_public,
                    'ISALBUM' => $item->ff_album,
                    'CREATE_DATE' => cot_date($date_format, strtotime($item->ff_created)),
                    'UPDATE_DATE' => cot_date($date_format, strtotime($item->ff_updated)),
                    'CREATED' => $item->ff_created,
                    'UPDATED' => $item->ff_updated,
                    'CREATED_RAW' => strtotime($item->ff_created),
                    'UPDATED_RAW' => strtotime($item->ff_updated),
                    'ICON' => $item->ff_album ? cot::$R['files_icon_gallery'] : cot::$R['files_icon_folder'],
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

files_model_Folder::__init();