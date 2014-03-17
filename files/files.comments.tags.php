<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=comments.newcomment.tags,comments.edit.tags
Tags=comments.tpl:{COMMENTS_FORM_PFS},{COMMENTS_FORM_SFS}
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

    if (cot_get_caller() == 'comments.functions')
    {
        $form_name = 'newcomment';
        $input_name = 'rtext';
    }
    else
    {
        $form_name = 'comments';
        $input_name = 'comtext';
    }

    $t->assign(array(
        'COMMENTS_FORM_PFS' => cot_files_buildPfs(cot::$usr['id'], $form_name, $input_name, cot::$L['Mypfs'], cot::$sys['parser']),
        'COMMENTS_FORM_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, $form_name, $input_name,
                    cot::$L['SFS'], cot::$sys['parser']) : ''
    ));
}