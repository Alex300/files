<?php
/**
 * Installation handler
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2011-2014
 * @license BSD License
 */

defined('COT_CODE') or die('Wrong URL');

global $db_groups, $db_users;

// Add groups fields if missing
$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_pfs_maxfile'");
if ($dbres->rowCount() == 0)
{
	$db->query("ALTER TABLE `$db_groups` ADD COLUMN `grp_pfs_maxfile` INT NOT NULL DEFAULT 0");
}
$dbres->closeCursor();

$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_pfs_maxtotal'");
if ($dbres->rowCount() == 0)
{
	$db->query("ALTER TABLE `$db_groups` ADD COLUMN `grp_pfs_maxtotal` INT NOT NULL DEFAULT 0");
}
$dbres->closeCursor();


$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_files_perpost'");
if ($dbres->rowCount() == 0)
{
    $db->query("ALTER TABLE `$db_groups` ADD COLUMN `grp_files_perpost` INT NOT NULL DEFAULT 0");
}
$dbres->closeCursor();

$dbres = $db->query("SHOW COLUMNS FROM `$db_users` WHERE `Field` = 'user_avatar'");
if ($dbres->rowCount() == 0)
{
    $db->query("ALTER TABLE `$db_users` ADD COLUMN `user_avatar` INT DEFAULT 0");
}
$dbres->closeCursor();