<?php

namespace cot\modules\files\controllers;

use Cot;
use cot\modules\files\models\File;
use cot\modules\files\services\FileService;
use cot\modules\files\services\ThumbnailService;
use image\Image;
use Throwable;
use XTemplate;

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
        global $adminPath, $adminHelp, $cot_yesno, $adminSubtitle;

        $tpl = new XTemplate(cot_tplfile('files.admin.main'));

        $imagickIsAvailable = (new \image\imagick\Image())->isAvailable();
        $gdIsAvailable = (new \image\gd\Image())->isAvailable();
        $currentDriver = Image::currentDriver();

        if (!$imagickIsAvailable && !$gdIsAvailable) {
            cot_message(Cot::$L['files_err_no_driver'], 'warning');
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

        $pfsUrl = cot_url('files', ['m' => 'pfs', 'uid' => Cot::$usr['id']]);
        $filesUrl = cot_url('files', ['uid' => Cot::$usr['id']]);
        $albumUrl = cot_url('files', ['a' => 'album', 'uid' => Cot::$usr['id']]);
        $adminHelp  = Cot::$L['files_userfilespace'] . ": <a href=\"{$pfsUrl}\" target=\"_blank\">{$pfsUrl}</a> (".
            Cot::$L['files_userfilespace_desc'].")<br />";
        $adminHelp .= Cot::$L['files_userpublic_files'] . ": <a href=\"{$filesUrl}\" target=\"_blank\">{$filesUrl}</a><br />";
        $adminHelp .= Cot::$L['files_userpublic_albums'] . ": <a href=\"{$albumUrl}\" target=\"_blank\">{$albumUrl}</a><br /><br />";

        $adminHelp .= "<strong>«". Cot::$L['files_cleanup'] . "»</strong> " . Cot::$L['files_cleanup_desc'].".<br />";
        $adminHelp .= Cot::$L['files_deleteallthumbs_desc'];

        $adminSubtitle = Cot::$L['Files'];

        $tpl->assign(array(
            'PAGE_TITLE' => Cot::$L['Files'] . ": " . Cot::$L['Administration'],
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
        global $adminPath, $adminHelp, $adminSubtitle, $db_files, $db_users, $cot_extrafields;

        $adminPath[] = array(cot_url('admin', 'm=files&s=allpfs'), Cot::$L['files_allpfs']);
        $adminHelp = Cot::$L['adm_help_allpfs'] ?? '';
        $adminSubtitle = Cot::$L['files_allpfs'] ?? '';

        $urlParams = array('m'=>'files', 'a'=> 'allpfs');
        $perPage = Cot::$cfg['maxrowsperpage'];
        [$pg, $d, $durl] = cot_import_pagenav('d', $perPage);

        /* === Hook === */
        foreach (cot_getextplugins('admin.files.allpfs.first') as $pl)
        {
            include $pl;
        }
        /* ===== */

        $totalitems = Cot::$db->query("SELECT COUNT(DISTINCT user_id) FROM $db_files
            WHERE source = 'pfs'")->fetchColumn();

        $pagenav = cot_pagenav('admin', $urlParams, $d, $totalitems, $perPage, 'd', '', Cot::$cfg['jquery'] && Cot::$cfg['turnajax']);

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
            if (isset($cot_extrafields[$db_users]['last_name'])) {
                $sqlOrder .= 'u.user_last_name ASC';
            }
            if (isset($cot_extrafields[$db_users]['first_name'])) {
                if ($sqlOrder != '') {
                    $sqlOrder .= ', ';
                }
                $sqlOrder .= ' u.user_first_name ASC';
            }
            if (isset($cot_extrafields[$db_users]['middle_name'])){
                if ($sqlOrder != '') {
                    $sqlOrder .= ', ';
                }
                $sqlOrder .= ' u.user_middle_name ASC';
            }

        }
        // /Если есть экстраполя ФИО, то сортировать по ним

        $sql_pfs = Cot::$db->query("SELECT DISTINCT f.user_id as uid, u.*, COUNT(*) as count FROM $db_files AS f
	        LEFT JOIN $db_users AS u ON f.user_id=u.user_id
	        WHERE source = 'pfs' GROUP BY f.user_id ORDER BY $sqlOrder LIMIT $d, ".$perPage);

        $t = new XTemplate(cot_tplfile('files.admin.allpfs'));

        $ii = 0;
        /* === Hook - Part1 : Set === */
        $extp = cot_getextplugins('admin.files.allpfs.loop');
        /* ===== */
        while ($row = $sql_pfs->fetch()) {
            $t->assign(cot_generate_usertags($row, 'ALLPFS_ROW_USER_'));

            $t->assign([
                'ALLPFS_ROW_URL' => cot_url('files', ['m' => 'pfs', 'uid' => $row['user_id']]),
                'ALLPFS_ROW_COUNT' => $row['count'],
            ]);

            /* === Hook - Part2 : Include === */
            foreach ($extp as $pl) {
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

        $t->assign([
            'ALLPFS_PAGINATION_PREV' => $pagenav['prev'],
            'ALLPFS_PAGNAV' => $pagenav['main'],
            'ALLPFS_PAGINATION_NEXT' => $pagenav['next'],
            'ALLPFS_TOTALITEMS' => $totalitems,
            'ALLPFS_ON_PAGE' => $ii,
            'PAGE_TITLE' => Cot::$L['files_allpfs'],
        ]);

        /* === Hook  === */
        foreach (cot_getextplugins('admin.files.allpfs.tags') as $pl) {
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
            $trashTable = Cot::$db->trash;
        }

        $count = 0;

        if (cot_module_active('forums')) {
            // Remove unused forum attachments
            require_once cot_incfile('forums', 'module');

            $postsTable = Cot::$db->forum_posts;

            $join = '';
            $where = '';
            if (cot_plugin_active('trashcan')) {
                // If the post is deleted to the trash, we do not delete its files
                $join = "LEFT JOIN $trashTable ON {$trashTable}.tr_itemid = {$filesTable}.source_id AND " . "{$trashTable}.tr_type = 'forumpost'";
                $where = " AND {$trashTable}.tr_id IS NULL";
            }

            $query = "SELECT id FROM $filesTable " .
                "LEFT JOIN $postsTable ON {$filesTable}.source_id = {$postsTable}.fp_id $join " .
                "WHERE {$filesTable}.source = 'forums' AND {$postsTable}.fp_id IS NULL $where";

            $files = File::findByCondition("{$filesTable}.id IN ({$query})");

            if ($files) {
                foreach ($files as $fileRow) {
                    $count++;
                    $fileRow->delete();
                }
            }
        }

        if (cot_module_active('page')) {
            // Remove unused page attachments
            require_once cot_incfile('page', 'module');

            $pageTable = Cot::$db->pages;

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
                foreach ($files as $fileRow) {
                    $count++;
                    $fileRow->delete();
                }
            }
        }

        $condition = [
            ['file_name', ''],
            ['file_name', null, null, 'OR'],
        ];

        // Delete  all records from DB with empty file name
        $files = File::findByCondition($condition);
        if ($files) {
            foreach ($files as $fileRow) {
                $fileRow->delete();
                $count++;
            }
        }

        $count += FileService::formGarbageCollect();

        cot_message(Cot::$L['files_items_removed'].': ' . $count);

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', 'm=files', '', true));
    }

    public function delAllThumbsAction()
    {
        $thumbnailDirectory = ThumbnailService::thumbnailDirectory(true);
        $fileSystem = FileService::getFilesystemByName('local');

        $result = 0;
        // Проверяем существование папки с миниатюрами только для того, чтобы потом не сбрасывать кеш без необходимости
        if ($fileSystem->directoryExists($thumbnailDirectory)) {
            $result++;
            $fileSystem->deleteDirectory($thumbnailDirectory);
        }

        if (!empty(Cot::$cfg['files']['storages'])) {
            foreach (array_keys(Cot::$cfg['files']['storages']) as $fileSystemNames) {
                try {
                    $fileSystem = FileService::getFilesystemByName($fileSystemNames);
                } catch (Throwable $e) {
                    continue;
                }
                if ($fileSystem->directoryExists($thumbnailDirectory)) {
                    $result++;
                    $fileSystem->deleteDirectory($thumbnailDirectory);
                }
            }
        }


        if ($result > 0) {
            // Let's clear the cache so the thumbnails can be regenerated
            if (Cot::$cache) {
                if (Cot::$cfg['cache_page']) {
                    Cot::$cache->static->clear('page');
                }
                if (Cot::$cfg['cache_index']) {
                    Cot::$cache->static->clear('index');
                }
                if (Cot::$cfg['cache_forums']) {
                    Cot::$cache->static->clear('forums');
                }
            }

            cot_message(Cot::$L['files_thumbs_removed']);
        }

        // Return to the main page and show messages
        cot_redirect(cot_url('admin', ['m' => 'files'], '', true));
    }
}