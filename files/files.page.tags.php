<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.add.tags,page.edit.tags
Tags=page.add.tpl:{PAGEADD_FORM_PFS},{PAGEADD_FORM_SFS},{PAGEADD_FORM_URL_PFS},{PAGEADD_FORM_URL_SFS};page.edit.tpl:{PAGEEDIT_FORM_PFS},{PAGEEDIT_FORM_SFS},{PAGEEDIT_FORM_URL_PFS},{PAGEEDIT_FORM_URL_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS link for page add/edit
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

    if (cot_get_caller() === 'page.add') {
        $pfsTagPrefix = 'PAGEADD';
    } else {
        $pfsTagPrefix = 'PAGEEDIT';
    }

    // Унифицированные теги
    $tags = [
        'PAGE_FORM_PFS' => cot_filesBuildPfs(Cot::$usr['id'], 'pageform', 'rpagetext', Cot::$L['Mypfs'], Cot::$sys['parser']),
        'PAGE_FORM_SFS' => cot_auth('files', 'a', 'A')
            ? cot_filesBuildPfs(0, 'pageform', 'rpagetext', Cot::$L['SFS'], Cot::$sys['parser'])
            : '',
        'PAGE_FORM_URL_PFS' => cot_filesBuildPfs(Cot::$usr['id'], 'pageform', 'rpageurl', Cot::$L['Mypfs']),
        'PAGE_FORM_URL_SFS' => cot_auth('files', 'a', 'A')
            ? cot_filesBuildPfs(0, 'pageform', 'rpageurl',  Cot::$L['SFS'])
            : ''
    ];

    $t->assign($tags);

    $t->assign([
        $pfsTagPrefix . '_FORM_PFS' => $tags['PAGE_FORM_PFS'],
        $pfsTagPrefix . '_FORM_SFS' => $tags['PAGE_FORM_SFS'],
        $pfsTagPrefix . '_FORM_URL_PFS' => $tags['PAGE_FORM_URL_PFS'],
        $pfsTagPrefix . '_FORM_URL_SFS' => $tags['PAGE_FORM_URL_SFS'],
    ]);
}