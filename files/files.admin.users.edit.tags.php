<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.users.edit.tags
Tags=admin.users.tpl:{ADMIN_USERS_EDITFORM_GRP_PFS_MAXFILE},{ADMIN_USERS_EDITFORM_GRP_PFS_MAXTOTAL},{ADMIN_USERS_EDITFORM_GRP_ATTACH_PER_POST}
[END_COT_EXT]
==================== */

/**
 * Users admin edit tags
 *
 * @package files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2011-2013
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

$pfs_is_active = true;

$t->assign(array(
	'ADMIN_USERS_EDITFORM_GRP_PFS_MAXFILE' => cot_inputbox('text', 'rmaxfile', htmlspecialchars($row['grp_pfs_maxfile']), 'size="16" maxlength="16"'),
	'ADMIN_USERS_EDITFORM_GRP_PFS_MAXTOTAL' => cot_inputbox('text', 'rmaxtotal', htmlspecialchars($row['grp_pfs_maxtotal']), 'size="16" maxlength="16"'),
    'ADMIN_USERS_EDITFORM_GRP_ATTACH_PER_POST' => cot_inputbox('text', 'rfiles_perpost', htmlspecialchars($row['grp_files_perpost']), 'size="16" maxlength="16"'),
));
