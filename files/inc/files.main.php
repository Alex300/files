<?php
defined('COT_CODE') or die('Wrong URL.');

/**
 * Main PFS Controller class for the Files module
 *
 *  Вывод папок и файлов пользователя
 * 
 * @package Files
 * @subpackage pfs
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2008-2014
 */
class MainController{


    /**
     * файлы пользователя
     * @param string $type
     * @return string
     */
    public function indexAction($type = 'all'){
        global $usr, $Ls, $db_files, $db_files_folders, $cot_modules;

        $perPage = cot::$cfg['files']['maxFoldersPerPage'];

        list($pgf, $df) = cot_import_pagenav('df', $perPage);   // page number folders

        list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');
        cot_block($usr['auth_read']);

        $f = cot_import('f', 'G', 'INT');     // folder id
        if(!$f) $f = 0;
        $uid = cot_import('uid', 'G', 'INT');  // user ID or 0
        if($uid === null) $uid = $usr['id'];

        $urlParams = array();
        if(!$f && $uid != $usr['id']) $urlParams['uid'] = $uid;

        /* === Hook === */
        foreach (cot_getextplugins('files.first') as $pl)
        {
            include $pl;
        }
        /* ===== */

        $folders = null;
        $folder = null;
        $folders_count = 0;
        $isSFS = false;                        // is Site File Space

        if($f > 0){
            $folder = files_model_Folder::getById($f);
            if(!$folder) cot_die_message(404);
            $uid = (int)$folder->user_id;

            // Private folders
            if(!$usr['isadmin'] && $uid != $usr['id'] && !$folder->ff_public){
                cot_die_message(404);
            }
            $type = ($folder->ff_album) ? 'image' : 'all';

        }else{
            $foldersCond = array(
                array('user_id', $uid),
            );
            if($type == 'image'){
                $foldersCond[] = array('ff_album', 1);
            }else{
                $foldersCond[] = array('ff_album', 0);
            }
            if(!$usr['isadmin'] && $uid != $usr['id']){
                $foldersCond[] = array('ff_public', 1);
            }
            $folders = files_model_Folder::find($foldersCond, $perPage, $df, array(array('ff_title', 'ASC')));
            $folders_count = files_model_Folder::count($foldersCond);
            $onPageFoldersCount = count($folders);
        }

        if($uid === 0){
            $isSFS = true;
            cot_block($usr['id'] > 0);     // Незареги не видят sfs вообще
        }

        $crumbs = array();
        $title = '';
        if($isSFS){
            $tmp = $urlParams;
            if($uid != $usr['id']) $tmp['uid'] = $uid;
            if($folder){
                $crumbs[] = array(cot_url('files', $tmp), cot::$L['SFS']);
                $crumbs[] = $title = $folder->ff_title;
            }else{
                $crumbs[] = $title = cot::$L['SFS'];
            }

        }else{

            $urr = cot_user_data($uid);
            if(empty($urr) && !$usr['isadmin']) cot_die_message(404);   // Вдруг пользователь удален, а файлы остались?

            if($uid == $usr['id']){
                $crumbs[] = array(cot_url('users', array('m' => 'details')), cot::$L['files_mypage']);
                if($folder){
                    if($type == 'image'){
                        $crumbs[] = array(cot_url('files', array_merge($urlParams, array('a' => 'album'))), cot::$L['files_albums']);
                    }else{
                        $crumbs[] = array(cot_url('files', $urlParams), cot::$L['Mypfs']);
                    }
                    $crumbs[] = $title = $folder->ff_title;
                }else{
                    if($type == 'image'){
                        $crumbs[] = $title = cot::$L['files_albums'];
                    }else{
                        $crumbs[] = $title = cot::$L['Mypfs'];
                    }
                }

            }else{
                $crumbs[] = array(cot_url('users'), cot::$L['Users']);
                $crumbs[] = array(cot_url('users', 'm=details&id='.$urr['user_id'].'&u='.$urr['user_name']),
                    cot_user_full_name($urr));
                if($folder){
                    $tmp = $urlParams;
                    if($uid != $usr['id']) $tmp['uid'] = $uid;
                    if($type == 'image'){
                        $crumbs[] = array(cot_url('files', array_merge($tmp, array('a' => 'album'))), cot::$L['files_albums']);
                    }else{
                        $crumbs[] = array(cot_url('files', $tmp), cot::$L['Files']);
                    }
                    $crumbs[] = $title = $folder->ff_title;
                }else{
                    if($type == 'image'){
                        $crumbs[] = $title = cot::$L['files_albums'];
                    }else{
                        $crumbs[] = $title = cot::$L['Files'];
                    }
                }
            }
        }

        $source = $isSFS ? 'sfs' : 'pfs';

        $filesCond = array(
            array('file_source', $source),
            array('file_item', $f),
        );
        if($type == 'image') $filesCond[] = array('file_img', 1);

        if($f == 0){
            if(!$isSFS) $filesCond[] = array('user_id', $uid);
            $files_count = intval(files_model_File::count($filesCond));
        }else{
            $files_count = $folder->ff_count;
        }
        $files = files_model_File::find($filesCond, 0, 0, 'file_order ASC');

        // Права на редактирование
        $canEdit = 0;
        if($usr['isadmin'] || $uid == $usr['id']) $canEdit = 1;
        if($isSFS && !$usr['isadmin']) $canEdit = 0;

        $uploadUrlParams = array('m'=>'files', 'a'=>'display', 'source'=>$source, 'item'=>$f,
            'nc'=>$cot_modules['files']['version']);

        if(!$isSFS && $uid != $usr['id'] && $usr['isadmin']){
            $uploadUrlParams['uid'] = $uid;
        }

        $tpl = cot_tplfile(array('files', $type), 'module');
        $t = new XTemplate($tpl);

        $t->assign(array(
            'FOLDERS_COUNT' => cot_declension($folders_count, $Ls['Folders']),
            'FOLDERS_COUNT_RAW' => $folders_count,
            'FOLDERS_ONPAGE_COUNT' => cot_declension($onPageFoldersCount, $Ls['Folders']),
            'FOLDERS_ONPAGE_COUNT_RAW' => $onPageFoldersCount,
            'IS_SITE_FILE_SPACE' => $isSFS,
            'FILES_COUNT' => cot_declension($files_count, $Ls['Files']),
            'FILES_COUNT_RAW' => $files_count,
            'FILES_IS_ROOT' => ($f == 0) ? 1 : 0,
            'PAGE_TITLE' => cot::$out['subtitle'] =  $title,
            'FILES_CAN_EDIT' => $canEdit,
            'FILES_SOURCE' => $source,
            'FILES_TYPE' => $type,
            'FILES_UPLOADURL'     => cot_url('files', $uploadUrlParams, '', true),
            'BREADCRUMBS' => cot_breadcrumbs($crumbs, cot::$cfg['homebreadcrumb']),
        ));

        if(!$isSFS){
            $t->assign(cot_generate_usertags($urr, 'USER_'));
            $t->assign(array(
                'USER_GENDER_RAW' => $urr['user_gender'],
                'USER_COUNTRY_RAW' => $urr['user_country'],
                // @deprecated use ...USER_FULL_NAME
                'USER_DISPLAY_NAME' => htmlspecialchars(cot_user_full_name($urr)),
                'USER_FULL_NAME' => htmlspecialchars(cot_user_full_name($urr)),
            ));
        }

        // Если мы находимся в корне, то можем работать с папками
        if($f == 0){
            if($folders){

                $folderIds = array();
                $onPageFoldersFilesCount = 0;
                foreach($folders as $folderRow){
                    $folderIds[] = $folderRow->ff_id;
                }

                $sql = cot::$db->query("SELECT file_item as ff_id, COUNT(*) as items_count, SUM(file_size) as size
                    FROM $db_files WHERE file_source='{$source}' AND file_item IN (".implode(',', $folderIds).")
                    GROUP BY file_item");
                while ($pfs_filesinfo = $sql->fetch()){
                    $ff_filessize[$pfs_filesinfo['ff_id']]  = $pfs_filesinfo['size'];
                    $onPageFoldersFilesCount += $pfs_filesinfo['items_count'];
                }

                $sql = cot::$db->query("SELECT SUM(ff_count) as files_count FROM $db_files_folders WHERE user_id=?", $uid);
                $foldersFilesCount = $sql->fetchColumn();

                $fLimit = 3;
                if($type == 'image') $fLimit = 6;
                $i = 1;
                foreach($folders as $folderRow){
                    $t->assign(files_model_Folder::generateTags($folderRow, 'FOLDER_ROW_', $urlParams));
                    $t->assign(array(
                        'FOLDER_ROW_NUM' => $i,
                        'FOLDER_ROW_ITEMS_SIZE' => cot_build_filesize((int)$ff_filessize[$folderRow->ff_id]),
                        'FOLDER_ROW_ITEMS_SIZE_RAW' => (int)$ff_filessize[$folderRow->ff_id],
                    ));

                    $filesRowCond = array(
                        array('file_source', $source),
                        array('file_item', $folderRow->ff_id),
                    );
                    if($type == 'image') $filesRowCond[] = array('file_img', 1);
                    $folderFiles = files_model_File::find($filesRowCond, $fLimit, 0, 'file_order ASC');
                    if($folderFiles){
                        $jj = 0;
                        foreach($folderFiles as $fileRow){
                            $t->assign(files_model_File::generateTags($fileRow, 'FILES_ROW_'));
                            $t->assign(array(
                                'FILES_ROW_NUM'      => $jj,
                            ));
                            $t->parse('MAIN.FOLDERS.ROW.FILES_ROW');
                            $jj++;
                        }
                    }
                    $i++;
                    $t->parse('MAIN.FOLDERS.ROW');
                }
            }else{
                $t->parse('MAIN.FOLDERS.EMPTY');
            }

            if($usr['auth_write']){
                if(($isSFS && $usr['isadmin']) || ($uid == $usr['id'])){
                    $formHidden = cot_inputbox('hidden', 'uid', $uid).cot_inputbox('hidden', 'act', 'save');
                    $formAlbum = cot_checkbox(true, 'ff_album',  cot::$L['files_isgallery']);
                    if($type == 'image'){
                        $formHidden .= cot_inputbox('hidden', 'ff_album', 1);
                        $formAlbum = '';
                    }elseif($type == 'file'){
                        $formHidden .= cot_inputbox('hidden', 'ff_album', 0);
                        $formAlbum = '';
                    }
                    $t->assign(array(
                        'FOLDER_ADDFORM_URL'    => cot_url('files', array('m' => 'pfs', 'a' => 'editFolder')),
                        'FOLDER_ADDFORM_TITLE'  => cot_inputbox('text', 'ff_title'),
                        'FOLDER_ADDFORM_DESC'   => cot_textarea('ff_desc', '', '', ''),
                        'FOLDER_ADDFORM_PUBLIC' => cot_checkbox(true, 'ff_public', cot::$L['files_ispublic']),
                        'FOLDER_ADDFORM_ALBUM'  => $formAlbum,
                        'FOLDER_ADDFORM_HIDDEN' => $formHidden,
                    ));
                    $t->parse('MAIN.FOLDER_NEWFORM');
                }
            }

            // Folders pagination
            $tmp = $urlParams;
            if($type = 'image') $tmp['a'] = 'album';
            $pagenavFolders = cot_pagenav('files', $tmp, $df, $folders_count, $perPage, 'df');

            $t->assign(array(
                'FOLDERS_FILES_COUNT' => cot_declension($foldersFilesCount, $Ls['Files']),
                'FOLDERS_FILES_COUNT_RAW' => $foldersFilesCount,
                'FOLDERS_ONPAGE_FILES_COUNT' => cot_declension($onPageFoldersFilesCount, $Ls['Files']),
                'FOLDERS_ONPAGE_FILES_COUNT_RAW' => $onPageFoldersFilesCount,

                'FOLDERS_PAGINATION'    => $pagenavFolders['main'],
                'FOLDERS_PAGEPREV'      => $pagenavFolders['prev'],
                'FOLDERS_PAGENEXT'      => $pagenavFolders['next'],
                'FOLDERS_CURRENTPAGE'   => $pagenavFolders['current'],
                'FOLDERS_MAXPERPAGE'    => $perPage,
                'FOLDERS_TOTALPAGES'    => $pagenavFolders['total']
            ));

            $t->parse('MAIN.FOLDERS');

            if($pgf > 1) cot::$out['subtitle'] .= " (".cot::$L['Page']." {$pgf})";

        }

        if($folder){
            $t->assign(files_model_Folder::generateTags($folder, 'FOLDER_', $urlParams));
        }else{
            $t->assign(array(
                'FOLDER_ID' => 0,
            ));
        }


        if($files){
            $jj = 0;
            foreach($files as $fileRow){
                $t->assign(files_model_File::generateTags($fileRow, 'FILES_ROW_'));
                $t->assign(array(
                    'FILES_ROW_NUM'      => $jj,
                ));
                $t->parse('MAIN.FILES.ROW');
                $jj++;
            }
        }
        $t->parse('MAIN.FILES');

        /* === Hook === */
        foreach (cot_getextplugins('files.tags') as $pl)
        {
            include $pl;
        }
        /* ===== */


        // Error and message handling
        cot_display_messages($t);

        $t->parse();
        return $t->text();

    }

    /**
     * Альбомы пользователя
     */
    public function albumAction(){
        return $this->indexAction('image');
    }

    /**
     * Файлы пользователя
     */
    public function filesAction(){
        return $this->indexAction('file');
    }


}