<?php
/**
 * Update to v.1.0.8
 */

global $db_files, $db_files_folders;

if (empty($db_files)) {
    // Registering tables
    Cot::$db->registerTable('files');
    Cot::$db->registerTable('files_folders');
}

$changeCollation = false;
if (
    Cot::$cfg['mysqlcharset'] == 'utf8mb4' &&
    (empty(Cot::$cfg['mysqlcollate']) || Cot::$cfg['mysqlcollate'] == 'utf8mb4_unicode_ci')
) {
    $changeCollation = true;
}
if ($changeCollation) {
    Cot::$db->query('ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files) . "
    CONVERT TO CHARACTER SET '" . Cot::$cfg['mysqlcharset'] . "' COLLATE '" . Cot::$cfg['mysqlcollate'] . "', 
    DEFAULT CHARACTER SET='" . Cot::$cfg['mysqlcharset'] . "' COLLATE='" . Cot::$cfg['mysqlcollate'] . "'");

    Cot::$db->query('ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files_folders) . "
    CONVERT TO CHARACTER SET '" . Cot::$cfg['mysqlcharset'] . "' COLLATE '" . Cot::$cfg['mysqlcollate'] . "', 
    DEFAULT CHARACTER SET='" . Cot::$cfg['mysqlcharset'] . "' COLLATE='" . Cot::$cfg['mysqlcollate'] . "'");
}