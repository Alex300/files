<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.edit.update.delete
[END_COT_EXT]
==================== */
/**
 * Delete user's files on user delete
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 *
 * @var int $id User id for delete
 */
defined('COT_CODE') or die('Wrong URL');

// If the user is deleted to the trash, we do not delete his files
if (!cot_plugin_active('trashcan') || !cot::$cfg['plugin']['trashcan']['trash_user']) {

    require_once cot_incfile('files', 'module');

    cot_delete_user_files($id);
}