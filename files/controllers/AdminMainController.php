<?php

namespace cot\modules\files\controllers;

use cot\modules\files\model\File;
use image\Image;

/**
 * @package Files
 */
class AdminMainController
{
    /**
     * Main (index) Action.
     */
    public function indexAction()
    {
        global $adminpath, $adminhelp, $cot_yesno, $adminsubtitle;

        $tpl = new \XTemplate(cot_tplfile('files.admin.main'));

        $imagickIsAvailable = (new \image\imagick\Image())->isAvailable();
        $gdIsAvailable = (new \image\gd\Image())->isAvailable();
        $currentDriver = Image::currentDriver();

        if (!$imagickIsAvailable && !$gdIsAvailable) {
            cot_message(\Cot::$L['files_err_no_driver'], 'warning');
        } else {
            if ($imagickIsAvailable) {
                $data = [
                    'Version' => \Imagick::getVersion()['versionString'],
                    'Supported formats' => implode(', ', \Imagick::queryformats()),
                ];
                foreach ($data as $key => $value) {
                    $tpl->assign([
                        'IMAGICK_DATA_NAME' => $key,
                        'IMAGICK_DATA_VALUE' => $value
                    ]);
                    $tpl->parse('MAIN.IMAGICK_INFO.ROW');
                }
                $tpl->assign(['IS_ACTIVE' => $currentDriver === Image::DRIVER_IMAGICK]);
                $tpl->parse('MAIN.IMAGICK_INFO');
            }

            if ($gdIsAvailable) {
                $data = gd_info();
                foreach ($data as $key => $value) {
                    if (mb_strlen($value) < 2) {
                        $value = $cot_yesno[$value];
                    }
                    $tpl->assign([
                        'GD_DATA_NAME' => $key,
                        'GD_DATA_VALUE' => $value
                    ]);
                    $tpl->parse('MAIN.GD_INFO.ROW');
                }
                $tpl->assign(['IS_ACTIVE' => $currentDriver === Image::DRIVER_GD]);
                $tpl->parse('MAIN.GD_INFO');
            }
        }

        $pfsUrl = cot_url('files', ['m' => 'pfs', 'uid' => \Cot::$usr['id']]);
        $filesUrl = cot_url('files', ['uid' => \Cot::$usr['id']]);
        $albumUrl = cot_url('files', ['a' => 'album', 'uid' => \Cot::$usr['id']]);
        $adminhelp  = \Cot::$L['files_userfilespace'] . ": <a href=\"{$pfsUrl}\" target=\"_blank\">{$pfsUrl}</a> (".
            \Cot::$L['files_userfilespace_desc'].")<br />";
        $adminhelp .= \Cot::$L['files_userpublic_files'] . ": <a href=\"{$filesUrl}\" target=\"_blank\">{$filesUrl}</a><br />";
        $adminhelp .= \Cot::$L['files_userpublic_albums'] . ": <a href=\"{$albumUrl}\" target=\"_blank\">{$albumUrl}</a><br /><br />";

        $adminhelp .= "<strong>«".\Cot::$L['files_cleanup'] . "»</strong> " . \Cot::$L['files_cleanup_desc'].".<br />";
        $adminhelp .= \Cot::$L['files_deleteallthumbs_desc'];

        $adminsubtitle = \Cot::$L['Files'];

        $tpl->assign(array(
            'PAGE_TITLE' => \Cot::$L['Files'] . ": " . \Cot::$L['Administration'],
        ));
        $tpl->parse('MAIN');
        return $tpl->text();
    }

