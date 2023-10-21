-- Main files table
CREATE TABLE IF NOT EXISTS `cot_files` (
	`id` INT UNSIGNED NOT NULL auto_increment,
	`user_id` INT UNSIGNED NOT NULL,
	`source` VARCHAR(128) NOT NULL,
	`source_id` INT UNSIGNED NOT NULL,
    `source_field` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Source item field',
    `path` VARCHAR(255) NOT NULL DEFAULT '',
    `file_name` VARCHAR(255) NOT NULL DEFAULT '',
	`original_name` VARCHAR(255) NOT NULL DEFAULT '',
    `ext` VARCHAR(16) NOT NULL DEFAULT '',
    `mime_type` VARCHAR(255) NOT NULL DEFAULT '',
    `is_img` TINYINT NOT NULL DEFAULT 0,
    `size` INT UNSIGNED NOT NULL DEFAULT 0,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `filesystem_name` VARCHAR(128) NOT NULL DEFAULT 'local',
    `downloads_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `unikey` VARCHAR(255) NOT NULL DEFAULT '',
    `created` DATETIME DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated` DATETIME DEFAULT NULL,
    `updated_by` INT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY(`id`),
	KEY `cot_files_source_idx` (`source`, `source_id`),
	KEY `cot_files_source_2_idx` (`source`, `source_id`, `source_field`),
    KEY `cot_files_source_is_img_idx` (`source`, `source_id`, `is_img`),
	KEY `cot_files_source_2_is_img_idx` (`source`, `source_id`, `source_field`, `is_img`),
	KEY `cot_files_img_idx` (`is_img`)
);

-- Folders
CREATE TABLE IF NOT EXISTS `cot_files_folders` (
  `ff_id` INT UNSIGNED NOT NULL auto_increment,
  `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `ff_title` VARCHAR(255) NOT NULL DEFAULT '',
  `ff_desc` VARCHAR (255) DEFAULT '',
  `ff_public` TINYINT DEFAULT '0',
  `ff_album` TINYINT DEFAULT '0',
  `ff_count` INT DEFAULT '0',
  `ff_created` datetime DEFAULT NULL,
  `ff_updated` datetime DEFAULT NULL,
  PRIMARY KEY  (`ff_id`),
  KEY `pff_userid` (`user_id`)
);
