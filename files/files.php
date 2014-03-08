<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=module
[END_COT_EXT]
==================== */
 /**
 * module Social for Cotonti Siena
 *
  * @package Files
  * @author Cotonti Team
  * @copyright Copyright (c) Cotonti Team 2011-2014
  * @license BSD License
 */
defined('COT_CODE') or die('Wrong URL.');

// Environment setup
$env['location'] = 'files';

// Self requirements
require_once cot_incfile('files', 'module');

if(empty($m)) $m = 'main';   // Констроллер по-умолчанию

//if (COT_AJAX && !$m) $m = 'ajax';

// Only if the file exists...
if (file_exists(cot_incfile('files', 'module', $m))) {
    require_once cot_incfile('files', 'module', $m);

    $outHeaderFooter = true;

    /* Create the controller */
    $_class = ucfirst($m).'Controller';
    $controller = new $_class();
    
    // TODO кеширование
    /* Perform the Request task */
    $action = $a.'Action';
    if (!$a && method_exists($controller, 'indexAction')){
        $content = $controller->indexAction();
    }elseif (method_exists($controller, $action)){
        $content = $controller->$action();
    }else{
        // Error page
		cot_die_message(404);
		exit;
    }
    
    //ob_clean();
    if($outHeaderFooter) require_once $cfg['system_dir'] . '/header.php';
    if (isset($content)) echo $content;
    if($outHeaderFooter) require_once $cfg['system_dir'] . '/footer.php';

}else{
    // Error page
    cot_die_message(404);
    exit;
}