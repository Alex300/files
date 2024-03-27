<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=comments.newcomment.tags,comments.edit.tags
Tags=comments.tpl:{COMMENTS_FORM_PFS},{COMMENTS_FORM_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS link for comments
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 * @copyright (c) Lily Software https://lily-software.com
 *
 * @var XTemplate $t
 */
defined('COT_CODE') or die('Wrong URL.');

if (cot_auth('files', 'a', 'W')) {
    require_once cot_incfile('files', 'module');

    if (cot_get_caller() === 'comments.functions') {
        $formName = 'newcomment';
        $inputName = 'rtext';
    } else {
        $formName = 'comments';
        $inputName = 'comtext';
    }

    $t->assign([
        'COMMENTS_FORM_PFS' => cot_filesBuildPfs(Cot::$usr['id'], $formName, $inputName, Cot::$L['Mypfs'], Cot::$sys['parser']),
        'COMMENTS_FORM_SFS' => (cot_auth('files', 'a', 'A'))
            ? cot_filesBuildPfs(0, $formName, $inputName, Cot::$L['SFS'], Cot::$sys['parser'])
            : '',
    ]);
}