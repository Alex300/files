<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.extrafields.first
[END_COT_EXT]
==================== */

/**
 * module Files for Cotonti Siena
 *
 * @package Files
 * @author Cotonti Team
 * @copyright (c) Cotonti Team
 */
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('files', 'module');

$extra_whitelist[$db_files] = array(
	'name' => $db_files,
	'caption' => cot::$L['Module'].' Files',
	'type' => 'module',
	'code' => 'files',
    'help' => cot::$L['files_extrafields_hint']."<br />".cot::$L['adm_help_info'],
	'tags' => array(
        'files.gallery.tpl' => '{FILES_ROW_XXXXX}, {FILES_ROW_XXXXX_TITLE}',
        'files.downloads.tpl' => '{FILES_ROW_XXXXX}, {FILES_ROW_XXXXX_TITLE}',
	)
);

$extra_whitelist[$db_files_folders] = array(
    'name' => $db_files_folders,
    'caption' => cot::$L['Module'].' Files',
    'type' => 'module',
    'code' => 'files',
    'tags' => array(

    )
);