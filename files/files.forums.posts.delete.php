<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.posts.delete.first
[END_COT_EXT]
==================== */

/**
 * Delete attached files on forum post deletes
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */
defined('COT_CODE') or die('Wrong URL');

// If the post is deleted to the trash, we do not delete files
if (!cot_plugin_active('trashcan') || !cot::$cfg['plugin']['trashcan']['trash_forum']) {
	require_once cot_incfile('files', 'module');

    $files = files_model_File::findByCondition([['file_source', 'forums'], ['file_item', $p],]);
    if ($files) {
        foreach ($files as $fileRow) {
            $fileRow->delete();
        }
    }
}
