<?php
/**
 * Update to v.1.0.8
 */

global $db_files, $db_files_folders;

if (empty($db_files)) {
    // Registering tables
    cot::$db->registerTable('files');
    cot::$db->registerTable('files_folders');
}

$changeCollation = false;
if (
    cot::$cfg['mysqlcharset'] == 'utf8mb4' &&
    (empty(cot::$cfg['mysqlcollate']) || cot::$cfg['mysqlcollate'] == 'utf8mb4_unicode_ci')
) {
    $changeCollation = true;
}
if ($changeCollation) {
    cot::$db->query('ALTER TABLE ' . cot::$db->quoteTableName(cot::$db->files) . "
    CONVERT TO CHARACTER SET '" . cot::$cfg['mysqlcharset'] . "' COLLATE '" . cot::$cfg['mysqlcollate'] . "', 
    DEFAULT CHARACTER SET='" . cot::$cfg['mysqlcharset'] . "' COLLATE='" . cot::$cfg['mysqlcollate'] . "'");

    cot::$db->query('ALTER TABLE ' . cot::$db->quoteTableName(cot::$db->files_folders) . "
    CONVERT TO CHARACTER SET '" . cot::$cfg['mysqlcharset'] . "' COLLATE '" . cot::$cfg['mysqlcollate'] . "', 
    DEFAULT CHARACTER SET='" . cot::$cfg['mysqlcharset'] . "' COLLATE='" . cot::$cfg['mysqlcollate'] . "'");
}