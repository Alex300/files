<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=header.tags,header.user.tags
Tags=header.tpl:{HEADER_USER_PFS},{HEADER_USER_PFS_URL}
[END_COT_EXT]
==================== */

/**
 * PFS header link
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

if ($usr['id'] > 0 && $cot_groups[$usr['maingrp']]['pfs_maxtotal'] >= 0 && $cot_groups[$usr['maingrp']]['pfs_maxfile'] >= 0)
{
	$pfs_url = cot_url('files', array('m'=>'pfs'));
	$out['pfs'] = cot_rc_link($pfs_url, $L['Mypfs']);
	$t->assign(array(
		'HEADER_USER_PFS' => $out['pfs'],
		'HEADER_USER_PFS_URL' => $pfs_url
	));
}
