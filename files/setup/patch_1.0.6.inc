<?php
/**
 * Update to 1.0.6
 */

global $db_x, $db_files;

if (empty($db_files)) {
    $db_files = $db_x . 'files';
}
if (empty($db_files_folders)) {
    $db_files_folders = $db_x . 'files_folders';
}

Cot::$db->query("UPDATE `{$db_files}` 
SET `file_path` = REPLACE(`file_path`, '".Cot::$cfg['files']['folder']."/', '')");

Cot::$db->query("ALTER TABLE `{$db_files}` 
    MODIFY `file_id` INT UNSIGNED NOT NULL auto_increment,
    MODIFY `user_id` INT UNSIGNED NOT NULL,
    MODIFY `file_item` INT UNSIGNED NOT NULL,
    MODIFY `file_img` TINYINT DEFAULT 0,
    MODIFY `file_size` INT UNSIGNED DEFAULT 0,
    MODIFY `file_count` INT UNSIGNED DEFAULT 0,
    MODIFY `file_order` SMALLINT UNSIGNED DEFAULT 0,
    ENGINE=InnoDB;");

Cot::$db->query("ALTER TABLE `{$db_files_folders}` 
    MODIFY `ff_id` INT UNSIGNED NOT NULL auto_increment,
    MODIFY `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
    MODIFY `ff_public` TINYINT DEFAULT '0',
    MODIFY `ff_album` TINYINT DEFAULT '0',
    MODIFY `ff_count` INT DEFAULT '0',
    ENGINE=InnoDB;");

if (Cot::$db->indexExists($db_files, 'file_source_2')) {
    Cot::$db->query("ALTER TABLE `{$db_files}` DROP INDEX `file_source_2`;");
}

if (!Cot::$db->indexExists($db_files, 'cot_files_source_2_idx')) {
    Cot::$db->query(
        "ALTER TABLE `{$db_files}` ADD INDEX `cot_files_source_2_idx` (`file_source`, `file_item`, `file_field`);"
    );
}

