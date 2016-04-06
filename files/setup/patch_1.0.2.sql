/* Update to 1.0.2 */
ALTER TABLE `cot_files` CHANGE `file_path` `file_path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '',
CHANGE `file_ext` `file_ext` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '',
CHANGE `file_img` `file_img` TINYINT(1) NULL DEFAULT '0',
CHANGE `file_size` `file_size` INT(11) NULL DEFAULT '0',
CHANGE `file_title` `file_title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '',
CHANGE `file_count` `file_count` INT(11) UNSIGNED NULL DEFAULT '0',
CHANGE `file_unikey` `file_unikey` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '';

ALTER TABLE `cot_files` ADD INDEX `cot_files_img_idx` (`file_img`);
ALTER TABLE `cot_files` DROP INDEX `file_source`, ADD INDEX `cot_files_source` (`file_source`, `file_item`) USING BTREE;
ALTER TABLE `cot_files` DROP INDEX `file_source_2`, ADD INDEX `cot_files_source_2` (`file_source`, `file_item`) USING BTREE;