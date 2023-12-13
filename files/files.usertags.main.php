<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=usertags.main
[END_COT_EXT]
==================== */

/**
 * Avatar for users
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL');

$temp_array['AVATAR'] = cot_rc('files_user_default_avatar');
$temp_array['AVATAR_ID'] = 0;
$temp_array['AVATAR_URL'] = '';
$temp_array['AVATAR_RAW'] = null;

if (is_array($user_data) && !empty($user_data['user_id']) && !empty($user_data['user_avatar'])) {
    if (!isset($user_data['user_avatar_file'])) {
        $user_data['user_avatar_file'] = '';
        if ($user_data['user_avatar'] > 0) {
            $user_data['user_avatar_file'] = File::getById($user_data['user_avatar']);
            if (empty($user_data['user_avatar_file'])) {
                $user_data['user_avatar_file'] = '';
            }
        }
    }
    if ($user_data['user_avatar_file']) {
        $temp_array['AVATAR'] = cot_filesUserAvatar($user_data['user_avatar'], $user_data);
        $temp_array['AVATAR_ID'] = $user_data['user_avatar'];
        $temp_array['AVATAR_URL'] = cot_filesUserAvatarUrl($user_data['user_avatar_file']);
        $temp_array['AVATAR_RAW'] = $user_data['user_avatar_file'];
    }
}