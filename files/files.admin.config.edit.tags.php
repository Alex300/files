<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.config.edit.tags
Tags=
[END_COT_EXT]
==================== */

/**
 * Module config
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 * @copyright (c) Lily Software https://lily-software.com
 */
(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

/**
 * @var string $o  
 * @var string $p Extension code
 * @var array $cot_modules
 */

if ($o !== 'module' && $p !== 'files') {
    return;
}
$nc = $cot_modules['files']['version'];

Resources::linkFileFooter(Cot::$cfg['modules_dir'] . '/files/js/config.js?nc=' . $nc);


