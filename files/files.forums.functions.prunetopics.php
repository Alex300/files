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
defined('COT_CODE') or die('Wrong URL');

// If the topic is deleted to the trash, we do not delete files
if (
    (!cot_plugin_active('trashcan') || !cot::$cfg['plugin']['trashcan']['trash_forum']) &&
    !empty($topicId)
) {
    $filesCond = [
        ['file_source', 'forums',],
        ['SQL', 'file_item IN (SELECT fp_id FROM ' . cot::$db->quoteTableName(cot::$db->forum_posts) .
            " WHERE fp_topicid = $topicId)",],
    ];
    $files = files_model_File::findByCondition($filesCond);
    if ($files) {
        foreach ($files as $fileRow) {
            $fileRow->delete();
        }
    }
}
