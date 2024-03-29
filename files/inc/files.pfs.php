<?php

use cot\modules\files\models\File;
use cot\modules\files\services\FileService;

defined('COT_CODE') or die('Wrong URL.');

/**
 * PFS Controller class for the Files module
 *
 *  Editing actions
 * 
 * @package Files
 * @subpackage pfs
 * @author Cotonti Team
 * @author Kalnov Alexey    <kalnovalexey@yandex.ru>
 */
class PfsController
{
    /**
     * User files
     * @return string
     */
    public function indexAction()
    {
        global $usr, $Ls, $db_files, $db_files_folders, $outHeaderFooter, $cot_extensions;

        $perPage = Cot::$cfg['files']['maxFoldersPerPage'];

        [$pgf, $df] = cot_import_pagenav('df', $perPage);   // page number folders

        [$usr['auth_read'], $usr['auth_write'], $usr['isadmin']] = cot_auth('files', 'a');
        cot_block($usr['auth_read']);   // Это бекэнд часть для пользователя. Может надо блокировать, если нет прав на запись????

        $c1 = cot_import('c1','G','ALP');					// form name
        $c2 = cot_import('c2','G','ALP');					// input name
        $parser = cot_import('parser', 'G', 'ALP');			// custom parser

        $f = cot_import('f', 'G', 'INT');     // folder id
        if (!$f) $f = 0;
        $uid = cot_import('uid', 'G', 'INT');  // user ID or 0
        if ($uid === null) $uid = $usr['id'];

        $standalone = 0;

        $urlParams = array('m' => 'pfs');
        if(!$f && $uid != Cot::$usr['id']) $urlParams['uid'] = $uid;

        if (!empty($c1) || !empty($c2)) {
            $standalone = 1;
            if(!empty($c1)) $urlParams['c1'] = $c1;
            if(!empty($c2)) $urlParams['c2'] = $c2;
            if(!empty($parser)) $urlParams['parser'] = $parser;
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.pfs.first') as $pl) {
            include $pl;
        }
        /* ===== */

        $folders = null;
        $folder = null;
        $folders_count = 0;
        $isSFS = false;             // is Site File Space
        $onPageFoldersCount = 0;

        if ($f > 0) {
            $folder = files_models_Folder::getById($f);
            if (!$folder) cot_die_message(404);
            $uid = (int)$folder->user_id;

        } else {
            $folders = files_models_Folder::findByCondition(array(array('user_id', $uid)), $perPage, $df, array(array('ff_title', 'ASC')));
            $folders_count = files_models_Folder::count(array(array('user_id', $uid)));
            $onPageFoldersCount = !empty($folders) ? count($folders) : 0;
        }

        if ($uid === 0) {
            $isSFS = true;
            cot_block(Cot::$usr['isadmin']);

        } elseif($uid != Cot::$usr['id']) {
            cot_block(Cot::$usr['isadmin']);
        }

        $limits = cot_filesGetLimits($uid);
        // Ограничения на загрузку файлов через POST
        if (Cot::$cfg['files']['chunkSize'] == 0){
            $limits['size_maxfile']  = min((int)$limits['size_maxfile'], cot_get_uploadmax() * 1024);
        }

        $crumbs = array();
        $title = '';
        if ($isSFS) {
            $tmp = $urlParams;
            if($uid != Cot::$usr['id']) $tmp['uid'] = $uid;
            if($folder) {
                $crumbs[] = array(cot_url('files', $tmp), Cot::$L['SFS']);
                $crumbs[] = $title = $folder->ff_title;

            } else {
                $crumbs[] = $title = Cot::$L['SFS'];
            }

        } else {
            cot_block(($limits['size_maxfile'] > 0 && $limits['size_maxtotal'] > 0) || $usr['isadmin']);

            $urr = cot_user_data($uid);
            if(empty($urr) && !$usr['isadmin']) cot_die_message(404);   // Вдруг пользователь удален, а вайлы остались?

            if($uid == Cot::$usr['id']){
                if($standalone == 0) $crumbs[] = array(cot_url('users', 'm=details'), Cot::$L['files_mypage']);
                if($folder){
                    $crumbs[] = array(cot_url('files', $urlParams), Cot::$L['Mypfs']);
                    $crumbs[] = $title = $folder->ff_title;

                } else {
                    $crumbs[] = $title = Cot::$L['Mypfs'];
                }

            } else {
                $crumbs[] = array(cot_url('users'), Cot::$L['Users']);
                $crumbs[] = array(cot_url('users', 'm=details&id='.$urr['user_id'].'&u='.$urr['user_name']),
                    cot_user_full_name($urr));

                if($folder) {
                    $tmp = $urlParams;
                    if($uid != $usr['id']) $tmp['uid'] = $uid;
                    $crumbs[] = array(cot_url('files', $tmp), Cot::$L['Files']);
                    $crumbs[] = $title = $folder->ff_title;

                } else {
                    $crumbs[] = $title = Cot::$L['Files'];
                }
            }
        }

        $tpl = ($standalone == 1) ? cot_tplfile(array('files', 'pfs', 'standalone'), 'module') : cot_tplfile(array('files', 'pfs'), 'module');
        $t = new XTemplate($tpl);

        // ========== Statistics =========
        $percentage = $limits['size_maxtotal'] > 0 ? round($limits['size_used'] / $limits['size_maxtotal'] * 100) : 100;
        $progressbarClass = 'progress-bar-info';
        if ($percentage > 70) $progressbarClass = 'progress-bar-warning';
        if ($percentage > 90) $progressbarClass = 'progress-bar-danger';
        $t->assign(array(
            'PFS_TOTALSIZE' => cot_build_filesize($limits['size_used'], 1),
            'PFS_TOTALSIZE_RAW' => $limits['size_used'],
            'PFS_MAXTOTAL' => cot_build_filesize($limits['size_maxtotal'], 1),
            'PFS_MAXTOTAL_RAW' => $limits['size_maxtotal'],
            'PFS_PERCENTAGE' => $percentage,
            'PFS_PROGRESSBAR_CLASS' => $progressbarClass,
            'PFS_MAXFILESIZE' => cot_build_filesize($limits['size_maxfile'], 1),
            'PFS_MAXFILESIZE_RAW' => $limits['size_maxfile'],
        ));
        // ========== /Statistics =========

        $allowedExts = explode(',', str_replace(' ', '', Cot::$cfg['files']['exts']));
        $descriptions = array();
        foreach($cot_extensions as $row) {
            $descriptions[$row[0]]  = $row[1];
        }
        foreach($allowedExts as $ext) {
            $t->assign(array(
                'ALLOWED_ROW_ICON_URL' => FileService::typeIcon($ext),
                'ALLOWED_ROW_EXT' => $ext,
                'ALLOWED_ROW_DESC' => !empty($descriptions[$ext]) ? $descriptions[$ext] : $ext
            ));
            $t->parse('MAIN.ALLOWED_ROW');
        }


        $source = $isSFS ? 'sfs' : 'pfs';
        if ($f == 0) {
            $countCond = [
                ['source', $source],
                ['source_id', $f],
            ];
            if (!$isSFS) {
                $countCond[] = ['user_id', $uid];
            }
            $files_count = File::count($countCond);

        } else {
            $files_count = $folder->ff_count;
        }

        $t->assign(array(
            'FOLDERS_COUNT' => cot_declension($folders_count, $Ls['Folders']),
            'FOLDERS_COUNT_RAW' => $folders_count,
            'FOLDERS_ONPAGE_COUNT' => cot_declension($onPageFoldersCount, $Ls['Folders']),
            'FOLDERS_ONPAGE_COUNT_RAW' => $onPageFoldersCount,
            'FILES_WIDGET' => cot_filesFileBox($source, $f, '', 'all', -1, 'files.filebox', $standalone, $uid),
            'IS_SITE_FILE_SPACE' => $isSFS,
            'PFS_FILES_COUNT' => cot_declension($files_count, $Ls['Files']),
            'PFS_FILES_COUNT_RAW' => $files_count,
            'PFS_IS_STANDALONE' => ($standalone) ? 1 : 0,
            'PFS_IS_ROOT' => ($f == 0) ? 1 : 0,
            'PAGE_TITLE' => Cot::$out['subtitle'] =  $title,
            'BREADCRUMBS' => cot_breadcrumbs($crumbs, !$standalone && Cot::$cfg['homebreadcrumb']),
        ));

        // Если мы находимся в корне, то можем работать с папками
        if ($f == 0) {
            $foldersFilesCount = 0;
            $onPageFoldersFilesCount = 0;
            if ($folders) {
                $i = 1;
                $folderIds = array();
                foreach ($folders as $folderRow) {
                    $folderIds[] = $folderRow->ff_id;
                }

                $sql = Cot::$db->query("SELECT source_id as ff_id, COUNT(*) as items_count, SUM(size) as size
                    FROM $db_files WHERE source = '{$source}' AND source_id IN (" . implode(',', $folderIds) . ")
                    GROUP BY source_id");
                while ($pfs_filesinfo = $sql->fetch()) {
                    $ff_filessize[$pfs_filesinfo['ff_id']]  = $pfs_filesinfo['size'];
                    $onPageFoldersFilesCount += $pfs_filesinfo['items_count'];
                }

                $sql = Cot::$db->query("SELECT SUM(ff_count) as files_count FROM $db_files_folders WHERE user_id=?", $uid);
                $foldersFilesCount = $sql->fetchColumn();

                foreach($folders as $folderRow) {
                    $itemsSize = !empty($ff_filessize[$folderRow->ff_id]) ? (int) $ff_filessize[$folderRow->ff_id] : 0;
                    $t->assign(files_models_Folder::generateTags($folderRow, 'FOLDER_ROW_', $urlParams));
                    $t->assign(array(
                        'FOLDER_ROW_NUM' => $i,
                        'FOLDER_ROW_ITEMS_SIZE' => cot_build_filesize($itemsSize),
                        'FOLDER_ROW_ITEMS_SIZE_RAW' => $itemsSize,
                    ));
                    $i++;
                    $t->parse('MAIN.FOLDERS.ROW');
                }
            }

            // Folders pagination
            $pagenavFolders = cot_pagenav('files', $urlParams, $df, $folders_count, $perPage, 'df');

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

            $hidden = cot_inputbox('hidden', 'uid', $uid).cot_inputbox('hidden', 'act', 'save');
            if ($standalone){
                $hidden .= cot_inputbox('hidden', 'c1', $c1).cot_inputbox('hidden', 'c2', $c2).
                    cot_inputbox('hidden', 'parser', $parser);
            }
            $t->assign(array(
                'FOLDER_ADDFORM_URL'    => cot_url('files', array('m' => 'pfs', 'a' => 'editFolder')),
                'FOLDER_ADDFORM_TITLE'  => cot_inputbox('text', 'ff_title'),
                'FOLDER_ADDFORM_DESC'   => cot_textarea('ff_desc', '', '', ''),
                'FOLDER_ADDFORM_PUBLIC' => cot_checkbox(true, 'ff_public', Cot::$L['files_ispublic']),
                'FOLDER_ADDFORM_ALBUM'  => cot_checkbox(true, 'ff_album',  Cot::$L['files_isgallery']),
                'FOLDER_ADDFORM_HIDDEN' => $hidden,
            ));
            $t->parse('MAIN.FOLDER_NEWFORM');

            if($pgf > 1) Cot::$out['subtitle'] .= " (".Cot::$L['Page']." {$pgf})";

        } else {
            if ($folder) $t->assign(files_models_Folder::generateTags($folder, 'FOLDER_', $urlParams));
        }

        if ($standalone) {
            $outHeaderFooter = false;

            if ($c1 == 'pageform' && $c2 == 'rpageurl'){
                $pfs_code_addfile = "' + gfile + '";
                $pfs_code_addthumb = "' + gthumb +'";
                $pfs_code_addpix = "' + gfile + '";
            }else{
                $pfs_code_addfile = cot_rc('files_pfs_code_addfile');
                $pfs_code_addthumb = cot_rc('files_pfs_code_addthumb');
                $pfs_code_addpix = cot_rc('files_pfs_code_addpix');
            }
            $winclose = Cot::$cfg['files']['pfs_winclose'] ? "\nwindow.close();" : '';

            cot_sendheaders();

            $html = Resources::render();
            if (!isset(Cot::$out['head_head'])) Cot::$out['head_head'] = '';
            if (!empty($html)) Cot::$out['head_head'] = $html.Cot::$out['head_head'];

            $html = Resources::renderFooter();
            if (!isset(Cot::$out['footer_rc'])) Cot::$out['footer_rc'] = '';
            if (!empty($html)) Cot::$out['footer_rc'] = $html . Cot::$out['footer_rc'];

            $t->assign(array(
                'PFS_HEAD' => Cot::$out['head_head'],
                'PFS_HEADER_JAVASCRIPT' => cot_rc('files_pfs_code_header_javascript',
                        array('c2'=>$c2,
                            'pfs_code_addthumb' => $pfs_code_addthumb,
                            'pfs_code_addpix'   => $pfs_code_addpix,
                            'pfs_code_addfile'  => $pfs_code_addfile,
                            'winclose'          => $winclose
                        )),
                'PFS_C1' => $c1,
                'PFS_C2' => $c2,
                'FOOTER_RC' => Cot::$out['footer_rc']
            ));

            $t->parse('MAIN.STANDALONE_HEADER');
            $t->parse('MAIN.STANDALONE_FOOTER');

            /* === Hook === */
            foreach (cot_getextplugins('files.pfs.standalone') as $pl)
            {
                include $pl;
            }
            /* ===== */

        } else {
            /* === Hook === */
            foreach (cot_getextplugins('files.pfs.tags') as $pl) {
                include $pl;
            }
            /* ===== */
        }

        // Error and message handling
        cot_display_messages($t);

        $t->parse();
        return $t->text();

    }

    /**
     * Edit folder
     */
    public function editFolderAction()
    {
        global $usr, $Ls, $cot_extensions, $outHeaderFooter;

        [Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']] = cot_auth('files', 'a');
        cot_block(Cot::$usr['auth_write']);

        $f = cot_import('f', 'G', 'INT');           // folder id
        if(!$f) $f = cot_import('f', 'P', 'INT');
        $uid = cot_import('uid', 'G', 'INT');
        if(is_null($uid)) $uid = cot_import('uid', 'P', 'INT');

        $c1 = cot_import('c1','G','ALP');			// form name
        if(!$c1) $c1 = cot_import('c1', 'P', 'ALP');
        $c2 = cot_import('c2','G','ALP');			// input name
        if(!$c2) $c2 = cot_import('c2', 'P', 'ALP');
        $parser = cot_import('parser', 'G', 'ALP');	// custom parser
        if(!$parser) $parser = cot_import('parser', 'P', 'ALP');
        $standalone = 0;                            // is in popup window
        $isSFS = false;                             // is Site File Space

        $act = cot_import('act', 'P', 'ALP');
        if (!$f) {
            $f = 0;
            $folder = new files_models_Folder();
            if($uid === null) $uid = Cot::$usr['id'];
            if($uid === 0) {
                $isSFS = true;

            } else {
                $folder->user_id = $uid;
            }

        } else {
            $folder = files_models_Folder::getById($f);
            if(!$folder) cot_die_message(404, TRUE);
            $folderData = $folder->toArray();
            $uid = (int)$folder->user_id;
            if($uid == 0) $isSFS = true;
        }

        if( ($isSFS || $folder->user_id != Cot::$usr['id']) && !$usr['isadmin']) cot_die_message(404, TRUE);

        $urlParams = array('m' => 'pfs');
        if(!$f && $uid != $usr['id']) $urlParams['uid'] = $uid;

        if (!empty($c1) || !empty($c2)){
            $standalone = 1;
            if(!empty($c1)) $urlParams['c1'] = $c1;
            if(!empty($c2)) $urlParams['c2'] = $c2;
            if(!empty($parser)) $urlParams['parser'] = $parser;
        }

        if ($act == 'save') {
            $item = array();
            $item['ff_title'] = cot_import('ff_title', 'P', 'TXT');
            cot_check(empty($item['ff_title']), Cot::$L['files_foldertitlemissing'], 'ff_title');

            $item['ff_desc']   = cot_import('ff_desc', 'P', 'TXT');
            $item['ff_album']  = cot_import('ff_album', 'P', 'BOL');
            $item['ff_public'] = cot_import('ff_public', 'P', 'BOL');
            $item['user_id']   = ($isSFS) ? 0 : $uid;

            $folder->setData($item);

            if(!cot_error_found()) {
                $redirUrl = $urlParams;
                $redirUrl['a'] = 'editFolder';
                if ($f) $redirUrl['f'] = $f;
                if ($f = $folder->save()) {
                    cot_message(Cot::$L['files_saved']);
                    $redirUrl['f'] = $f;
                }
                cot_redirect(cot_url('files', $redirUrl, '', true));
            }
        }

        $limits = cot_filesGetLimits($uid);

        if ($isSFS){
            $tmp = $urlParams;
            if($uid != Cot::$usr['id']) $tmp['uid'] = $uid;
            if($f) {
                $crumbs[] = array(cot_url('files', $tmp), Cot::$L['SFS']);
                $tmp['f'] = $folder->ff_id;
                unset($tmp['uid']);
                $crumbs[] = array(cot_url('files', $tmp), $folderData['ff_title']);

            } else {
                $crumbs[] = array(cot_url('files', $urlParams), Cot::$L['SFS']);
            }

        } else {
            cot_block(($limits['size_maxfile'] > 0 && $limits['size_maxtotal'] > 0) || $usr['isadmin']);

            $urr = cot_user_data($uid);

            $tmp = $urlParams;
            if($uid != Cot::$usr['id']) $tmp['uid'] = $uid;
            if($uid == Cot::$usr['id']) {
                if($standalone == 0) $crumbs[] = array(cot_url('users', 'm=details'), Cot::$L['files_mypage']);
                $crumbs[] = array(cot_url('files', $tmp), Cot::$L['Mypfs']);
                if($f){
                    $tmp['f'] = $folder->ff_id;
                    $crumbs[] = array(cot_url('files', $tmp), $folderData['ff_title']);
                }
                Cot::$out['subtitle'] = Cot::$L['Mypfs'];

            } else {
                $crumbs[] = array(cot_url('users'), Cot::$L['Users']);
                $crumbs[] = array(cot_url('users', 'm=details&id='.$urr['user_id'].'&u='.$urr['user_name']),
                    cot_user_full_name($urr));
                $crumbs[] = array(cot_url('files', $tmp), Cot::$L['Files']);
                if($f) {
                    $crumbs[] = array(cot_url('files', array('m'=>'pfs', 'f' => $folder->ff_id)), $folderData['ff_title']);
                }
                Cot::$out['subtitle'] = Cot::$L['Files'].' - '.$urr['user_name'];
            }
        }

        if (!$f) {
            $isAlbum = cot_import('ff_album', 'P', 'BOL');
            $crumbs[] = $title = ($isAlbum) ? Cot::$L['files_newalbum'] : Cot::$L['files_newfolder'];
            Cot::$out['subtitle'] = $title.' - '.Cot::$out['subtitle'];

        } else {
            $isAlbum = cot_import('ff_album', 'P', 'BOL');
            $crumbs[] = Cot::$L['Edit'];
            $title = $folderData['ff_title'].': '.Cot::$L['Edit'];
            Cot::$out['subtitle'] = $title.' - '.Cot::$out['subtitle'];
        }

        $source = $isSFS ? 'sfs' : 'pfs';

        $tpl = cot_tplfile(array('files', 'pfs', 'folder', 'edit'), 'module');
        $t = new XTemplate($tpl);

        $folderFormHidden = cot_inputbox('hidden', 'uid', $uid).cot_inputbox('hidden', 'f', $f).
            cot_inputbox('hidden', 'act', 'save');

        if($f > 0) {
            $t->assign(files_models_Folder::generateTags($folder, 'FOLDER_', $urlParams));

        } else {
            $folder->ff_public = 1;
            $folder->ff_album = 1;
        }

        $folderFormAlbum = cot_checkbox($folder->ff_album, 'ff_album',  Cot::$L['files_isgallery']);

        // Если в папке есть файлы не изображения, то это не альбом
        if (
            $f > 0
            && File::count([['source', $source], ['source_id', $f], ['is_img', 0]]) > 0
        ) {
            $folderFormAlbum = '';
            $folderFormHidden .= cot_inputbox('hidden', 'ff_album', 0);
            $folder->ff_album = 0;
        }

        if (!empty($c1)) {
            $folderFormHidden .= cot_inputbox('hidden', 'c1', $c1);
        }
        if (!empty($c2)) {
            $folderFormHidden .= cot_inputbox('hidden', 'c2', $c2);
        }

        $t->assign(array(
            'FOLDER_FORM_URL'    => cot_url('files', array('m' => 'pfs', 'a' => 'editFolder')),
            'FOLDER_FORM_TITLE'  => cot_inputbox('text', 'ff_title', $folder->ff_title),
            'FOLDER_FORM_DESC'   => cot_textarea('ff_desc', $folder->ff_desc, '', ''),
            'FOLDER_FORM_PUBLIC' => cot_checkbox($folder->ff_public, 'ff_public', Cot::$L['files_ispublic']),
            'FOLDER_FORM_ALBUM'  => $folderFormAlbum,
            'FOLDER_FORM_HIDDEN' => $folderFormHidden,
        ));
        $t->parse('MAIN.FORM');

        // ========== Statistics =========
        $percentage = $limits['size_maxtotal'] > 0 ? round($limits['size_used'] / $limits['size_maxtotal'] * 100) : 100;
        $progressbarClass = 'progress-bar-info';
        if($percentage > 70) $progressbarClass = 'progress-bar-warning';
        if($percentage > 90) $progressbarClass = 'progress-bar-danger';
        $t->assign(array(
            'PFS_TOTALSIZE' => cot_build_filesize($limits['size_used'], 1),
            'PFS_TOTALSIZE_RAW' => $limits['size_used'],
            'PFS_MAXTOTAL' => cot_build_filesize($limits['size_maxtotal'], 1),
            'PFS_MAXTOTAL_RAW' => $limits['size_maxtotal'],
            'PFS_PERCENTAGE' => $percentage,
            'PFS_PROGRESSBAR_CLASS' => $progressbarClass,
            'PFS_MAXFILESIZE' => cot_build_filesize($limits['size_maxfile'], 1),
            'PFS_MAXFILESIZE_RAW' => $limits['size_maxfile'],
        ));
        // ========== /Statistics =========

        $allowedExts = explode(',', str_replace(' ', '', Cot::$cfg['files']['exts']));
        $descriptions = array();
        foreach($cot_extensions as $row){
            $descriptions[$row[0]]  = $row[1];
        }
        foreach($allowedExts as $ext) {
            $t->assign(array(
                'ALLOWED_ROW_ICON_URL' => FileService::typeIcon($ext),
                'ALLOWED_ROW_EXT' => $ext,
                'ALLOWED_ROW_DESC' => !empty($descriptions[$ext]) ? $descriptions[$ext] : $ext
            ));
            $t->parse('MAIN.ALLOWED_ROW');
        }

        $t->assign(array(
            'PFS_FILES_COUNT' => cot_declension($folder->ff_count, $Ls['Files']),
            'PFS_FILES_COUNT_RAW' => $folder->ff_count,

            'FILES_WIDGET' => ($folder->ff_id > 0 ) ?
                    cot_filesFileBox($source, $f, '', 'all', -1, 'files.filebox', $standalone) : '',
            'IS_SITE_FILE_SPACE' => $isSFS,
            'PAGE_TITLE' => Cot::$out['subtitle'] =  $title,
            'BREADCRUMBS' => cot_breadcrumbs($crumbs, !$standalone && Cot::$cfg['homebreadcrumb']),
        ));

        if ($standalone == 1){

            $outHeaderFooter = false;

            if ($c1 == 'pageform' && $c2 == 'rpageurl'){
                $pfs_code_addfile = "' + gfile + '";
                $pfs_code_addthumb = "' + gthumb +'";
                $pfs_code_addpix = "' + gfile + '";

            } else {
                $pfs_code_addfile = cot_rc('files_pfs_code_addfile');
                $pfs_code_addthumb = cot_rc('files_pfs_code_addthumb');
                $pfs_code_addpix = cot_rc('files_pfs_code_addpix');
            }
            $winclose = Cot::$cfg['files']['pfs_winclose'] ? "\nwindow.close();" : '';

            cot_sendheaders();

            $html = Resources::render();
            if (!isset(Cot::$out['head_head'])) Cot::$out['head_head'] = '';
            if (!empty($html)) Cot::$out['head_head'] = $html . Cot::$out['head_head'];

            $html = Resources::renderFooter();
            if (!isset(Cot::$out['footer_rc'])) Cot::$out['footer_rc'] = '';
            if (!empty($html)) Cot::$out['footer_rc'] = $html . Cot::$out['footer_rc'];

            $t->assign(array(
                'PFS_HEAD' => Cot::$out['head_head'],
                'PFS_HEADER_JAVASCRIPT' => cot_rc('files_pfs_code_header_javascript',
                    array('c2'=>$c2,
                        'pfs_code_addthumb' => $pfs_code_addthumb,
                        'pfs_code_addpix'   => $pfs_code_addpix,
                        'pfs_code_addfile'  => $pfs_code_addfile,
                        'winclose'          => $winclose
                    )),
                'PFS_C1' => $c1,
                'PFS_C2' => $c2,
                'FOOTER_RC' => Cot::$out['footer_rc']
            ));

            $t->parse('MAIN.STANDALONE_HEADER');
            $t->parse('MAIN.STANDALONE_FOOTER');

            /* === Hook === */
            foreach (cot_getextplugins('files.pfs.standalone') as $pl) {
                include $pl;
            }
            /* ===== */

        } else {
            /* === Hook === */
            foreach (cot_getextplugins('files.pfs.tags') as $pl) {
                include $pl;
            }
            /* ===== */
        }

        // Error and message handling
        cot_display_messages($t);

        $t->parse();
        return $t->text();
    }

    /**
     * Delete folder
     */
    public function deleteFolderAction(){
        global $usr;

        [$usr['auth_read'], $usr['auth_write'], $usr['isadmin']] = cot_auth('files', 'a');
        cot_block($usr['auth_write']);

        $f = cot_import('f', 'G', 'INT');           // folder id
        if(!$f) cot_die_message(404);
        $folder = files_models_Folder::getById($f);
        if(!$folder) cot_die_message(404);
        $uid = (int)$folder->user_id;

        $c1 = cot_import('c1','G','ALP');			// form name
        if(!$c1) $c1 = cot_import('c1', 'P', 'ALP');
        $c2 = cot_import('c2','G','ALP');			// input name
        if(!$c2) $c2 = cot_import('c2', 'P', 'ALP');
        $parser = cot_import('parser', 'G', 'ALP');	// custom parser
        if(!$parser) $parser = cot_import('parser', 'P', 'ALP');
        $standalone = 0;                        // is in popup window
        $isSFS = false;                         // is Site File Space

        if($uid == 0) $isSFS = true;

        if( ($isSFS || $folder->user_id != $usr['id']) && ! $usr['isadmin']) cot_die_message(404, TRUE);

        $urlParams = array('m' => 'pfs');
        if($uid != $usr['id']) $urlParams['uid'] = $uid;

        if (!empty($c1) || !empty($c2)){
            $standalone = 1;
            if(!empty($c1)) $urlParams['c1'] = $c1;
            if(!empty($c2)) $urlParams['c2'] = $c2;
            if(!empty($parser)) $urlParams['parser'] = $parser;
        }

        $folderArr = $folder->toArray();

        $folder->delete();
        cot_message(sprintf(Cot::$L['files_folder_deleted'], $folderArr['ff_title']));
        cot_redirect(cot_url('files', $urlParams, '', true));
    }
}