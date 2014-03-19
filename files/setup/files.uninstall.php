<?php
/**
 * Uninstallation handler
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2011-2014
 * @license BSD License
 */

defined('COT_CODE') or die('Wrong URL');

global $db_groups, $db_users;

// Remove Files columns from groups table
$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_pfs_maxfile'");
if ($dbres->rowCount() == 1)
{
	$db->query("ALTER TABLE `$db_groups` DROP COLUMN `grp_pfs_maxfile`");
}
$dbres->closeCursor();

$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_pfs_maxtotal'");
if ($dbres->rowCount() == 1)
{
	$db->query("ALTER TABLE `$db_groups` DROP COLUMN `grp_pfs_maxtotal`");
}
$dbres->closeCursor();


$dbres = $db->query("SHOW COLUMNS FROM `$db_groups` WHERE `Field` = 'grp_files_perpost'");
if ($dbres->rowCount() == 1)
{
    $db->query("ALTER TABLE `$db_groups` DROP COLUMN `grp_files_perpost`");
}
$dbres->closeCursor();

$dbres = $db->query("SHOW COLUMNS FROM `$db_users` WHERE `Field` = 'user_avatar'");
if ($dbres->rowCount() == 1)
{
    $db->query("ALTER TABLE `$db_users` DROP COLUMN `user_avatar`");
}
$dbres->closeCursor();


// todo рекурсивное удаление всех файлов в папке хранения