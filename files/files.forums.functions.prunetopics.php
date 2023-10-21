<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.functions.prunetopics
[END_COT_EXT]
==================== */
/**
 * Delete forum post attached files on topic delete
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL');

// If the topic is deleted to the trash, we do not delete files
if (
    (!cot_plugin_active('trashcan') || !Cot::$cfg['plugin']['trashcan']['trash_forum'])
    && !empty($topicId)
) {
    $filesCond = [
        ['source', 'forums',],
        ['SQL', 'source_id IN (SELECT fp_id FROM ' . Cot::$db->quoteTableName(Cot::$db->forum_posts) .
            " WHERE fp_topicid = $topicId)",],
    ];
    $files = File::findByCondition($filesCond);
    if ($files) {
        foreach ($files as $fileRow) {
            $fileRow->delete();
        }
    }
}
