<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin
[END_COT_EXT]
==================== */

/**
 * Files admin panel
 *
 * @package Files
 * @author Cotonti Team
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 *
 * @var string|null $a
 * @var string|null $m
 * @var string|null $n
 */
(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list(Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']) = cot_auth('files', 'a');
cot_block(Cot::$usr['isadmin']);

// Self requirements
require_once cot_incfile('files', 'module');

$adminPath[] = [cot_url('admin', ['m' => 'extensions']), Cot::$L['Extensions']];
$adminPath[] = [cot_url('admin', ['m' => 'extensions', 'a' => 'details', 'mod' => $m]), $cot_modules[$m]['title']];
$adminPath[] = [cot_url('admin', ['m' => $m]), Cot::$L['Administration']];
$adminHelp = '';


// TODO кеширование
$t = new XTemplate(cot_tplfile('files.admin'));

if (!$n) {
    $n = 'main';
}

$controllerClassName = '\\cot\\modules\\files\\controllers\\Admin' . ucfirst($n) . 'Controller';
if (!class_exists($controllerClassName )) {
    // Error page
    cot_die_message(404);
    exit;
}

// Only if the controller class exists...
$controller = new $controllerClassName ();

//if (empty($a)) {
//    $a = cot_import('a', 'P', 'TXT');
//}
/* Perform the Request task */
$content = '';
$actionExists = false;
if (!empty($a)) {
    $controllerAction = $a . 'Action';
    if (method_exists($controller, $controllerAction)) {
        $actionExists = true;
        $content = $controller->$controllerAction();
    }
} elseif (method_exists($controller, 'indexAction')) {
    $actionExists = true;
    $content = $controller->indexAction();
}

if (!$actionExists) {
    cot_die_message(404);
    exit;
}


if (COT_AJAX && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Не использовать эту фичу, если $_SERVER["REQUEST_METHOD"] == 'GET' т.к. это поломает ajax пагинацию
    require_once Cot::$cfg['system_dir'] . '/header.php';
    echo $content;
    require_once Cot::$cfg['system_dir'] . '/footer.php';
    exit;
}

$t->assign('CONTENT', $content);

// Error and message handling
cot_display_messages($t);

$t->parse('MAIN');    
$adminMain = $t->text('MAIN');
