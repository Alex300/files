-- Main files table
CREATE TABLE IF NOT EXISTS `cot_files` (
	`file_id` INT NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL,
	`file_source` VARCHAR(64) NOT NULL,
	`file_item` INT NOT NULL,
	`file_field` varchar(255) DEFAULT '' COMMENT 'Item field',
	`file_path` VARCHAR(255) NOT NULL,
	`file_name` VARCHAR(255) NOT NULL,
	`file_ext` VARCHAR(16) NOT NULL,
	`file_img` TINYINT NOT NULL DEFAULT 0,
	`file_size` INT NOT NULL,
	`file_title` VARCHAR(255) NOT NULL,
	`file_count` INT NOT NULL DEFAULT 0,
	`file_order` SMALLINT NOT NULL DEFAULT 0,
	`file_updated` datetime DEFAULT NULL,
	`file_unikey` varchar(255)  DEFAULT '',
	PRIMARY KEY(`file_id`),
	KEY `file_source` (`file_source`, `file_item`),
	KEY `file_source_2` (`file_source`, `file_item`, `file_field`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Folders
CREATE TABLE IF NOT EXISTS `cot_files_folders` (
  `ff_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `ff_title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ff_desc` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `ff_public` tinyint(1) NOT NULL default '0',
  `ff_album` tinyint(1) NOT NULL default '0',
  `ff_count` int(11) NOT NULL default '0',
  `ff_created` datetime DEFAULT NULL,
  `ff_updated` datetime DEFAULT NULL,
  PRIMARY KEY  (`ff_id`),
  KEY `pff_userid` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
