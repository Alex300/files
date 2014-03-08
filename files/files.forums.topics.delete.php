<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.topics.rights
[END_COT_EXT]
==================== */

/**
 * Delete forum post attached files on topic delete
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');

if (cot_auth('files', 'a', 'W') && cot::$usr['isadmin'] && !empty($q) && $a == 'delete')
{
	cot_check_xg();
    require_once cot_incfile('files', 'module');

	foreach ($db->query("SELECT fp_id FROM $db_forum_posts WHERE fp_topicid = ?", array($q))->fetchAll(PDO::FETCH_COLUMN)
             as $files_post){

        $filesCond = array(
            array('file_source', 'forums'),
            array('file_item', $files_post),
        );
        $files = files_model_File::find($filesCond);
        if($files){
            foreach($files as $fileRow) $fileRow->delete();
        }
	}
}
