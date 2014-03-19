<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.profile.tags,users.edit.tags
Tags=users.profile.tpl:{USERS_PROFILE_AVATAR};users.edit.tpl:{USERS_EDIT_AVATAR}
[END_COT_EXT]
==================== */

/**
 * Avatar for users
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');

if(cot_get_caller() == 'users.profile'){
    $avatarTagPrefix = 'USERS_PROFILE_';
    $uid = null;
}else{
    $avatarTagPrefix = 'USERS_EDIT_';
    $uid = $urr['user_id'];
}
$t->assign(array(
    $avatarTagPrefix.'AVATAR' => cot_files_avatarbox($uid),
));