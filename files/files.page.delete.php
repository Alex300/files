<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.edit.delete.done
[END_COT_EXT]
==================== */

/**
 * Delete attached files on forum post deletes
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL');

if (cot_auth('files', 'a', 'W')){

    require_once cot_incfile('files', 'module');

    $filesCond = array(
        array('file_source', 'page'),
        array('file_item', $id),
    );
    $files = files_model_File::find($filesCond);
    if($files){
        foreach($files as $fileRow) $fileRow->delete();
    }
}
