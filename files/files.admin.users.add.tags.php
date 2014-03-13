<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.users.add.tags
Tags=admin.users.tpl:{ADMIN_USERS_NGRP_PFS_MAXFILE},{ADMIN_USERS_NGRP_PFS_MAXTOTAL},{ADMIN_USERS_NGRP_ATTACH_PER_POST}
[END_COT_EXT]
==================== */

/**
 * Users admin add tags
 *
 * @package files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2011-2014
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

$pfs_is_active = true;

$t->assign(array(
	'ADMIN_USERS_NGRP_PFS_MAXFILE' => cot_inputbox('text', 'rmaxfile', '', 'size="16" maxlength="16"'),
	'ADMIN_USERS_NGRP_PFS_MAXTOTAL' => cot_inputbox('text', 'rmaxtotal', '', 'size="16" maxlength="16"'),
    'ADMIN_USERS_NGRP_ATTACH_PER_POST' => cot_inputbox('text', 'rfiles_perpost', '', 'size="16" maxlength="16"'),
));
