<?php
(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

/**
 * Files main Admin Controller class
 * 
 * @package shop
 * @subpackage admin
 * @copyright http://portal30.ru
 *
 */
class MainController{

    /**
     * Main (index) Action.
     */
    public function indexAction(){
        global $adminpath, $adminhelp, $cot_yesno, $adminsubtitle;

        $tpl = new XTemplate(cot_tplfile('files.admin.main'));

        if (!function_exists('gd_info')){
            cot_message(cot::$L['files_nogd'], 'warning');
        }else{
            $gd_datas = gd_info();
            foreach ($gd_datas as $k => $i){
                if (mb_strlen($i) < 2) $i = $cot_yesno[$i];
                $tpl->assign(array(
                    'GD_DATAS_NAME' => $k,
                    'GD_DATAS_DATAS_ENABLE' => $i
                ));
                $tpl->parse('MAIN.GD_INFO.ROW');
            }
            $tpl->parse('MAIN.GD_INFO');
        }

        $pfsUrl = cot_url('files', array('m'=>'pfs', 'uid'=>cot::$usr['id']));
        $filesUrl = cot_url('files', array('uid'=>cot::$usr['id']));
        $albumUrl = cot_url('files', array('a'=>'album', 'uid'=>cot::$usr['id']));
        $adminhelp  = cot::$L['files_userfilespace'].": <a href=\"{$pfsUrl}\" target=\"_blank\">{$pfsUrl}</a> (".
            cot::$L['files_userfilespace_desc'].")<br />";
        $adminhelp .= cot::$L['files_userpublic_files'].": <a href=\"{$filesUrl}\" target=\"_blank\">{$filesUrl}</a><br />";
        $adminhelp .= cot::$L['files_userpublic_albums'].": <a href=\"{$albumUrl}\" target=\"_blank\">{$albumUrl}</a><br /><br />";

        $adminhelp .= "<strong>«".cot::$L['files_cleanup']."»</strong> ".cot::$L['files_cleanup_desc'].".<br />";
        $adminhelp .= cot::$L['files_deleteallthumbs_desc'];

        $adminsubtitle = cot::$L['Files'];

        $tpl->assign(array(
            'PAGE_TITLE' => cot::$L['Files'].": ".cot::$L['Administration'],
        ));
        $tpl->parse('MAIN');
        return $tpl->text();
	}


    /**
     * @return string
     * @todo на будущее, выбор поля для сортировки и фильтры
     */
    public function allpfsAction(){
        global $adminpath, $adminhelp, $adminsubtitle, $db_files, $db_users, $cot_extrafields;

        $adminpath[] = array(cot_url('admin', 'm=files&s=allpfs'), cot::$L['files_allpfs']);
        $adminhelp = cot::$L['adm_help_allpfs'];
        $adminsubtitle = cot::$L['files_allpfs'];

        $urlParams = array('m'=>'files', 'a'=> 'allpfs');
        $perPage = cot::$cfg['maxrowsperpage'];
        $perPage = 1;
        list($pg, $d, $durl) = cot_import_pagenav('d', $perPage);

        /* === Hook === */
        foreach (cot_getextplugins('admin.files.allpfs.first') as $pl)
        {
            include $pl;
        }
        /* ===== */

        $totalitems = cot::$db->query("SELECT COUNT(DISTINCT user_id) FROM $db_files
            WHERE file_source='pfs'")->fetchColumn();

       $pagenav = cot_pagenav('admin', $urlParams, $d, $totalitems, $perPage, 'd', '', cot::$cfg['jquery'] && cot::$cfg['turnajax']);

        $sqlOrder = $order = 'u.user_name ASC';

        // Если есть экстраполя ФИО, то сортировать по ним
        if (isset($cot_extrafields[$db_users]['firstname']) || isset($cot_extrafields[$db_users]['lastname']) ||
            isset($cot_extrafields[$db_users]['middlename'])){

            $sqlOrder = '';
            if (isset($cot_extrafields[$db_users]['lastname'])) $sqlOrder .= 'u.user_lastname ASC';
            if (isset($cot_extrafields[$db_users]['firstname'])){
                if($sqlOrder != '') $sqlOrder .= ', ';
                $sqlOrder .= ' u.user_firstname ASC';
            }
            if (isset($cot_extrafields[$db_users]['middlename'])){
                if($sqlOrder != '') $sqlOrder .= ', ';
                $sqlOrder .= ' u.user_middlename ASC';
            }

        }
        if (isset($cot_extrafields[$db_users]['first_name']) || isset($cot_extrafields[$db_users]['last_name']) ||
            isset($cot_extrafields[$db_users]['middle_name'])){

            $sqlOrder = '';
            if (isset($cot_extrafields[$db_users]['last_name'])) $sqlOrder .= 'u.user_last_name ASC';
            if (isset($cot_extrafields[$db_users]['first_name'])){
                if($sqlOrder != '') $sqlOrder .= ', ';
                $sqlOrder .= ' u.user_first_name ASC';
            }
            if (isset($cot_extrafields[$db_users]['middle_name'])){
                if($sqlOrder != '') $sqlOrder .= ', ';
                $sqlOrder .= ' u.user_middle_name ASC';
            }

        }
        // /Если есть экстраполя ФИО, то сортировать по ним

        $sql_pfs = cot::$db->query("SELECT DISTINCT f.user_id as uid, u.*, COUNT(*) as count FROM $db_files AS f
	        LEFT JOIN $db_users AS u ON f.user_id=u.user_id
	        WHERE file_source='pfs' GROUP BY f.user_id ORDER BY $sqlOrder LIMIT $d, ".$perPage);

        $t = new XTemplate(cot_tplfile('files.admin.allpfs'));

        $ii = 0;
        /* === Hook - Part1 : Set === */
        $extp = cot_getextplugins('admin.files.allpfs.loop');
        /* ===== */
        while ($row = $sql_pfs->fetch()){

            $t->assign(cot_generate_usertags($row, 'ALLPFS_ROW_USER_'));

            $t->assign(array(
                'ALLPFS_ROW_URL' => cot_url('files', array('m'=>'pfs', 'uid'=>$row['user_id'])),
                'ALLPFS_ROW_USER_DISPLAY_NAME' => cot_files_user_displayName($row),
                'ALLPFS_ROW_COUNT' => $row['count']
            ));

            /* === Hook - Part2 : Include === */
            foreach ($extp as $pl)
            {
                include $pl;
            }
            /* ===== */
            $t->parse('MAIN.ALLPFS_ROW');
            $ii++;
        }

        // Site file spase info
        if($d == 0){
            $t->assign(array(
                'SFS_COUNT' => files_model_File::count(array(array('file_source', 'sfs'))),
            ));
            $t->parse('MAIN.SFS');
        }

        $t->assign(array(
            'ALLPFS_PAGINATION_PREV' => $pagenav['prev'],
            'ALLPFS_PAGNAV' => $pagenav['main'],
            'ALLPFS_PAGINATION_NEXT' => $pagenav['next'],
            'ALLPFS_TOTALITEMS' => $totalitems,
            'ALLPFS_ON_PAGE' => $ii,
            'PAGE_TITLE' => cot::$L['files_allpfs'],
        ));

        /* === Hook  === */
        foreach (cot_getextplugins('admin.files.allpfs.tags') as $pl)
        {
            include $pl;
        }
        /* ===== */

        $t->parse('MAIN');
        return $t->text();

    }

    /**
     * @todo все пользователям, у которых user_avatar > 0 а файла с таким id нет, установить user_avatar = 0
     */
    public function cleanupAction(){
        global $db_forum_posts, $db_files, $db_pages;

        $count = 0;

        if (cot_module_active('forums')){
            // Remove unused forum attachments
            require_once cot_incfile('forums', 'module');

            $condition = "LEFT JOIN $db_forum_posts ON $db_files.file_item = $db_forum_posts.fp_id
		                  WHERE $db_files.file_source = 'forums' AND $db_forum_posts.fp_id IS NULL";

            $res = cot::$db->query("SELECT file_id FROM $db_files $condition")->fetchAll(PDO::FETCH_COLUMN);
            if($res){
                $files = files_model_File::find(array(array('file_id', $res)));
                if($files){
                    foreach($files as $fileRow){
                        $count++;
                        $fileRow->delete();
                    }
                }
            }
        }

        if (cot_module_active('page')){
            // Remove unused page attachments
            require_once cot_incfile('page', 'module');

            $condition = "LEFT JOIN $db_pages ON $db_files.file_item = $db_pages.page_id
		                  WHERE $db_files.file_source = 'page' AND $db_pages.page_id IS NULL";

            $res = cot::$db->query("SELECT file_id FROM $db_files $condition")->fetchAll(PDO::FETCH_COLUMN);
            if($res){
                $files = files_model_File::find(array(array('file_id', $res)));
                if($files){
                    foreach($files as $fileRow){
                        $count++;
                        $fileRow->delete();
                    }
                }
            }
        }

        $count += cot_files_formGarbageCollect();

        cot_message($count . ' ' . cot::$L['files_items_removed']);

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', 'm=files', '', true));
    }


    public function delAllThumbsAction(){

        if(empty(cot::$cfg['files']['folder']) || !file_exists(cot::$cfg['files']['folder'].'/_thumbs')){
            cot_redirect(cot_url('admin', 'm=files', '', true));
        }

        rrmdir(cot::$cfg['files']['folder'].'/_thumbs');

        // Очистим кеш, чтобы миниатюры могли перегенерироваться
        if (cot::$cache){
            if (cot::$cfg['cache_page']){
                cot::$cache->page->clear('page');
            }
            if (cot::$cfg['cache_index']){
                cot::$cache->page->clear('index');
            }
            if (cot::$cfg['cache_forums']){
                cot::$cache->page->clear('forums');
            }
        }


        cot_message(cot::$L['files_thumbs_removed']);

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', 'm=files', '', true));
    }
}