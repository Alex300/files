<?php
/**
 * Files API
 *
 * @package Files
 * @author Cotonti Team
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */

use cot\modules\files\models\File;
use cot\modules\files\services\FileService;
use cot\modules\files\services\ThumbnailService;
use filesystem\LocalFilesystem;
use image\Image;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

defined('COT_CODE') or die('Wrong URL.');

// Additional API requirements
require_once cot_incfile('uploads');
require_once './datas/extensions.php';
require_once cot_incfile('extrafields');
require_once cot_incfile('forms');

if (!function_exists('cot_user_data')) {
    require_once cot_incfile('users', 'module');
}

// Self requirements
require_once cot_langfile('files', 'module');
require_once cot_incfile('files', 'module', 'resources');

Cot::$db->registerTable('files');
cot_extrafields_register_table('files');

Cot::$db->registerTable('files_folders');
cot_extrafields_register_table('files_folders');

/**
 * Returns number of attachments for a specific item.
 * @param string $source Target module/plugin code
 * @param int $sourceId Target item id
 * @param string $sourceField Target item field
 * @param string $type Attachment type filter: 'files', 'images'. By default includes all attachments.
 * @return int Number of attachments
 */
function cot_filesCount($source, $sourceId, $sourceField = '', $type = 'all')
{
    static $a_cache = [];

    $cacheField = ($sourceField != '') ? $sourceField : '_empty_field_name_';
    if (!isset($a_cache[$source][$sourceId][$cacheField][$type])) {
        $cond = [
            ['source', $source],
            ['source_id', $sourceId],
        ];
        if ($type === 'files') {
            if (Image::currentDriver() === Image::DRIVER_GD) {
                $cond[] = [[
                    ['is_img', 0],
                    ['ext', Image::supportedFormats(), '<>', 'OR'],
                ]];
            } else {
                $cond[] = ['is_img', 0];
            }
        } elseif ($type === 'images') {
            $cond[] = ['is_img', 1];
            if (Image::currentDriver() === Image::DRIVER_GD) {
                $cond[] = ['ext', Image::supportedFormats()];
            }
        }
        if ($sourceField !== '_all_') {
            $cond[] = ['source_field', $sourceField];
        }

        $a_cache[$source][$sourceId][$cacheField][$type] = File::count($cond);
    }
    return $a_cache[$source][$sourceId][$cacheField][$type];
}

/**
 * Fetches a single attachment object for a given item.
 * @param string $source Target module/plugin code.
 * @param int $sourceId Target item id.
 * @param string $sourceField Target item field
 * @param string $column Empty string to return full row, one of the following to return a single value: 'id',
 *                              'user_id', 'path', 'file_name', 'original_name', 'ext', 'is_img', 'size', 'title', 'downloads_count'
 * @param string $number Attachment number within item, or one of these values: 'first', 'rand' or 'last'. Defines which image is selected.
 * @return File|int|string|null Scalar column value, File object or NULL if no attachments found.
 */
function cot_filesGet($source, $sourceId, $sourceField = '', $column = '', $number = 'first')
{
    static $a_cache;

    if (!isset($a_cache[$source][$sourceId][$number])) {
        $order_by = $number == 'rand' ? 'RAND()' : 'sort_order';
        if ($number == 'last') {
            $order_by .= ' DESC';
        }

        $offset = is_numeric($number) && $number > 1 ? ((int) $number - 1) . ',' : 0;
        $cond = [
            ['source', $source],
            ['source_id', $sourceId],
        ];

        if ($sourceField != '_all_') {
            $cond[] = ['source_field', $sourceField];
        }
        $file = File::findByCondition($cond, 1, $offset, $order_by);
        if (!$file) {
            return null;
        }
        $a_cache[$source][$sourceId][$number] = current($file);

    }

    return empty($column) ? $a_cache[$source][$sourceId][$number] : $a_cache[$source][$sourceId][$number]->{$column};
}

/**
 * Extracts filename extension with tar (.tar.gz, tar.bz2, etc.) support.
 *
 * @param string $filename File name
 * @return string|false File extension or false on error
 */
function cot_filesGetExtension(string $filename)
{
    if (preg_match('#((\.tar)?\.\w+)$#', $filename, $m)) {
        return mb_strtolower(mb_substr($m[1], 1));
    }

    return false;
}

/**
 * Gets upload space limits.
 *
 * @param int $uid User ID. 0 - use current user
 * @param string $source
 * @param int $item
 * @param string $field
 * @return array
 * @throws Exception
 */
