<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=pm.reply.tags,pm.send.tags
Tags=pm.message.tpl:{PM_FORM_PFS},{PM_FORM_SFS};pm.send.tpl:{PMSEND_FORM_PFS},{PMSEND_FORM_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS link for private messages
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 * @copyright (c) Lily Software https://lily-software.com
 *
 * @var XTemplate $t
 */
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('files', 'module');

$pfsTagPrefix = cot_get_caller() === 'pm.send' ? 'PMSEND_FORM' : 'PM_FORM';

$t->assign([
    $pfsTagPrefix . '_PFS' => cot_filesBuildPfs(Cot::$usr['id'], 'newlink', 'newpmtext', Cot::$L['Mypfs']),
    $pfsTagPrefix . '_SFS' => cot_auth('files', 'a', 'A')
        ? cot_filesBuildPfs(0, 'newlink', 'newpmtext', Cot::$L['SFS'])
        : '',
]);