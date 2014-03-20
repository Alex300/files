<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.main
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

if(!empty($sqlusers)){
    $filesIds = array();
    foreach ($sqlusers as $key => $urr){
        $sqlusers[$key]['user_avatar_file'] = false;
        if($urr['user_avatar'] > 0){
            $filesIds[] = ($urr['user_avatar']);
        }
    }
    reset($sqlusers);

    if(!empty($filesIds)){
        $tmp = files_model_File::find(array(array('file_id', $filesIds)));
        $files = array();
        if($tmp){
            foreach($tmp as $fileRow){
                $files[$fileRow->file_id] = $fileRow;
            }

            foreach ($sqlusers as $key => $urr){
                if($urr['user_avatar'] > 0 && !empty($files[$urr['user_avatar']])){
                    $sqlusers[$key]['user_avatar_file'] = $files[$urr['user_avatar']];
                }
            }
            reset($sqlusers);
        }
    }
}