    /**
     * @return string
     * @todo на будущее, выбор поля для сортировки и фильтры
     */
    public function allpfsAction()
    {
        global $adminpath, $adminhelp, $adminsubtitle, $db_files, $db_users, $cot_extrafields;

        $adminpath[] = array(cot_url('admin', 'm=files&s=allpfs'), \Cot::$L['files_allpfs']);
        $adminhelp = \Cot::$L['adm_help_allpfs'] ?? '';
        $adminsubtitle = \Cot::$L['files_allpfs'] ?? '';

        $urlParams = array('m'=>'files', 'a'=> 'allpfs');
        $perPage = \Cot::$cfg['maxrowsperpage'];
        list($pg, $d, $durl) = cot_import_pagenav('d', $perPage);

        /* === Hook === */
        foreach (cot_getextplugins('admin.files.allpfs.first') as $pl)
        {
            include $pl;
        }
        /* ===== */

        $totalitems = \Cot::$db->query("SELECT COUNT(DISTINCT user_id) FROM $db_files
            WHERE source = 'pfs'")->fetchColumn();

        $pagenav = cot_pagenav('admin', $urlParams, $d, $totalitems, $perPage, 'd', '', \Cot::$cfg['jquery'] && \Cot::$cfg['turnajax']);

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

        $sql_pfs = \Cot::$db->query("SELECT DISTINCT f.user_id as uid, u.*, COUNT(*) as count FROM $db_files AS f
	        LEFT JOIN $db_users AS u ON f.user_id=u.user_id
	        WHERE source = 'pfs' GROUP BY f.user_id ORDER BY $sqlOrder LIMIT $d, ".$perPage);

        $t = new \XTemplate(cot_tplfile('files.admin.allpfs'));

        $ii = 0;
        /* === Hook - Part1 : Set === */
        $extp = cot_getextplugins('admin.files.allpfs.loop');
        /* ===== */
        while ($row = $sql_pfs->fetch()){

            $t->assign(cot_generate_usertags($row, 'ALLPFS_ROW_USER_'));

            $t->assign(array(
                'ALLPFS_ROW_URL' => cot_url('files', array('m'=>'pfs', 'uid'=>$row['user_id'])),
                // @deprecated use ...USER_FULL_NAME
                'ALLPFS_ROW_USER_DISPLAY_NAME' => cot_user_full_name($row),
                'ALLPFS_ROW_USER_FULL_NAME' => cot_user_full_name($row),
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
        if ($d == 0) {
            $t->assign([
                'SFS_COUNT' => File::count([['source', 'sfs']]),
            ]);
            $t->parse('MAIN.SFS');
        }

        $t->assign(array(
            'ALLPFS_PAGINATION_PREV' => $pagenav['prev'],
            'ALLPFS_PAGNAV' => $pagenav['main'],
            'ALLPFS_PAGINATION_NEXT' => $pagenav['next'],
            'ALLPFS_TOTALITEMS' => $totalitems,
            'ALLPFS_ON_PAGE' => $ii,
            'PAGE_TITLE' => \Cot::$L['files_allpfs'],
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
    public function cleanupAction()
    {
        // For include files
        global $cfg, $L, $R;

        $filesTable = File::tableName();
        $trashTable = '';
        if (cot_plugin_active('trashcan')) {
            require_once cot_incfile('trashcan', 'plug');
            $trashTable = \Cot::$db->trash;
        }

        $count = 0;

        if (cot_module_active('forums')) {
            // Remove unused forum attachments
            require_once cot_incfile('forums', 'module');

            $postsTable = \Cot::$db->forum_posts;

            $join = '';
            $where = '';
            if (cot_plugin_active('trashcan')) {
                // If the post is deleted to the trash, we do not delete its files
                $join = "LEFT JOIN $trashTable ON {$trashTable}.tr_itemid = {$filesTable}.source_id AND " .
                    "{$trashTable}.tr_type = 'forumpost'";

                $where = " AND {$trashTable}.tr_id IS NULL";
            }

            $query = "SELECT id FROM $filesTable " .
                "LEFT JOIN $postsTable ON {$filesTable}.source_id = {$postsTable}.fp_id $join " .
                "WHERE {$filesTable}.source = 'forums' AND {$postsTable}.fp_id IS NULL $where";

            $files = File::findByCondition("{$filesTable}.id IN ({$query})");

            if ($files) {
                foreach($files as $fileRow) {
                    $count++;
                    $fileRow->delete();
                }
            }
        }

        if (cot_module_active('page')) {
            // Remove unused page attachments
            require_once cot_incfile('page', 'module');

            $pageTable = \Cot::$db->pages;

            $join = '';
            $where = '';
            if (cot_plugin_active('trashcan')) {
                // If the page is deleted to the trash, we do not delete its files
                $join = "LEFT JOIN $trashTable ON {$trashTable}.tr_itemid = {$filesTable}.source_id AND {$trashTable}.tr_type = 'page'";
                $where = " AND {$trashTable}.tr_id IS NULL";
            }

            $query = "SELECT id FROM $filesTable " .
                "LEFT JOIN $pageTable ON {$filesTable}.source_id = {$pageTable}.page_id $join " .
                "WHERE {$filesTable}.source = 'page' AND {$pageTable}.page_id IS NULL $where";

            $files = File::findByCondition("{$filesTable}.id IN ({$query})");

            if ($files) {
                foreach($files as $fileRow) {
                    $count++;
                    $fileRow->delete();
                }
            }
        }

        $count += cot_files_formGarbageCollect();

        cot_message(\Cot::$L['files_items_removed'].': ' . $count);

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', 'm=files', '', true));
    }

    public function delAllThumbsAction()
    {
        if (empty(\Cot::$cfg['files']['folder']) || !file_exists(\Cot::$cfg['files']['folder'] . '/_thumbs')) {
            cot_redirect(cot_url('admin', 'm=files', '', true));
        }

        rrmdir(\Cot::$cfg['files']['folder'].'/_thumbs');

        // Очистим кеш, чтобы миниатюры могли перегенерироваться
        if (\Cot::$cache){
            if (\Cot::$cfg['cache_page']){
                \Cot::$cache->page->clear('page');
            }
            if (\Cot::$cfg['cache_index']){
                \Cot::$cache->page->clear('index');
            }
            if (\Cot::$cfg['cache_forums']){
                \Cot::$cache->page->clear('forums');
            }
        }

        cot_message(\Cot::$L['files_thumbs_removed']);

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', 'm=files', '', true));
    }
}