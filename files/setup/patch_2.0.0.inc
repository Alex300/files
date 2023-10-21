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
        . ' DROP INDEX `cot_files_source_idx`, '
        . ' DROP INDEX `cot_files_source_2_idx`, '
        . ' DROP INDEX `cot_files_img_idx`;'
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . ' CHANGE `file_id` `id` INT UNSIGNED NOT NULL auto_increment, '
    . ' CHANGE `file_source` `source` VARCHAR(128) NOT NULL, '
    . ' CHANGE `file_item` `source_id` INT UNSIGNED NOT NULL, '
	. " CHANGE `file_field` `source_field` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Source item field', "
	. " CHANGE `file_path` `path` VARCHAR(255) NOT NULL DEFAULT '', "
	. " CHANGE `file_name` `original_name` VARCHAR(255) NOT NULL DEFAULT '', "
	. " CHANGE `file_ext` `ext` VARCHAR(16) NOT NULL DEFAULT '', "
	. ' CHANGE `file_img` `is_img` TINYINT NOT NULL DEFAULT 0, '
	. ' CHANGE `file_size` `size` INT UNSIGNED NOT NULL DEFAULT 0, '
	. " CHANGE `file_title` `title` VARCHAR(255) NOT NULL DEFAULT '', "
	. ' CHANGE `file_count` `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0, '
	. ' CHANGE `file_order` `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0, '
	. " CHANGE `file_unikey` `unikey` VARCHAR(255) NOT NULL DEFAULT '' AFTER `sort_order`, "
	. ' CHANGE `file_updated` `updated` DATETIME DEFAULT NULL;'
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . " ADD `file_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `path`, "
    . " ADD `mime_type` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ext`, "
    . " ADD `filesystem_name` VARCHAR(128) NOT NULL DEFAULT '' AFTER `title`, "
	. " ADD `created` DATETIME DEFAULT NULL AFTER `unikey`, "
	. ' ADD `created_by` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created`, '
	. ' ADD `updated_by` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`;'
);

Cot::$db->query(
    'ALTER TABLE ' . Cot::$db->quoteTableName(Cot::$db->files)
    . ' ADD INDEX `cot_files_source_idx` (`source`, `source_id`), '
    . ' ADD INDEX `cot_files_source_2_idx` (`source`, `source_id`, `source_field`), '
    . ' ADD INDEX `cot_files_source_is_img_idx` (`source`, `source_id`, `is_img`), '
    . ' ADD INDEX `cot_files_source_2_is_img_idx` (`source`, `source_id`, `source_field`, `is_img`), '
    . ' ADD INDEX `cot_files_img_idx` (`is_img`);'
);

Cot::$db->update(Cot::$db->files, ['filesystem_name' => 'local'], "filesystem_name = '' OR filesystem_name IS NULL");

// Update framing mode in module config: 'crop'->'outbound', 'auto'->'inset'
Cot::$db->update(
    Cot::$db->config,
    ['config_value' => 'outbound'],
    "config_owner = 'module' AND config_cat = 'files' AND config_name = 'thumb_framing' AND config_value = 'crop'"
);
Cot::$db->update(
    Cot::$db->config,
    ['config_value' => 'inset'],
    "config_owner = 'module' AND config_cat = 'files' AND config_name = 'thumb_framing' AND config_value = 'auto'"
);

Cot::$db->update(
    Cot::$db->config,
    ['config_value' => 'outbound'],
    "config_owner = 'module' AND config_cat = 'files' AND config_name = 'avatar_framing' AND config_value = 'crop'"
);
Cot::$db->update(
    Cot::$db->config,
    ['config_value' => 'inset'],
    "config_owner = 'module' AND config_cat = 'files' AND config_name = 'avatar_framing' AND config_value = 'auto'"
);

$thumbnailDirectory = Cot::$cfg['files']['folder'] . '/_thumbs'; //FileService::thumbnailDirectory();

if (file_exists($thumbnailDirectory)) {
    // Remove all thumbnails
    cot_removeDirectoryRecursive($thumbnailDirectory);

    // Let's clear the cache so the thumbnails can be regenerated
    if (Cot::$cache) {
        if (Cot::$cfg['cache_page']) {
            Cot::$cache->static->clear('page');
        }
        if (Cot::$cfg['cache_index']) {
            Cot::$cache->static->clear('index');
        }
        if (Cot::$cfg['cache_forums']) {
            Cot::$cache->static->clear('forums');
        }
    }
}

// split file path to directory name and file name
// Самое тяжелое в конце
$statement = Cot::$db->query('SELECT id, path FROM ' . Cot::$db->quoteTableName(Cot::$db->files));
while ($row = $statement->fetch()) {
    Cot::$db->update(Cot::$db->files, ['file_name' => basename($row['path']), 'path' => dirname($row['path'])], "id = {$row['id']}");
}
$statement->closeCursor();