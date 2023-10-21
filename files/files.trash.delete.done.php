<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=trash.delete.done
[END_COT_EXT]
==================== */

/**
 * Delete attached files on trash item deletes
 *
 * @package Files
 * @author Cotonti Team
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL');

if (!empty($res) && !empty($res['tr_itemid'])) {
    $deleteFiles = true;
    switch ($res['tr_type']) {
        case 'forumpost':
            $source = 'forum';
            break;

        case 'user':
            cot_delete_user_files($res['tr_itemid']);
            $deleteFiles = false;
            break;

        default:
            $source = $res['tr_type'];
    }

    if ($deleteFiles) {
        require_once cot_incfile('files', 'module');

        $files = File::findByCondition([
            ['source', $source],
            ['source_id', $res['tr_itemid']],
        ]);

        if ($files) {
            foreach ($files as $fileRow) {
                $fileRow->delete();
            }
        }
    }
}