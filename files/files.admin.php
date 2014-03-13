<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin
[END_COT_EXT]
==================== */

/**
 * Files admin panel
 *
 * @package shop
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2014
 */
(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');
cot_block($usr['isadmin']);

// Self requirements
require_once cot_incfile('files', 'module');

$adminpath[] = array(cot_url('admin', 'm=extensions'), $L['Extensions']);
$adminpath[] = array(cot_url('admin', 'm=extensions&a=details&mod='.$m), $cot_modules[$m]['title']);
$adminpath[] = array(cot_url('admin', 'm='.$m), $L['Administration']);
$adminhelp = '';


// TODO кеширование
$t = new XTemplate(cot_tplfile('files.admin'));

if (!$n) $n = 'main';

// Only if the file exists...
if (file_exists(cot_incfile('files', 'module', 'admin.'.$n))) {
    require_once cot_incfile('files', 'module','admin.'.$n);
    /* Create the controller */
    $_class = ucfirst($n).'Controller';

    $controller = new $_class();
    
    if(!$a) $a = cot_import('a', 'P', 'TXT');
    /* Perform the Request task */
    $_action = $a.'Action';
    if ($a && method_exists($controller, $_action)){
        $content = $controller->$_action();
    }elseif(method_exists($controller, 'indexAction')){
        $content = $controller->indexAction();
    }
}else{
    // Error page
    cot_die_message(404);
    exit;
}

if (COT_AJAX) {
    require_once $cfg['system_dir'] . '/header.php';
    echo $content;
    require_once $cfg['system_dir'] . '/footer.php';
    exit;
}
//$adminhelp .= '<p><a href="http://portal30.ru/sozdanie-internet-sajtov/free-scripts/cotonti-shop" target="_blanl">powered by portal30</a></p>';
$t->assign('CONTENT', $content);

// Error and message handling
cot_display_messages($t);

$t->parse('MAIN');    
$adminmain = $t->text('MAIN');