function cot_filesGetLimits($uid = 0, $source = 'pfs', $item = 0, $field = '')
{
    if (!is_null($uid)) {
        $uid = (int) $uid;
    }

    $limits = [
        'size_maxfile' => 0,
        'size_maxtotal' => 0,
        'size_used' => 0,
        'size_left' => 0,
        'count_max' => 0,
        'count_used'=> 0,
        'count_left'=> 0,
    ];

    // Use current user
    if ($uid <= 0) {
        if (Cot::$usr['id'] == 0) {
            // Default guest user data
            $urr = array(
                'user_id' => 0,
                'user_maingrp' => COT_GROUP_GUESTS
            );

        } else {
            // Get authorized user data
            $uid = Cot::$usr['id'];
            $urr = cot_user_data($uid);
        }

    } else {
        // Get specified user
        $urr = cot_user_data($uid);
        if (!$urr) {
            throw new Exception('User not found');
        }
    }

    if (isset($urr['auth'])) {
        unset($urr['auth']);
    }

    if ($source == 'sfs') {
        // Site file space
        if (cot_auth('files', 'a', 'A')) {
            $limits['size_maxfile']  = 100000000000000000;
            $limits['size_maxtotal'] = 100000000000000000;
            $limits['size_used']     = 0;
            $limits['size_left']     = 100000000000000000;
            $limits['count_max']     = 100000000000000000;
            $limits['count_used']    = 0;
            $limits['count_left']    = 100000000000000000;

            return $limits;
        }
    }

    $sql_condGroup = "g.grp_id={$urr['user_maingrp']}";
    if ($urr['user_id'] > 0) {
        $sql_condGroup = 'g.grp_id IN (SELECT gru_groupid FROM ' . Cot::$db->groups_users . " WHERE gru_userid = {$urr['user_id']})";
    }

    $sql = "SELECT MAX(g.grp_pfs_maxfile) AS size_maxfile, MAX(g.grp_pfs_maxtotal) AS size_maxtotal,
            SUM(f.size) as size_used, MAX(g.grp_files_perpost) as count_max
          FROM " . Cot::$db->groups . " as g
          LEFT JOIN " . Cot::$db->files . " as f ON f.source != 'sfs' AND f.user_id = {$urr['user_id']}
          WHERE $sql_condGroup";

    $tmp = Cot::$db->query($sql)->fetch();

    $limits['size_maxfile']  = (int) $tmp['size_maxfile'];
    if ($limits['size_maxfile'] == -1) {
        $limits['size_maxfile'] = 0;

    } elseif ($limits['size_maxfile'] == 0) {
        $limits['size_maxfile']  = 100000000000000000;
    }

    // Ограничения на загрузку файлов через POST
    // пока вынесено в контроллер
//        if(Cot::$cfg['files']['chunkSize'] == 0){
//            $limits['maxfile']  = min((int)$limits['maxfile'], cot_get_uploadmax() * 1024);
//        }

    $limits['size_maxtotal'] = (int)$tmp['size_maxtotal'];
    if($limits['size_maxtotal'] == -1) {
        $limits['size_maxtotal'] = 0;

    } elseif($limits['size_maxtotal'] == 0) {
        $limits['size_maxtotal']  = 100000000000000000;
    }

    // I'm not sure if we should always set size_used = 0 for guests.
    // Now you can set unlimit in admin-cp
    $limits['size_used'] = (int)$tmp['size_used'];
    $limits['size_left'] = $limits['size_maxtotal'] - $limits['size_used'];
    if($limits['size_left'] < 0) $limits['size_left'] = 0;


    if($source == 'pfs') {
        // В PFS не накладывается ограничений на количество файлов, только на размеры
        // There is no file count limits in PFS, only for file sizes
        $limits['count_max']     = 100000000000000000;
        $limits['count_used']    = 0;
        $limits['count_left']    = 100000000000000000;

        // Site file space
        if($uid === 0){
            $limits['size_maxfile']  = 100000000000000000;
            $limits['size_maxtotal'] = 100000000000000000;
            $limits['size_used']     = 0;
            $limits['size_left']     = 100000000000000000;
            return $limits;
        }


        return $limits;
    }

    $limits['count_max'] = (int)$tmp['count_max'];
    if($limits['count_max'] == -1) {
        $limits['count_max'] = 0;

    } elseif($limits['count_max'] == 0) {
        $limits['count_max']  = 100000000000000000;
    }

    $limits['count_used'] = File::count([
        ['source', $source],
        ['source_id', $item],
        ['source_field', $field],
    ]);
    $limits['count_left'] = $limits['count_max'] - $limits['count_used'];

    return $limits;
}

/**
 * Detects file MIME type
 * @param string $path File path
 * @return ?string MIME type
 */
function cot_filesGetMime($path): ?string
{
    if (!file_exists($path)) {
        return null;
    }

    $ext = cot_filesGetExtension($path);

    if (function_exists('mime_content_type')) {
        // Php extension 'fileinfo' is required
        return mime_content_type($path);

    } elseif (function_exists('finfo_open')) {
        // Php extension 'fileinfo' is required
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $path);
        finfo_close($finfo);
        return $mimetype;

    } elseif (class_exists('\League\MimeTypeDetection\ExtensionMimeTypeDetector')) {
        $detector = new ExtensionMimeTypeDetector();
        // Only detect by extension
        return $detector->detectMimeTypeFromPath($path);

    } elseif (isset(FileService::MIME_TYPES[$ext])) {
        return FileService::MIME_TYPES[$ext];

    } else {
        return 'application/octet-stream';
    }
}

