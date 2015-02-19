<?php
/**
 * English Language File for the Files Module
 *
 * @package Files
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2008-2014
 */
defined('COT_CODE') or die('Wrong URL.');

$L['info_desc'] = 'Personal file space and attach images and files and build galleries using pages and forum posts';
$L['info_notes'] = 'DO NOT FORGET to create a writable folder for the files. Jquery must be on.';

$L['files_add'] = 'Add files';
$L['files_albums'] = 'Albums';
$L['files_attach'] = 'Attach files';
$L['files_attachments'] = 'Attachments';
$L['files_cancel'] = 'Cancel upload';
$L['files_created'] = 'Created';
$L['files_downloads'] = 'Downloads';
$L['files_draghere'] = 'Drag files here';
$L['files_extallowed'] = 'Extensions allowed';
$L['files_havenotfolders'] = 'have not any folders';
$L['files_intheroot'] = 'in the root';
$L['files_inthisfolder'] = 'in this folder';
$L['files_isgallery'] = 'Album?';
$L['files_ispublic'] = 'Public?';
$L['files_maxsize'] = 'Maximum size for a file';
$L['files_maxspace'] = 'Maximum space allowed';
$L['files_morefiles'] = 'More files';
$L['files_mypage'] = 'My page';
$L['files_newalbum'] = 'Create a new album';
$L['files_newfile'] = 'Upload a file';
$L['files_newfolder'] = 'Create a new folder';
$L['files_onpage'] = 'On this page';
$L['files_pastecode'] = 'Paste code';
$L['files_pastefile'] = 'Paste as file link';
$L['files_pastegallery'] = 'Paste as gallery';
$L['files_pasteimage'] = 'Paste as image';
$L['files_pastethumb'] = 'Paste as thumbnail';
$L['files_processing'] = 'Processing';
$L['files_replace'] = 'Replace';
$L['files_select_avatar'] = 'Select avatar';
$L['files_start'] = 'Start';
$L['files_start_upload'] = 'Start upload';
$L['files_totalsize'] = 'Total size';
$L['files_updated'] = 'Updated';
$L['files_youhavenotfolders'] = 'You have not any folders';

/**
 * Admin
 */
$L['adm_maxsizesingle'] = $L['files_maxsizesingle'] = 'Max size for a single file in '.$L['PFS'].' ('.$L['bytes'].'),<br />
    0 - unlimited, -1 - disabled';
$L['adm_maxsizeallpfs'] = $L['files_maxsizesingle'] = 'Max size of all files together in '.$L['PFS'].' ('.$L['bytes'].'),<br />
    0 - unlimited, -1 - disabled';
$L['files_allpfs'] = 'All PFS';
$L['files_allpfs_help'] = $L['PFS'].' of all registered users';
$L['files_cleanup'] = 'Clean up';
$L['files_cleanup_conf'] = 'This will remove all files attached to posts which no longer exist. Continue?';
$L['files_cleanup_desc'] = 'Will remove all files attached to pages and forum posts which no longer exists, if for some
    reason they were not removed automatically';
$L['files_deleteallthumbs'] = 'Remove all thumbnails';
$L['files_deleteallthumbs_conf'] = 'All thumbnails for all images will be deleted. New thumbnails will be generated when they are needed';
$L['files_deleteallthumbs_desc'] = 'If you have changed thumbnails settings and want to regenerate them, use the
    <strong>«Remove all thumbnails»</strong>. New thumbnails will be generated when they are needed. It can cause
    problems on pages with lots of thumbnails';
$L['files_gd'] = 'GD graphical library';
$L['files_itemsperpost'] = 'Attachments per post (max.), 0 - unlimited';
$L['files_items_removed'] = 'Items removed';
$L['files_thumbs_removed'] = 'Thumbnails removed';
$L['files_userfilespace'] = 'User file space';
$L['files_userfilespace_desc'] = 'This is your files. Set desired user id';
$L['files_userpublic_albums'] = 'User Public images and albums';
$L['files_userpublic_files'] = 'User public files and folders';
$L['files_extrafields_hint'] = 'Extrafields must have class «file-edit» to edit them wia Ajax';
$L['files_extrafields_files'] = 'Files Extrafields';
$L['files_extrafields_folders'] = 'File folders Extrafields';

/**
 * Errors & Messages
 */
