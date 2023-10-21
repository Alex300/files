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
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */
defined('COT_CODE') or die('Wrong URL');

/**
 * @var ?array $urr
 * @var XTemplate $t
 */

if (cot_get_caller() == 'users.profile') {
    $avatarTagPrefix = 'USERS_PROFILE_';
    $uid = null;
} else {
    $avatarTagPrefix = 'USERS_EDIT_';
    $uid = $urr['user_id'];
}
$t->assign([
    $avatarTagPrefix . 'AVATAR' => cot_filesAvatarBox($uid),
]);