/**
 * @param string $fileName
 * @return bool
 */
function cot_filesIsValidImageFile($fileName)
{
    $mime = cot_filesGetMime($fileName);
    if (!$mime) {
        return false;
    }
    [$type, $subtype] = explode('/', $mime);
    if ($type !== 'image') {
        return false;
    }

    $ext = cot_filesGetExtension($fileName);

    if (!in_array(mb_strtolower($ext), Image::supportedFormats())) {
        // Can't process this image
        return null;
    }

    return true;
}

/**
 * Strips all unsafe characters from file base name and converts it to latin
 *
 * Like cot_safename() but don't use cot_unique in file name. So it can be used in chunks file uploading
 *
 * @param string $basename File base name
 * @param bool $underscore Convert spaces to underscores
 * @param string $postfix Postfix appended to filename
 * @return string
 *
 * @see cot_safename()
 */
function cot_filesSafeName($basename, $underscore = true, $postfix = '')
{
    global $lang, $cot_translit;

    if (!$cot_translit && $lang != 'en' && file_exists(cot_langfile('translit', 'core'))) {
        require_once cot_langfile('translit','core');
    }

    $fname = mb_substr($basename, 0, mb_strrpos($basename, '.'));
    $ext = mb_substr($basename, mb_strrpos($basename, '.') + 1);
    if ($lang != 'en' && is_array($cot_translit)) {
        $fname = strtr($fname, $cot_translit);
    }
    if ($underscore) {
        $fname = str_replace(' ', '_', $fname);
    }
    $fname = str_replace('..', '.', $fname);
    $fname = preg_replace('#[^a-zA-Z0-9\-_\.\ \+]#', '', $fname);
//    if(empty($safename) || $safename != $fname) $fname = $safename.cot_unique();
    return $fname . $postfix . '.' . mb_strtolower($ext);
}

/**
 * Returns file's thumbnail url. Generates the thumbnail first if it does not exist.
 * Can be used in template files as callback
 * @param File|int $id File ID or instance of File.
 * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
 * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
 * @param string $frame Framing mode: one of \image\Image::THUMBNAIL_XXX constants (for backwards compatibility 'auto' and 'crop' also supported)
 * @param bool $watermark - set watermark if Cot::$cfg['files']['thumb_watermark'] not empty?
 * @param bool $lastMod Include last file modification time as GET parameter
 * @return string Thumbnail path on success or null on error
 *
 * @see ThumbnailService::thumbnail()
 */
function cot_filesThumbnailUrl($id, $width = 0, $height = 0, string $frame = '', $watermark = true, $lastMod = null): string
{
    if ($watermark === '0' || mb_strtolower($watermark) === 'false') {
        $watermark = false;
    }
    $watermark = (bool) $watermark;

    $thumbnail = ThumbnailService::thumbnail($id, $width, $height, $frame, $watermark);
    if (empty($thumbnail)) {
        return '';
    }

    $addLastMod = $lastMod;
    $lastModified = null;
    if ($lastMod === null) {
        // Default value
        if ($thumbnail['fileSystem'] instanceof LocalFilesystem) {
            $addLastMod = true;
        } else {
            if ($id instanceof File) {
                $addLastMod = true;
                $lastModified = strtotime($id->updated);
            }
        }
    }

    if ($addLastMod) {
        // FancyBox does not work this way
        return $thumbnail['url'] . '?lm=' . ($lastModified ?? $thumbnail['fileSystem']->lastModified($thumbnail['path']));
    }
    return $thumbnail['url'];
}

/**
 * Adds watermark for image
 * @param $source
 * @param $target
 * @param string $watermark watermark file
 * @param int $jpegquality
 * @return bool
 *
 * @deprecated ?? or @todo
 */
function cot_files_watermark($source, $target, $watermark = '', $jpegquality = 85){

    if (empty($watermark)) return false;
    if(!file_exists($source) || !is_readable($source)) return false;

    $sourceExt = cot_filesGetExtension($source);
    $targetExt = cot_filesGetExtension($target);

    $is_img = (int)in_array($sourceExt, array('gif', 'jpg', 'jpeg', 'png'));
    if (!$is_img) return false;

    // Load the image
    $image = imagecreatefromstring(file_get_contents($source));
    $w = imagesx($image);
    $h = imagesy($image);

    // Load the watermark
    $watermark = imagecreatefrompng($watermark);
    $ww = imagesx($watermark);
    $wh = imagesy($watermark);

    $wmAdded = false;
    if ( ($ww + 60) < $w && ($wh + 40) < $h ){
        imagealphablending($image, true);   // Наложение прозрачности

        if($targetExt == 'gif' || $targetExt == 'png'){
            imagesavealpha($image, true);
        }

        // Insert watermark to the right bottom corner
        imagecopy($image, $watermark, $w - 40 - $ww, $h-$wh-20, 0, 0, $ww, $wh);
        unlink($target);
        switch($targetExt)
        {
            case 'gif':
                imagegif($image, $target);
                break;

            case 'png':
                imagepng($image, $target);
                break;

            default:
                imagejpeg($image, $target, $jpegquality);
                break;
        }
        $wmAdded = true;

    }

    imagedestroy($watermark);
    imagedestroy($image);
    return $wmAdded;
}

