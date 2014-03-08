<?php
defined('COT_CODE') or die('Wrong URL.');

/**
 * Main PFS Controller class for the Files module
 *
 *  Вывод папок и файлов пользователя
 * 
 * @package Files
 * @subpackage pfs
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2008-2014
 */
class MainController{

    /**
     * файлы пользователя
     * @return string
     */
    public function indexAction(){
        global $usr;

        list($pgf, $df) = cot_import_pagenav('df', cot::$cfg['files']['maxFoldersPerPage']);   // page number folders


    }



    /**
     * Альбомы пользователя
     */
    public function albumAction(){
        return $this->userFiles('image');
    }

    /**
     * Файлы пользователя
     */
    public function filesAction(){
        return $this->userFiles('file');
    }



}