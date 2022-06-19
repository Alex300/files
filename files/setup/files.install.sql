-- Main files table
CREATE TABLE IF NOT EXISTS `cot_files` (
	`file_id` INT UNSIGNED NOT NULL auto_increment,
	`user_id` INT UNSIGNED NOT NULL,
	`file_source` VARCHAR(64) NOT NULL,
	`file_item` INT UNSIGNED NOT NULL,
	`file_field` varchar(255) DEFAULT '' COMMENT 'Item field',
	`file_path` VARCHAR(255) DEFAULT '',
	`file_name` VARCHAR(255) NOT NULL,
	`file_ext` VARCHAR(16) DEFAULT '',
	`file_img` TINYINT DEFAULT 0,
	`file_size` INT UNSIGNED DEFAULT 0,
	`file_title` VARCHAR(255) DEFAULT '',
	`file_count` INT UNSIGNED DEFAULT 0,
	`file_order` SMALLINT UNSIGNED DEFAULT 0,
	`file_updated` datetime DEFAULT NULL,
	`file_unikey` VARCHAR(255) DEFAULT '',
	PRIMARY KEY(`file_id`),
	KEY `cot_files_source_idx` (`file_source`, `file_item`),
	KEY `cot_files_source_2_idx` (`file_source`, `file_item`, `file_field`),
	KEY `cot_files_img_idx` (`file_img`)
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