/**
 * Workaround for splitting basename with beginning utf8 multibyte char
 */
function mb_basename($filepath, $suffix = '')
{
    $splited = preg_split('/\//', rtrim($filepath, '/ '));
    $suffix = (string) $suffix;

    return substr(basename('X' . $splited[count($splited) - 1], $suffix), 1);
}

/**
 * Delete user files.
 * Used when deleting a user
 *
 * @param int $userId Used ID
 * @return int Count of deleted files
 */
function cot_delete_user_files($userId)
{
    $userId = (int) $userId;
    $i = 0;
    if ($userId < 1) {
        return $i;
    }

    // Delete all user's PFS files
    $items = File::findByCondition([
        ['source', 'pfs'],
        ['user_id', $userId],
    ]);
    if (!empty($items)) {
        foreach ($items as $itemRow) {
            $i++;
            $itemRow->delete();
            unset($itemRow);
        }
        unset($items);
    }

    // Delete all user's PFS folders
    $items = files_models_Folder::findByCondition([['user_id', $userId,],]);
    if (!empty($items)) {
        foreach ($items as $itemRow) {
            // Folder is not a file, so we don't need to count them
            $itemRow->delete();
            unset($itemRow);
        }
        unset($items);
    }

    // Delete all user's files
    $items = File::findByCondition([
        ['source', 'user',],
        ['source_id', $userId,],
    ]);
    if (!empty($items)) {
        foreach ($items as $itemRow) {
            $i++;
            $itemRow->delete();
            unset($itemRow);
        }
        unset($items);
    }

    /* === Hook === */
    foreach (cot_getextplugins('files.delete_user_files.done') as $pl) {
        include $pl;
    }
    /* ===== */

    return $i;
}

// ===== outputs and widgets =====

/**
 * Generates an avatar selection
 * Use it as CoTemplate callback.
 *
 * @param int $userId for admins only
 * @param string $tpl Template code
 * @return string
 *
 * @todo no cache parameter for css
 * @todo generate formUnikey
 */
