<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.edit.update.delete
[END_COT_EXT]
==================== */
/**
 * module Files for Cotonti Siena
 *
 * @package Files
 * @author Cotonti Team
 * @copyright (c) Cotonti Team
 */
defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('advert', 'module');

// Удалить все файлы PFS пользователя
$condition = array(
    array('file_source', 'pfs'),
    array('user_id', $id)
);
$items = files_model_File::find($condition);
if(!empty($items)) {
    foreach($items as $itemRow) {
        $itemRow->delete();
        unset($itemRow);
    }
    unset($items);
}

// Удалить все папки PFS пользователя
$condition = array(
    array('user_id', $id)
);
$items = files_model_Folder::find($condition);
if(!empty($items)) {
    foreach($items as $itemRow) {
        $itemRow->delete();
        unset($itemRow);
    }
    unset($items);
}