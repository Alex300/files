<?php
/**
 * Update to 1.0.6
 */

global $db_x, $db_files;

if (empty($db_files)) $db_files = $db_x.'files';
if (empty($db_files_folders)) $db_files_folders = $db_x.'files_folders';

cot::$db->query("UPDATE `{$db_files}` 
SET `file_path` = REPLACE(`file_path`, '".cot::$cfg['files']['folder']."/', '')");

cot::$db->query("ALTER TABLE `{$db_files}` 
    MODIFY `file_id` INT UNSIGNED NOT NULL auto_increment,
    MODIFY `user_id` INT UNSIGNED NOT NULL,
    MODIFY `file_item` INT UNSIGNED NOT NULL,
    MODIFY `file_img` TINYINT DEFAULT 0,
    MODIFY `file_size` INT UNSIGNED DEFAULT 0,
    MODIFY `file_count` INT UNSIGNED DEFAULT 0,
    MODIFY `file_order` SMALLINT UNSIGNED DEFAULT 0,
    ENGINE=InnoDB;");

cot::$db->query("ALTER TABLE `{$db_files_folders}` 
    MODIFY `ff_id` INT UNSIGNED NOT NULL auto_increment,
    MODIFY `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
    MODIFY `ff_public` TINYINT DEFAULT '0',
    MODIFY `ff_album` TINYINT DEFAULT '0',
    MODIFY `ff_count` INT DEFAULT '0',
    ENGINE=InnoDB;");

cot::$db->query("UPDATE `{$db_files}` 
DROP INDEX `file_source_2`, ADD INDEX `cot_files_source_2_idx` (`file_source`, `file_item`, `file_field`);");
