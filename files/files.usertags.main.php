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
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');

//$user_data['user_avatar'];
$temp_array['AVATAR'] = cot_rc('files_user_default_avatar');
$temp_array['AVATAR_ID'] = 0;
$temp_array['AVATAR_URL'] = '';
$temp_array['AVATAR_RAW'] = null;

if($user_data['user_id'] > 0 && $user_data['user_avatar'] > 0){
    if(!isset($user_data['user_avatar_file'])){
        $user_data['user_avatar_file'] = files_model_File::getById($user_data['user_avatar']);
    }
    if($user_data['user_avatar_file']){
        $temp_array['AVATAR'] = cot_files_user_avatar($user_data['user_avatar'], $user_data);
        $temp_array['AVATAR_ID'] = $user_data['user_avatar'];
        $temp_array['AVATAR_URL'] = cot_files_user_avatar_url($user_data['user_avatar_file']);
        $temp_array['AVATAR_RAW'] = files_model_File::getById($user_data['user_avatar_file']);
    }
}