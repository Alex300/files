<?php
(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

/**
 * Files main Admin Controller class
 * 
 * @package shop
 * @subpackage admin
 * @copyright http://portal30.ru
 *
 */
class MainController{

    /**
     * Main (index) Action.
     */
    public function indexAction(){
        global $adminpath, $adminhelp, $cot_yesno;

//        cot_rc_link_file(cot::$cfg['modules_dir'].'/files/tpl/files.admin.css');

        $tpl = new XTemplate(cot_tplfile('files.admin.main'));

        if (!function_exists('gd_info')){
            cot_message(cot::$L['adm_nogd'], 'warning');
        }else{
            $gd_datas = gd_info();
            foreach ($gd_datas as $k => $i){
                if (mb_strlen($i) < 2) $i = $cot_yesno[$i];
                $tpl->assign(array(
                    'GD_DATAS_NAME' => $k,
                    'GD_DATAS_DATAS_ENABLE' => $i
                ));
                $tpl->parse('MAIN.GD_INFO.ROW');
            }
            $tpl->parse('MAIN.GD_INFO');
        }

        $pfsUrl = cot_url('files', array('m'=>'pfs', 'uid'=>cot::$usr['id']));
        $filesUrl = cot_url('files', array('uid'=>cot::$usr['id']));
        $albumUrl = cot_url('files', array('a'=>'album', 'uid'=>cot::$usr['id']));
        $adminhelp  = cot::$L['files_userfilespace'].": <a href=\"{$pfsUrl}\" target=\"_blank\">{$pfsUrl}</a> (".
            cot::$L['files_userfilespace_desc'].")<br />";
        $adminhelp .= cot::$L['files_userpublic_files'].": <a href=\"{$filesUrl}\" target=\"_blank\">{$filesUrl}</a><br />";
        $adminhelp .= cot::$L['files_userpublic_albums'].": <a href=\"{$albumUrl}\" target=\"_blank\">{$albumUrl}</a><br /><br />";

        $adminhelp .= "<strong>«".cot::$L['files_cleanup']."»</strong> ".cot::$L['files_cleanup_desc'].".<br />";
        $adminhelp .= cot::$L['files_deleteallthumbs_desc'];

        $tpl->assign(array(

            'PAGE_TITLE' => cot::$L['Files'].": ".cot::$L['Administration'],

        ));
        $tpl->parse('MAIN');
        return $tpl->text();
	}

}