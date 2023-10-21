<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.edit.delete.done
[END_COT_EXT]
==================== */

/**
 * Delete attached files on page deletes
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */

use cot\modules\files\models\File;

/**
 * @var int $id Page id for delete
 */

defined('COT_CODE') or die('Wrong URL');

// If the page is deleted to the trash, we do not delete files
if (!cot_plugin_active('trashcan') || !Cot::$cfg['plugin']['trashcan']['trash_page']) {
    require_once cot_incfile('files', 'module');

    $files = File::findByCondition([['source', 'page'], ['source_id', $id],]);
    if ($files) {
        foreach ($files as $fileRow) {
            $fileRow->delete();
        }
    }
}
