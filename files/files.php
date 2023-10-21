<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=module
[END_COT_EXT]
==================== */

 /**
  * module Files for Cotonti Siena
  * @package Files
  */

defined('COT_CODE') or die('Wrong URL.');

// Environment setup
\Cot::$env['location'] = 'files';

// Self requirements
require_once cot_incfile('files', 'module');

if (empty($m)) {
    // Default controller
    $m = 'main';
}

// Old controllers
$oldControllers = ['main', 'pfs'];
if (in_array($m, $oldControllers)) {
    if (file_exists(cot_incfile('files', 'module', $m))) {
        require_once cot_incfile('files', 'module', $m);
    }
    $controllerClassName = ucfirst($m).'Controller';
} else {
    $controllerClassName = '\\cot\\modules\\files\\controllers\\' . ucfirst($m) . 'Controller';
}

if (!class_exists($controllerClassName)) {
    // Error page
    cot_die_message(404);
    exit;
}

$outHeaderFooter = true;

/* Create the controller */
$controller = new $controllerClassName();

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

//ob_clean();
if ($outHeaderFooter) {
    require_once \Cot::$cfg['system_dir'] . '/header.php';
}
if (isset($content)) {
    echo $content;
}
if ($outHeaderFooter) {
    require_once \Cot::$cfg['system_dir'] . '/footer.php';
}

