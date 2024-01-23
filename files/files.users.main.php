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
 * @copyright Copyright (c) Cotonti Team
 * @license BSD
 *
 * @todo заменить $sqlusers на $users после выхода Cotonti 0.9.24
 */

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL');

if (!empty($sqlusers)) {
    $filesIds = [];
    foreach ($sqlusers as $key => $urr) {
        $sqlusers[$key]['user_avatar_file'] = false;
        if ($urr['user_avatar'] > 0) {
            $filesIds[] = $urr['user_avatar'];
        }
    }
    reset($sqlusers);

    if (!empty($filesIds)) {
        $tmp = File::findByCondition([['id', $filesIds]]);
        $files = [];
        if ($tmp) {
            foreach($tmp as $fileRow) {
                $files[$fileRow->id] = $fileRow;
            }

            foreach ($sqlusers as $key => $urr){
                if ($urr['user_avatar'] > 0 && !empty($files[$urr['user_avatar']])) {
                    $sqlusers[$key]['user_avatar_file'] = $files[$urr['user_avatar']];
                }
            }
            reset($sqlusers);
        }
    }
}