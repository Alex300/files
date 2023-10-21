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

use cot\modules\files\services\FileService;

 /**
 * @var int $id
 */

defined('COT_CODE') or die('Wrong URL');

if (cot_auth('files', 'a', 'W')) {
    if ($id) {
        FileService::linkFiles('page', $id);
    }
}
