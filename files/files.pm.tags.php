<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=pm.reply.tags,pm.send.tags
Tags=pm.message.tpl:{PM_FORM_PFS},{PM_FORM_SFS};pm.send.tpl:{PMSEND_FORM_PFS},{PMSEND_FORM_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS link for private messages
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('files', 'module');

$pfs_tag = cot_get_caller() == 'pm.send' ? 'PMSEND_FORM' : 'PM_FORM';

$t->assign(array(
    $pfs_tag.'_PFS' => cot_files_buildPfs(cot::$usr['id'], 'newlink', 'newpmtext',cot::$L['Mypfs']),
    $pfs_tag.'_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, 'newlink', 'newpmtext', cot::$L['SFS']) : '',
));