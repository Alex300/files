<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.add.tags,page.edit.tags
Tags=page.add.tpl:{PAGEADD_FORM_PFS},{PAGEADD_FORM_SFS},{PAGEADD_FORM_URL_PFS},{PAGEADD_FORM_URL_SFS};page.edit.tpl:{PAGEEDIT_FORM_PFS},{PAGEEDIT_FORM_SFS},{PAGEEDIT_FORM_URL_PFS},{PAGEEDIT_FORM_URL_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS link on page.add
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

if(cot_auth('files', 'a', 'W')){

    require_once cot_incfile('files', 'module');

    if (cot_get_caller() == 'page.add')
    {
        $pfs_tag = 'PAGEADD';
    }
    else
    {
        $pfs_tag = 'PAGEEDIT';
    }

    $t->assign(array(
        $pfs_tag . '_FORM_PFS' => cot_files_buildPfs($usr['id'], 'pageform', 'rpagetext',$L['Mypfs'], $sys['parser']),
        $pfs_tag . '_FORM_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, 'pageform', 'rpagetext',
                                $L['SFS'], $sys['parser']) : '',
        $pfs_tag . '_FORM_URL_PFS' => cot_files_buildPfs($usr['id'], 'pageform', 'rpageurl', $L['Mypfs']),
        $pfs_tag . '_FORM_URL_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, 'pageform', 'rpageurl',
                                $L['SFS']) : '',

        // Унифицированные теги
        'PAGE_FORM_PFS' => cot_files_buildPfs($usr['id'], 'pageform', 'rpagetext',$L['Mypfs'], $sys['parser']),
        'PAGE_FORM_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, 'pageform', 'rpagetext', $L['SFS'], $sys['parser']) : '',
        'PAGE_FORM_URL_PFS' => cot_files_buildPfs($usr['id'], 'pageform', 'rpageurl', $L['Mypfs']),
        'PAGE_FORM_URL_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, 'pageform', 'rpageurl',  $L['SFS']) : ''
    ));
}