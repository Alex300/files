<?php
/**
 * Resourses for the Files module
 *
 * @package Files
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2008-2014
 */

cot::$R['files_pfs_code_addfile']   = '<a href="\'+gfile+\'" title="\'+gdesc+\'">\'+gname+\'</a>';
cot::$R['files_pfs_code_addpix']    = '<img src="\'+gfile+\'" alt="\'+gdesc+\'" />';
cot::$R['files_pfs_code_addthumb']  = '<a href="\'+gfile+\'" title="\'+gdesc+\'"><img src="\'+gthumb+\'" alt="\'+gdesc+\'" /></a>';

cot::$R['files_pfs_code_header_javascript'] = '
	function addfile(gfile, gdesc, gname, gthumb) {
		if (opener.CKEDITOR.instances.{$c2} != undefined) {
			opener.CKEDITOR.instances.{$c2}.insertHtml(\'{$pfs_code_addfile}\');
		} else {
			insertText(opener.document, \'{$c2}\', \'{$pfs_code_addfile}\');
		}
		{$winclose}
	}
	function addthumb(gfile, gdesc, gname, gthumb) {
		if (opener.CKEDITOR.instances.{$c2} != undefined) {
			opener.CKEDITOR.instances.{$c2}.insertHtml(\'{$pfs_code_addthumb}\');
		} else {
			insertText(opener.document, \'{$c2}\', \'{$pfs_code_addthumb}\');
		}
		{$winclose}
	}
	function addpix(gfile, gdesc, gname) {
		if (opener.CKEDITOR.instances.{$c2} != undefined) {
			opener.CKEDITOR.instances.{$c2}.insertHtml(\'{$pfs_code_addpix}\');
		} else {
			insertText(opener.document, \'{$c2}\', \'{$pfs_code_addpix}\');
		}
		{$winclose}
	}
    function addgallery(fid) {
        if (opener.CKEDITOR.instances.{$c2} != undefined) {
            opener.CKEDITOR.instances.{$c2}.insertHtml(\'[pfs_gallery?f=\'+ fid +\']\');
        } else {
            insertText(opener.document, \'{$c2}\', \'[pfs_gallery?f=\'+ fid +\']\');
        }
        {$winclose}
    }';

/**
 * PFS Folder Types
 */
cot::$R['files_icon_gallery'] =
	'<img class="icon" src="images/icons/'.cot::$cfg['defaulticons'].'/gallery.png" alt="'.cot::$L['Gallery'].'" />';
cot::$R['files_icon_folder'] =
	'<img class="icon" src="images/icons/'.cot::$cfg['defaulticons'].'/folder.png" alt="'.cot::$L['Folder'].'" />';

/**
 * Image / Thumb / Link Insert Icons
 */
//cot::$R['files_icon_pastefile'] =
//    '<img class="icon" src="images/icons/'.cot::$cfg['defaulticons'].'/link.png" title="'.cot::$L['files_pastefile'].'" />';
cot::$R['files_icon_pastefile'] = '<i class="glyphicon glyphicon-link"></i>';

//cot::$R['files_icon_pasteimage'] =
//    '<img class="icon" src="images/icons/'.cot::$cfg['defaulticons'].'/image.png" title="'.cot::$L['files_pasteimage'].'" />';
cot::$R['files_icon_pasteimage'] = '<i class="glyphicon glyphicon-picture"></i>';

//cot::$R['files_icon_pastethumb'] =
//    '<img class="icon" src="images/icons/'.cot::$cfg['defaulticons'].'/thumbnail.png" title="'.cot::$L['files_pastethumb'].'" />';
cot::$R['files_icon_pastethumb'] = '<i class="glyphicon glyphicon-th"></i>';

/**
 * Image / Thumb / Link Add Icons
 */
cot::$R['files_pfs_link_addpix'] =
    '<a href="#" title="'.cot::$L['files_pasteimage'].'" '.
    'data-toggle="tooltip" class="btn btn-default btn-sm pasteImage">'.cot::$R['files_icon_pasteimage'].'</a>';


cot::$R['files_pfs_link_addthumb'] =
    '<a href="#"  title="'.cot::$L['files_pastethumb'].'" '.
        'data-toggle="tooltip" class="btn btn-default btn-sm pasteThumb">'.cot::$R['files_icon_pastethumb'].'</a>';

cot::$R['files_pfs_link_addfile'] =
    '<a href="#" title="'.cot::$L['files_pastefile'].'" '.
        'data-toggle="tooltip" class="btn btn-default btn-sm pasteFile">'.cot::$R['files_icon_pastefile'].'</a>';


$R['files_user_avatar'] = '<img src="{$src}" alt="{$alt}" class="avatar img-responsive" />';
$R['files_user_default_avatar'] = '<img src="datas/defaultav/blank.png" alt="'.cot::$L['Avatar'].'" class="avatar img-responsive" />';