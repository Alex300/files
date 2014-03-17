<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.editpost.tags, forums.posts.newpost.tags, forums.newtopic.tags
Tags=forums.editpost.tpl:{FORUMS_EDITPOST_PFS},{FORUMS_EDITPOST_SFS};forums.editpost.tpl:{FORUMS_POSTS_NEWPOST_PFS},{FORUMS_POSTS_NEWPOST_SFS};forums.newtopic.tpl:{FORUMS_NEWTOPIC_PFS},{FORUMS_NEWTOPIC_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS links for forums
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

if(cot_auth('files', 'a', 'W')){
    require_once cot_incfile('files', 'module');

    $pfs_caller = cot_get_caller();
    if ($pfs_caller == 'forums.posts')
    {
        $pfs_src = 'newpost';
        $pfs_name = 'rmsgtext';
        $pfs_tag = 'POSTS_NEWPOST';
    }
    elseif ($pfs_caller == 'forums.newtopic')
    {
        $pfs_src = 'newtopic';
        $pfs_name = 'rmsgtext';
        $pfs_tag = 'NEWTOPIC';
    }
    else
    {
        $pfs_src = 'editpost';
        $pfs_name = 'rmsgtext';
        $pfs_tag = 'EDITPOST';
    }

    $t->assign(array(
        'FORUMS_' . $pfs_tag . '_PFS' => cot_files_buildPfs($usr['id'], $pfs_src, $pfs_name, cot::$L['Mypfs']),
        'FORUMS_' . $pfs_tag . '_SFS' => (cot_auth('files', 'a', 'A')) ? cot_files_buildPfs(0, $pfs_src, $pfs_name, cot::$L['SFS']) : '',
    ));
}