function cot_filesAvatarBox($userId = null, $tpl = 'files.avatarbox')
{
    global $R, $cot_modules, $usr;

    [Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']] = cot_auth('files', 'a');

    $source = 'pfs';
    $item = 0;
    $filed = '';

    $uid = Cot::$usr['id'];
    if ($usr['isadmin']) {
        $uid = $userId;

        if (is_null($uid)) {
            $uid = cot_import('uid', 'G', 'INT');
        }
        if (is_null($uid)) {
            $uid = Cot::$usr['id'];
        }
    }

    $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'linkFile': 'linkFileFooter';
    $nc = $cot_modules['files']["version"];

    // Подключаем jQuery-templates только один раз
//    static $jQtemlatesOut = false;
//    $jQtemlates = '';
    $modUrl = Cot::$cfg['modules_dir'] . '/files';

    // CSS to style the file input field as button and adjust the Bootstrap progress bars
    Resources::$jsFunc($modUrl . '/lib/upload/css/jquery.fileupload.css');
    Resources::$jsFunc($modUrl . '/lib/upload/css/jquery.fileupload-ui.css');

    /* === Java Scripts === */
    // The jQuery UI widget factory, can be omitted if jQuery UI is already included
    Resources::linkFileFooter($modUrl . '/lib/upload/js/vendor/jquery.ui.widget.js?nc=' . $nc, 'js');
    Resources::linkFileFooter($modUrl . '/lib/upload/js/jquery.iframe-transport.js?nc=' . $nc);

    if (Cot::$cfg['files']['image_resize'] && Cot::$cfg['files']['image_maxwidth'] > 0 && Cot::$cfg['files']['image_maxheight'] > 0) {
        // The Load Image plugin is included for the preview images and image resizing functionality
        Resources::linkFileFooter($modUrl . '/lib/JavaScript-Load-Image/js/load-image.all.min.js?nc=' . $nc);
    }

    // The basic File Upload plugin
    Resources::linkFileFooter($modUrl . '/lib/upload/js/jquery.fileupload.js?nc=' . $nc);

    // The File Upload file processing plugin
    Resources::linkFileFooter($modUrl . '/lib/upload/js/jquery.fileupload-process.js?nc='.$nc);

    if (Cot::$cfg['files']['image_resize'] && Cot::$cfg['files']['image_maxwidth'] > 0 && Cot::$cfg['files']['image_maxheight'] > 0) {
        // The File Upload image preview & resize plugin
        Resources::linkFileFooter($modUrl . '/lib/upload/js/jquery.fileupload-image.js?nc=' . $nc);
    }

    // The File Upload validation plugin
    Resources::linkFileFooter($modUrl . '/lib/upload/js/jquery.fileupload-validate.js?nc='.$nc);

    $formId = "{$source}_{$item}_{$filed}";
    $type = ['image'];

    $type = json_encode($type);

    // Get current avatar
    $user = cot_user_data($uid);
    $avatar = cot_filesUserAvatar($user['user_avatar'], $user);

    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    $limits = cot_filesGetLimits(Cot::$usr['id'], $source, $item, '');

    $unikey = mb_substr(md5($formId . '_' . rand(0, 99999999)), 0, 15);
    $params = base64_encode(serialize([
        'source'  => $source,
        'item'    => $item,
        'field'   => '',
        'limit'   => $limits['count_max'],
        'type'    => $type,
        'avatar'  => 1,
        'unikey'  => $unikey,
    ]));

    $action = 'index.php?e=files&m=upload&source=' . $source . '&item=' . $item;
    if ($uid != Cot::$usr['id']) {
        $t->assign([
            'UPLOAD_UID' => $uid,
        ]);
        $action .= '&uid='.$uid;
    }

    // Metadata
    $t->assign([
        'AVATAR'         => $avatar,
        'UPLOAD_ID'      => $formId,
        'UPLOAD_SOURCE'  => $source,
        'UPLOAD_ITEM'    => $item,
        'UPLOAD_FIELD'   => '',
        'UPLOAD_LIMIT'   => $limits['count_max'],
        'UPLOAD_TYPE'    => $type,
        'UPLOAD_PARAM'   => $params,
        'UPLOAD_CHUNK'   => (int) Cot::$cfg['files']['chunkSize'],
        'UPLOAD_EXTS'    => preg_replace('#[^a-zA-Z0-9,]#', '', Cot::$cfg['files']['exts']),
//        'UPLOAD_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',Cot::$cfg['plugin']['attach2']['accept']),
        'UPLOAD_MAXSIZE' => $limits['size_maxfile'],
        'UPLOAD_ACTION'  => $action,
        'UPLOAD_X'       => Cot::$sys['xk'],
    ]);

    $t->parse();
    return $t->text();
}

/**
 * Generates a link to PFS popup window
 *
 * @param int $uid User ID
 * @param string $formName Form name
 * @param string $inputName Input name
 * @param string $title Link title
 * @param string $parser Custom parser (otional)
 * @return string
 */
function cot_filesBuildPfs($uid, $formName, $inputName, $title, $parser = '')
{
    if ($uid == 0)
    {
        $res = "<a href=\"javascript:files_pfs('0','" . $formName . "','" . $inputName . "','" . $parser . "')\">" . $title . "</a>";
    }
    elseif (cot_auth('files', 'a', 'R'))
    {
        $res = "<a href=\"javascript:files_pfs('" . $uid . "','" . $formName . "','" . $inputName . "','" . $parser . "')\">" . $title . "</a>";
    }
    else
    {
        $res = '';
    }

    static $jsOut = false;

    if ($res != '' && !$jsOut) {
//        $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'cot_rc_link_file': 'cot_rc_link_footer';
        $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'cot_rc_embed': 'cot_rc_embed_footer';

        $jsFunc("function files_pfs(id, c1, c2, parser){
    window.open(getBaseHref() + 'index.php?e=files&m=pfs&uid=' + id + '&c1=' + c1 + '&c2=' + c2 + '&parser=' + parser, 'PFS', 'status=1, toolbar=0,location=0,directories=0,menuBar=0,resizable=1,scrollbars=yes,width=754,height=512,left=32,top=16');
    }");
        $jsOut = true;
    }

    return $res;
}

/**
 * Renders attached items on page
 * @param string $source Target module/plugin code
 * @param int|int[] $item Target item id
 * @param string $field
 * @param string $tpl Template code
 * @param string $type Attachment type filter: 'files', 'images'. By default includes all attachments.
 * @param int $limit
 * @param string $order
 * @return string Rendered output
 */
function cot_filesDisplay(
    string $source,
    $item,
    string $field = '',
    string $tpl = 'files.display',
    string $type = 'all',
    $limit = 0,
    string $order = ''
): string {
    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    $t->assign([
        'FILES_SOURCE' => $source,
        'FILES_ITEM' => $item,
        'FILES_FIELD' => $field,
    ]);

    $condition = [['source', $source]];

    if (is_array($item)) {
        $item = array_map('intval', $item);
    } else {
        $item = (int) $item;
    }
    $condition[] = ['source_id', $item];

    if ($field !== '_all_') {
        $condition[] = ['source_field', $field];
    }

    if ($type == 'files') {
        if (Image::currentDriver() === Image::DRIVER_GD) {
            $condition[] = [[
                ['is_img', 0],
                ['ext', Image::supportedFormats(), '<>', 'OR'],
            ]];
        } else {
            $condition[] = ['is_img', 0];
        }
    } elseif ($type == 'images') {
        $condition[] = ['is_img', 1];
        if (Image::currentDriver() === Image::DRIVER_GD) {
            $condition[] = ['ext', Image::supportedFormats()];
        }
    }

    if ($order == '') {
        $order = 'sort_order ASC';
    }

    $files = File::findByCondition($condition, $limit, 0, $order);

    $num = 1;
    if ($files) {
        $t->assign(array(
            'FILES_COUNT' => count($files),
        ));

        /* === Hook - Part1 : Set === */
        $extp = cot_getextplugins('files.display.loop');
        /* ===== */

        foreach ($files as $row) {
            $t->assign(File::generateTags($row, 'FILES_ROW_'));
            $t->assign([
                'FILES_ROW_NUM' => $num,
                'FILES_ROW' => $row,
            ]);

            /* === Hook - Part2 : Include === */
            foreach ($extp as $pl) {
                include $pl;
            }
            /* ===== */

            $t->parse('MAIN.FILES_ROW');
            $num++;
        }

    } else {
        $t->assign([
            'FILES_COUNT'  => 0,
        ]);
    }

    /* === Hook === */
    foreach (cot_getextplugins('files.display.tags') as $pl) {
        include $pl;
    }
    /* ===== */

    $t->parse();

    return $t->text();
}


/**
 * Renders files only as downloads block.
 * @param string $source Target module/plugin code
 * @param int|int[] $item Target item id
 * @param string $field
 * @param string $tpl Template code
 * @param int $limit
 * @param string $order
 * @return string Rendered output
 */
function cot_filesDownloads(string $source, $item, string $field = '', string $tpl = 'files.downloads', $limit = 0, string $order = ''): string
{
    return cot_filesDisplay($source, $item, $field, $tpl, 'files', $limit, $order);
}

/**
 * Generates a form input file
 * Use it as CoTemplate callback.
 *
 * @param string $source
 * @param int $item
 * @param string $name Input name
 * @param string $type File types. Comma separated 'all', 'file', 'image', 'audio', 'video'
 * @param int $limit file limit
 *      -1 - use plugin config value
 *       0 - unlimited
 * @param string $tpl Template code
 * @param int $standalone 0 - normal, 1 in new window like pfs, 2 - in iframe like cot_filesWidget
 * @param ?int $userId for admins only
 * @return string
 *
 * @todo no cache parameter for css
 * @todo generate formUnikey
 */
function cot_filesFileBox(
    string $source,
    $item,
    string $name = '',
    string $type = 'all',
    $limit = -1,
    string $tpl = 'files.filebox',
    $standalone = 0,
    $userId = null
): string {
    global $R, $cot_modules, $usr;

    [Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']] = cot_auth('files', 'a');

    $uid = Cot::$usr['id'];
    if ($source == 'pfs' && $usr['isadmin']) {
        $uid = $userId;

        if (is_null($uid)) {
            $uid = cot_import('uid', 'G', 'INT');
        }
        if (is_null($uid)) {
            $uid = $usr['id'];
        }
    }

    $nc = $cot_modules['files']['version'];

    // Подключаем jQuery-templates только один раз
    static $jQtemplatesOut = false;
    $jQtemplates = '';
    if (!$jQtemplatesOut) {
        $templates = new XTemplate(cot_tplfile('files.templates', 'module'));
        $templates->assign(array(
            'IS_STANDALONE' => ($standalone == 1) ? 1 : 0,
        ));
        $templates->parse();
        $jQtemplates = $templates->text();
        $jQtemplatesOut = true;

        $modUrl = Cot::$cfg['modules_dir'] . '/files';

        // Generic page styles
        Resources::linkFile($modUrl.'/tpl/filebox.css');

        // Bootstrap Image Gallery styles
        //$jsFunc($cfg['plugins_dir'].'/attach2/lib/Gallery/css/blueimp-gallery.min.css');

        // CSS to style the file input field as button and adjust the Bootstrap progress bars
        Resources::linkFile($modUrl.'/lib/upload/css/jquery.fileupload.css');
        Resources::linkFile($modUrl.'/lib/upload/css/jquery.fileupload-ui.css');

        /* === Java Scripts === */
        // The jQuery UI widget factory, can be omitted if jQuery UI is already included
        Resources::linkFileFooter($modUrl.'/lib/upload/js/vendor/jquery.ui.widget.js?nc='.$nc);

        // The Templates plugin is included to render the upload/download listings
        Resources::linkFileFooter($modUrl.'/lib/JavaScript-Templates/tmpl.min.js?nc='.$nc);

        // The Load Image plugin is included for the preview images and image resizing functionality
        Resources::linkFileFooter($modUrl.'/lib/JavaScript-Load-Image/js/load-image.all.min.js?nc='.$nc);

        // The Canvas to Blob plugin is included for image resizing functionality
        Resources::linkFileFooter($modUrl.'/lib/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js?nc='.$nc);

        // blueimp Gallery script
        //cot_rc_link_footer($cfg['plugins_dir'].'/attach2/lib/Gallery/js/jquery.blueimp-gallery.min.js');

        // The Iframe Transport is required for browsers without support for XHR file uploads
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.iframe-transport.js?nc='.$nc);

        // The basic File Upload plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload.js?nc='.$nc);

        // The File Upload file processing plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-process.js?nc='.$nc);

        // The File Upload image preview & resize plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-image.js?nc='.$nc);

        // The File Upload audio preview plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-audio.js?nc='.$nc);

        // The File Upload video preview plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-video.js?nc='.$nc);

        // The File Upload validation plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-validate.js?nc='.$nc);

        // The File Upload user interface plugin
        Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload-ui.js?nc='.$nc);

        //    // The localization script
        //    cot_rc_link_footer($cfg['plugins_dir'].'/attach2/lib/upload/js/locale.js');


        // The main application script
        Resources::linkFileFooter($modUrl.'/js/files.js?nc='.$nc);

        // Table Drag&Drop plugin for reordering
        Resources::linkFileFooter('js/jquery.tablednd.min.js?nc='.$nc);
    }

    $formId = "{$source}_{$item}_{$name}";
    $type = str_replace(' ', '', $type);
    if (empty($type)) {
        $type = array('all');
    } else {
        $type = explode(',', $type);
    }
    $type = json_encode($type);

    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    $limits = cot_filesGetLimits($usr['id'], $source, $item, $name);
    if ($limit == 0) {
        $limit = 100000000000000000;
    } elseif($limit == -1) {
        $limit = $limits['count_max'];
    }

    $params = array(
        'source'  => $source,
        'item'    => $item,
        'field'   => $name,
        'limit'   => $limit,
        'type'    => $type,
    );

    $action = 'index.php?e=files&m=upload&source=' . $source . '&item=' . $item;
    if (!empty($name)) {
        $action .= '&field='.$name;
    }

    static $unikey = '';

    $formUnikey = '';
    if (!in_array($source, ['sfs', 'pfs']) && $item == 0) {
        $unikeyName = "cf_{$source}_{$item}";

        if (empty($unikey)) {
            $unikey = cot_import($unikeyName, 'P', 'TXT');
            if (!$unikey) {
                $unikey = cot_import($unikeyName, 'G', 'TXT');
            }
            $unikey = cot_import_buffered($unikeyName, $unikey);
            if (!$unikey) {
                $unikey = mb_substr(md5("{$source}_{$item}" . '_' . \Cot::$usr['id'] . rand(0, 99999999)), 0, 15);
            }
        }
        $params['unikey'] = $unikey;
        $formUnikey = cot_inputbox('hidden', $unikeyName, $unikey);

        $action .= '&unikey=' . $unikey;
    }

    $params = base64_encode(serialize($params));

    if ($uid != $usr['id']) {
        $t->assign([
            'UPLOAD_UID' => $uid,
        ]);
        $action .= '&uid=' . $uid;
    }

    // Metadata
    $t->assign([
        'UPLOAD_ID' => $formId,
        'UPLOAD_SOURCE' => $source,
        'UPLOAD_ITEM' => $item,
        'UPLOAD_FIELD' => $name,
        'UPLOAD_LIMIT' => $limit,
        'UPLOAD_TYPE' => $type,
        'UPLOAD_PARAM' => $params,
        'UPLOAD_CHUNK' => (int) Cot::$cfg['files']['chunkSize'],
        'UPLOAD_EXTS' => preg_replace('#[^a-zA-Z0-9,]#', '', Cot::$cfg['files']['exts']),
//        'UPLOAD_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',Cot::$cfg['plugin']['attach2']['accept']),
        'UPLOAD_MAXSIZE' => $limits['size_maxfile'],
        'UPLOAD_ACTION'  => $action,
        'UPLOAD_THUMB_WIDTH' => (int) Cot::$cfg['files']['thumb_width'],
        'UPLOAD_THUMB_HEIGHT' => (int) Cot::$cfg['files']['thumb_height'],
        'UPLOAD_X' => Cot::$sys['xk'],
    ]);

    if ($standalone == 2) {
        $html = Resources::render();
        if ($html) {
            Cot::$out['head_head'] = (!empty(Cot::$out['head_head'])) ? $html . Cot::$out['head_head'] : $html;
        }
        Cot::$out['footer_rc'] = !empty(Cot::$out['footer_rc']) ? Cot::$out['footer_rc'] : '';
        Cot::$out['footer_rc'] .= Resources::renderFooter();
    }

    $t->parse();
    return $formUnikey . $t->text() . $jQtemplates;
}

/**
 * Renders images only as a gallery.
 * @param string $source Target module/plugin code
 * @param int|int[] $item Target item id
 * @param string $field
 * @param string $tpl Template code
 * @param int $limit
 * @param string $order
 * @return string Rendered output
 */
function cot_filesGallery(string $source, $item, string $field = '', string $tpl = 'files.gallery', $limit = 0, string $order = ''): string
{
    return cot_filesDisplay($source, $item, $field, $tpl, 'images', $limit, $order);
}

/**
 * Get current avatar
 * @param $file_id
 * @param array|int $urr
 * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
 * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
 * @param string $frame
 * @return string
 */
function cot_filesUserAvatar($file_id = 0, $urr = 0, $width = 0, $height = 0, $frame = ''): string
{
    $avatar = cot_rc('files_user_default_avatar');
    if ($file_id == 0 && is_array($urr) && isset($urr['user_avatar'])) {
        $file_id = $urr['user_avatar'];
    }
    $userAvatar = cot_filesUserAvatarUrl($file_id, $width, $height, $frame);
    $alt = Cot::$L['Avatar'];
    if (is_array($urr)) {
        $alt = htmlspecialchars(cot_user_full_name($urr));
    }
    if ($userAvatar) {
        $avatar = cot_rc('files_user_avatar', [
            'src' => $userAvatar,
            'alt' => $alt,
        ]);
    }
    return $avatar;
}

/**
 * @param $fileId File ID or instance of File.
 * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
 * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
 * @param string $frame Framing mode: one of \image\Image::THUMBNAIL_XXX constants (for backwards compatibility 'auto' and 'crop' also supported)
 * @return string
 */
function cot_filesUserAvatarUrl($fileId, $width = 0, $height = 0, string $frame = ''): string
{
    // -------
    // Если залито обновление модуля до версии 2.0.0-beta1, а таблицы в БД еще не обновлены, эта функция может ломать админку.
    // @todo убрать через несколько релизов
    global $cot_modules;
    if (version_compare($cot_modules['files']['version'], '2.0.0-beta1', '<')) {
        return '';
    }
    // -------

    static $avatarUrlCache = [];

    if (empty($width) || (is_numeric($width) && (int) $width <= 0))  {
        $width  = (int) Cot::$cfg['files']['avatar_width'];
    }
    if (empty($height) || (is_numeric($width) && (int) $height <= 0)) {
        $height = (int) Cot::$cfg['files']['avatar_height'];
    }

    if (empty($frame)) {
        $frame = Cot::$cfg['files']['avatar_framing'];
    }

    $cacheKey = $fileId . '-' . $width . ' - ' . $height .  $frame;
    if (isset($avatarUrlCache[$cacheKey])) {
        return $avatarUrlCache[$cacheKey];
    }

    if ($fileId instanceof File) {
        $file = $fileId;
        $fileId = $file->id;
    } else {
        $fileId = (int) $fileId;
        if ($fileId < 1) {
            return '';
        }
        $file = File::getById($fileId);
    }

    if (!$file) {
        return '';
    }

    $avatarUrlCache[$cacheKey] = cot_filesThumbnailUrl($file, $width, $height, $frame);

    return $avatarUrlCache[$cacheKey];
}

/**
 * Generates a file upload/edit widget.
 * Use it as CoTemplate callback.
 *
 * @param string $source Target module/plugin code.
 * @param int $item Target item id.
 * @param string $field Target item field
 * @param string $tpl Template code
 * @param string $width
 * @param string $height
 * @return string Rendered widget
 */
function cot_filesWidget(string $source, $item, string $field = '', string $tpl = 'files.widget', $width = '100%', $height = '300'): string
{
    global $files_widget_present, $cot_modules;

    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    // Metadata
    $limits = cot_filesGetLimits(Cot::$usr['id'], $source, $item, $field);

    $urlParams = [
        'm' => 'files',
        'a' => 'display',
        'source' => $source,
        'item' => $item,
        'field' => $field,
        'nc' => $cot_modules['files']['version'],
    ];

    $t->assign([
        'FILES_SOURCE' => $source,
        'FILES_ITEM' => $item,
        'FILESH_FIELD' => $field,
        'FILES_EXTS' => preg_replace('#[^a-zA-Z0-9,]#', '', Cot::$cfg['files']['exts']),
//        'FILES_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',$cfg['plugin']['attach2']['accept']),
        'FILES_MAXSIZE' => $limits['size_maxfile'],
        'FILES_WIDTH' => $width,
        'FILES_HEIGHT' => $height,
        'FILES_URL' => cot_url('files', $urlParams, '', true),
    ]);

    $t->parse();

    $files_widget_present = true;

    return $t->text();
}

/**
 * TPL files not allows to execute php code
 * So it will be here
 *
 * @todo not needed with View template engine
 */
function cot_filesLoadBootstrap(): string
{
    $ret = '<!-- 654654654 -->';

    //if(Resources::getAlias('@bootstrap.js') !== null) $ret = Resources::getAlias('@bootstrap.js');
    $cssAlias = Resources::getAlias('@bootstrap.css');
    if ($cssAlias) {
        if (!Resources::isFileAdded('@bootstrap.css')) {
            $ret .= cot_rc(
                'code_rc_css_file', [
                    'url' => Resources::getAlias('@bootstrap.css')
                ]
            ) . "\n";

        }
    }

    $themeAlias = Resources::getAlias('@bootstrapTheme.css');
    if ($themeAlias) {
        if (!Resources::isFileAdded('@bootstrapTheme.css')) {
            $ret .= cot_rc(
                'code_rc_css_file',
                [
                    'url' => Resources::getAlias('@bootstrapTheme.css')
                ]
            ) . "\n";
        }
    }

    return $ret;
}