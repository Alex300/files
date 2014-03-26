<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.add.add.done
[END_COT_EXT]
==================== */

/**
 * Link possibly uploaded files to pages
 *
 * @package Files
 * @author Cotonti Team
 */
defined('COT_CODE') or die('Wrong URL');

if (cot_auth('files', 'a', 'W')){

    if($id) cot_files_linkFiles('page', $id);
}
