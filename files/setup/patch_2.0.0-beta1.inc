<?php
/**
 * Update to v.2.0.0
 */

global $db_files, $db_files_folders;

if (empty($db_files)) {
    // Registering tables
    Cot::$db->registerTable('files');
    Cot::$db->registerTable('files_folders');
}

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
        . " DROP INDEX `cot_files_source_idx`,
        DROP INDEX `cot_files_source_2_idx`,
        DROP INDEX `cot_files_img_idx`;"
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . " CHANGE `file_id` `id` INT UNSIGNED NOT NULL auto_increment, 
    CHANGE `file_source` `source` VARCHAR(128) NOT NULL,
    CHANGE `file_item` `source_id` INT UNSIGNED NOT NULL,
	CHANGE `file_field` `source_field` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Source item field',
	CHANGE `file_path` `path` VARCHAR(255) NOT NULL DEFAULT '',
	CHANGE `file_name` `original_name` VARCHAR(255) NOT NULL DEFAULT '',
	CHANGE `file_ext` `ext` VARCHAR(16) NOT NULL DEFAULT '',
	CHANGE `file_img` `is_img` TINYINT NOT NULL DEFAULT 0,
	CHANGE `file_size` `size` INT UNSIGNED NOT NULL DEFAULT 0,
	CHANGE `file_title` `title` VARCHAR(255) NOT NULL DEFAULT '',
	CHANGE `file_count` `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0,
	CHANGE `file_order` `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
	CHANGE `file_unikey` `unikey` VARCHAR(255) NOT NULL DEFAULT '' AFTER `sort_order`,
	CHANGE `file_updated` `updated` DATETIME DEFAULT NULL;"
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . " ADD `file_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `path`,
	ADD `created` DATETIME DEFAULT NULL AFTER `unikey`,
	ADD `created_by` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created`,
	ADD `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`;"
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . " ADD INDEX `cot_files_source_idx` (`source`, `source_id`),
    ADD INDEX `cot_files_source_2_idx` (`source`, `source_id`, `source_field`),
    ADD INDEX `cot_files_img_idx` (`is_img`);"
);


// split fill path to directory name and file name
$statement = Cot::$db->query('SELECT id, path FROM ' . Cot::$db->quoteTableName(Cot::$db->files));
while ($row = $statement->fetch()) {
    Cot::$db->update(Cot::$db->files, ['file_name' => basename($row['path']), 'path' => dirname($row['path'])]);
}
$statement->closeCursor();