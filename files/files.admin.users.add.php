<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.users.add.first
[END_COT_EXT]
==================== */

/**
 * Users admin edit tags
 *
 * @package files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2011-2014
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

$rgroups['grp_pfs_maxfile'] = (int)cot_import('rmaxfile', 'P', 'INT');
// Ограничения на загрузку файлов через POST
if(cot::$cfg['files']['chunkSize'] == 0){
    $rgroups['grp_pfs_maxfile']  = min($rgroups['grp_pfs_maxfile'], cot_get_uploadmax() * 1024);
}
$rgroups['grp_pfs_maxtotal'] = (int)cot_import('rmaxtotal', 'P', 'INT');
$rgroups['grp_files_perpost'] = (int)cot_import('rfiles_perpost', 'P', 'INT');
