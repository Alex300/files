<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=rc
[END_COT_EXT]
==================== */

declare(strict_types=1);

/**
 * Files global assets loader
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru> https://github.com/Alex300
 */

use cot\modules\files\inc\FilesAssets;

defined('COT_CODE') or die('Wrong URL');

if (Cot::$cfg['files']['loadAssetsGlobally']) {
    FilesAssets::getInstance()->load();
}
