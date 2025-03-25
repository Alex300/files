<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=forums.editpost.tags, forums.posts.newpost.tags, forums.newtopic.tags
Tags=forums.editpost.tpl:{FORUMS_EDITPOST_PFS},{FORUMS_EDITPOST_SFS};forums.posts.tpl:{FORUMS_POSTS_NEWPOST_PFS},{FORUMS_POSTS_NEWPOST_SFS};forums.newtopic.tpl:{FORUMS_NEWTOPIC_PFS},{FORUMS_NEWTOPIC_SFS}
[END_COT_EXT]
==================== */

/**
 * PFS links for forums
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

    $pfsCaller = cot_get_caller();
    if ($pfsCaller === 'forums.posts') {
        $pfsSrc = 'newpost';
        $pfsName = 'rmsgtext';
        $pfsTag = 'POSTS_NEWPOST';
    } elseif ($pfsCaller === 'forums.newtopic') {
        $pfsSrc = 'newtopic';
        $pfsName = 'rmsgtext';
        $pfsTag = 'NEWTOPIC';
    } else {
        $pfsSrc = 'editpost';
        $pfsName = 'rmsgtext';
        $pfsTag = 'EDITPOST';
    }

    $t->assign([
        'FORUMS_' . $pfsTag . '_PFS' => cot_filesBuildPfs(Cot::$usr['id'], $pfsSrc, $pfsName, Cot::$L['Mypfs']),
        'FORUMS_' . $pfsTag . '_SFS' => cot_auth('files', 'a', 'A')
            ? cot_filesBuildPfs(0, $pfsSrc, $pfsName, Cot::$L['SFS'])
            : '',
    ]);
}