$L['files_err_abort'] = 'File upload aborted';
$L['files_err_count'] = 'Exceeded maximum number of files';
$L['files_err_move'] = 'Failed to move the uploaded file';
$L['files_err_nospace'] = 'Not enough personal disk space';
$L['files_err_perms'] = 'You are not permitted to do this';
$L['files_err_toobig'] = 'File is too big';
$L['files_err_type'] = 'This type of files is not allowed';
$L['files_err_replace'] = 'Could not replace file';
$L['files_err_upload'] = 'The file could not be uploaded';
$L['files_folder_deleted'] = 'Folder «%1$s» deleted';
$L['files_foldertitlemissing'] = 'A folder title is required.';
$L['files_nogd'] = 'The GD graphical library is not supported by this host, Cotonti won\'t be able to create
    thumbnails for images.';
$L['files_saved'] = 'Saved.';


/**
 * Module Config
 */
$L['cfg_folder'] = 'Directory for files';
$L['cfg_prefix'] = 'File prefix';
$L['cfg_exts'] = 'Allowed extensions (comma separated, no dots and spaces)';
$L['cfg_filecheck'] = 'File Check';
$L['cfg_filecheck_hint'] = 'If Enabled will check any uploaded files through the '.$L['PFS'].', or images through the profile. To insure they are valid files. &quot;Yes&quot; recommended, for security reasons.';
$L['cfg_pfsnomimepass'] = 'No Mimetype Pass';
$L['cfg_pfsnomimepass_hint'] = 'If Enabled will it will allow uploaded files to pass even if there is no mimetype in the config file.';
$L['cfg_maxFoldersPerPage'] = 'Max folders count per page';
$L['cfg_pfs_winclose'] = 'Close popup window after file insert into editor';

$L['cfg_upl_separator'] = 'Upload options';
$L['cfg_autoupload'] = 'Start uploading automatically';
$L['cfg_sequential'] = 'Sequential uploading instead of concurrent';
$L['cfg_chunkSize'] = 'Upload files by chunks (in bytes)';
$L['cfg_chunkSize_hint'] = 'Large files can be uploaded in smaller chunks with
    browsers supporting the Blob API. (Leave empty to disable)';

$L['cfg_img_separator'] = 'Image options';
$L['cfg_image_convert'] = 'Convert all images to JPG on upload';
$L['cfg_image_resize'] = array('Reduce uploaded images', 'Loaded images will be proportionally reduced
    In accordance with the following parameters');
$L['cfg_image_maxwidth']  = 'Reduce image width to';
$L['cfg_image_maxheight'] = 'Reduce image height to';
$L['cfg_quality'] = 'JPEG quality in %';

$L['cfg_th_separator'] = 'Thumbnails options';
$L['cfg_thumbs'] = 'Display image thumbnails?';
$L['cfg_thumb_width'] = 'Default thumbnail width';
$L['cfg_thumb_height'] = 'Default thumbnail height';
$L['cfg_thumb_framing'] = 'Default thumbnail framing mode';
$L['cfg_thumb_framing_params'] = array(
    'height' => 'By height',
    'width'  => 'By width',
    'auto'   => 'Auto',
    'border_auto' => 'Auto with border',
    'crop'   => 'Crop'
);
$L['cfg_thumb_border'] = 'Thumbnails, border size, px';
$L['cfg_thumb_border_hint'] = 'If thumbnail framing mode is «Auto with border»';
$L['cfg_thumb_bordercolor'] = 'Thumbnails, border color';
$L['cfg_thumb_bordercolor_hint'] = 'Hex color code. If thumbnail framing mode is «Auto with border»';
$L['cfg_thumb_bgcolor'] = 'Thumbnails, background color';
$L['cfg_thumb_bgcolor_hint'] = 'If thumbnail framing mode is «Auto with border»';
$L['cfg_upscale'] = 'Upscale images smaller than thumb size';

$L['cfg_wm_separator'] = 'Watermark options';
$L['cfg_thumb_watermark'] = 'Watermark for thumbnails';
$L['cfg_thumb_watermark_hint'] = 'Path to watermark file. Supports png with transparency. (Leave empty to disable)';
$L['cfg_thumb_wm_widht'] = 'Min. thumbnail width';
$L['cfg_thumb_wm_widht_hint'] = 'The watermark will be placed on on a thumbnail only if it width and height equal or greater than a given params';
$L['cfg_thumb_wm_height'] = 'Min. thumbnail height';

$L['cfg_av_separator'] = 'User avatar options';
$L['cfg_avatar_width'] = 'Default avatar width';
$L['cfg_avatar_height'] = 'Default avatar height';
$L['cfg_avatar_framing'] = 'Default avatar framing mode';
$L['cfg_avatar_framing_params'] = array(
    'height' => 'By height',
    'width'  => 'By width',
    'auto'   => 'Auto',
    'border_auto' => 'Auto with border',
    'crop'   => 'Crop'
);