<?php
/**
 * Files API
 *
 * @package Files
 * @author Cotonti Team
 * @author Kalnov Alexey    <kalnovalexey@yandex.ru>
 */

use cot\modules\files\model\File;
use image\exception\ImageException;
use image\Image;

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
 * Terminates further script execution with a given
 * HTTP response status and output.
 * If the message is omitted, then it is taken from the
 * HTTP status line.
 * @param  int    $code     HTTP/1.1 status code
 * @param  string $message  Output string
 * @param  array  $response Custom response object
 */
function cot_files_ajax_die($code, $message = null, $response = null)
{
    $status = cot_files_ajax_get_status($code);
    cot_sendheaders('application/json', $status);
    if (is_null($message)) {
        $message = substr($status, strpos($status, ' ') + 1);
    }

    if (is_null($response)) {
        echo json_encode($message);

    } else {
        $response['message'] = $message;
        echo json_encode($response);
    }

    exit;
}

/**
 * Returns HTTP satus line for a given
 * HTTP response code
 * @param  int    $code HTTP response code
 * @return string       HTTP status line
 */
function cot_files_ajax_get_status($code)
{
    static $msg_status = array(
        200 => '200 OK',
        201 => '201 Created',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        307 => '307 Temporary Redirect',
        400 => '400 Bad Request',
        401 => '401 Authorization Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        409 => '409 Conflict',
        411 => '411 Length Required',
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        503 => '503 Service Unavailable',
    );

    if (isset($msg_status[$code])) {
        return $msg_status[$code];
    }

    return "$code Unknown";
}

/**
 * Подсветка ошибочных элементов на форме
 *
 * @param string $name имя элемента
 * @return string
 */
function cot_files_formGroupClass($name)
{
    $error = Cot::$cfg['msg_separate'] ? cot_implode_messages($name, 'error') : '';
    if($error) return 'has-error has-feedback';

    return '';
}

/**
 * Checks if file extension is allowed for upload. Returns error message or empty string.
 * Emits error messages via cot_error().
 *
 * @param  string  $file  Full file name
 * @return boolean        true if all checks passed, false if something was wrong
 */
//function cot_files_checkFile($file)
//{
//    $file_ext = cot_files_get_ext($file);
//    if (!cot_files_isExtensionAllowed($file_ext)) {
//        return false;
//    }
//
//    $valid_exts = explode(',', Cot::$cfg['files']['exts']);
//    $valid_exts = array_map('trim', $valid_exts);
//
//    $handle = fopen($file, "rb");
//    $tmp = fread ( $handle , 10 );
//    fclose($handle);
//    if (!in_array('php', $valid_exts) && mb_stripos(trim($tmp), '<?php') === 0) {
//        return false;
//    }
//
//    return true;
//}

/**
 * Checks if file extension is allowed for upload. Returns error message or empty string.
 * Emits error messages via cot_error().
 *
 * @param  string  $ext   File extension
 * @return boolean        true if all checks passed, false if something was wrong
 */
function cot_files_isExtensionAllowed($ext)
{
    if(!Cot::$cfg['files']['filecheck']) return true;

    $valid_exts = explode(',', Cot::$cfg['files']['exts']);
    $valid_exts = array_map('trim', $valid_exts);
    if (empty($ext) || !in_array($ext, $valid_exts)) {
        return false;
    }

    return true;
}

/**
 * Returns number of attachments for a specific item.
 * @param string $source Target module/plugin code
 * @param int $sourceId Target item id
 * @param string $sourceField Target item field
 * @param string $type Attachment type filter: 'files', 'images'. By default includes all attachments.
 * @return int Number of attachments
 */
function cot_files_count($source, $sourceId, $sourceField = '', $type = 'all')
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
function cot_files_get($source, $sourceId, $sourceField = '', $column = '', $number = 'first')
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
 * @param  string $filename File name
 * @return string|false    File extension or false on error
 */
function cot_files_get_ext($filename)
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
function cot_files_getLimits($uid = 0, $source = 'pfs', $item = 0, $field = '')
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
 * @return string|null MIME type
 */
function cot_files_getMime($path)
{
    if (!file_exists($path)) {
        return null;
    }

    $mime_types = array(
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',
        'flv'  => 'video/x-flv',

        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        '7z'  => 'application/x-7z-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt'  => 'video/quicktime',
        'mov' => 'video/quicktime',
        'mp4' => 'video/mp4',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai'  => 'application/postscript',
        'eps' => 'application/postscript',
        'ps'  => 'application/postscript',

        // ms office
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
    );

    $ext = cot_files_get_ext($path);

    if (function_exists('mime_content_type')) {
        // Php extension 'fileinfo' is required
        return mime_content_type($path);

    } elseif (function_exists('finfo_open')) {
        // Php extension 'fileinfo' is required
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $path);
        finfo_close($finfo);
        return $mimetype;

    } elseif (isset($mime_types[$ext])) {
        return $mime_types[$ext];

    } else {
        return 'application/octet-stream';
    }
}

/**
 * @param string $fileName
 * @return bool
 */
function cot_files_isValidImageFile($fileName)
{
    $mime = cot_files_getMime($fileName);
    if (!$mime) {
        return false;
    }
    list($type, $subtype) = explode('/', $mime);
    if ($type !== 'image') {
        return false;
    }

    return true;
}

/**
 * Привязка ранее загруженных файлов к только что созданному объекту
 *
 * @param $source
 * @param $item
 */
function cot_files_linkFiles($source, $item){

    $formId = "{$source}_0";

    $unikey = cot_import('cf_'.$formId, 'P', 'TXT');
    if (!$unikey) {
        $unikey = cot_import('cf_'.$formId, 'G', 'TXT');
    }
    //$unikey = cot_import_buffered('cf_'.$formId, $unikey);

    if($unikey && $item > 0){
        $condition = [
            ['source', $source],
            ['source_id', 0],
            ['unikey', $unikey],
        ];

        $files = File::findByCondition($condition);

        if ($files) {
            foreach ($files as $fileRow){
                $oldFullPath = \Cot::$cfg['files']['folder']. '/' . $fileRow->fullName;
                $newPath = cot_files_path($source, $item, $fileRow->id, $fileRow->ext, $fileRow->user_id);
                $newFullPath = \Cot::$cfg['files']['folder']. '/' . $newPath;

                $file_dir = dirname($newFullPath);
                if (!is_dir($file_dir)) {
                    mkdir($file_dir, \Cot::$cfg['dir_perms'], true);
                }
                if (!@rename($oldFullPath, $newFullPath)) {
                    cot_error(\Cot::$L['files_err_upload']);
                    $fileRow->delete();

                } else {
                    $fileRow->source_id = $item;
                    $fileRow->path = dirname($newPath);
                    $fileRow->file_name = basename($newPath);
                    $fileRow->unikey = '';
                    $fileRow->save();
                }
            }
        }
    }

    cot_files_formGarbageCollect();
}

/**
 * Calculates attachment path.
 * Return path relative to Cot::$cfg['files']['folder']
 *
 * @param  string $source Module or plugin code
 * @param  int    $item Parent item ID
 * @param  int    $id   Attachment ID
 * @param  string $ext  File extension. Leave it empty to auto-detect.
 * @param  int    $uid   User ID for pfs
 * @return string|false  Path for the file on disk
 *
 * @todo обфускация имени файла или использование оригинального имени файла
 */
function cot_files_path($source, $item, $id, $ext = '', $uid = 0)
{
    $filesPath = $source . '/' . $item;
    if ($source == 'pfs') {
        if ($uid == 0) {
            $uid = Cot::$usr['id'];
        }
        $filesPath = $source . '/'. $uid. '/' . $item;
    }

    if (empty($ext)) {
        // Auto-detect extension
        $mask = $filesPath . '/' . Cot::$cfg['files']['prefix'] . $id . '.*';
        $files = glob($mask, GLOB_NOSORT);
        if (!$files || count($files) == 0) {
            return false;
        } else {
            return $files[0];
        }
    } else {
        return $filesPath . '/' . Cot::$cfg['files']['prefix'] . $id . '.' . $ext;
    }
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
function cot_files_safeName($basename, $underscore = true, $postfix = '')
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
 * Temporary folder for file upload
 * @param bool $create Create folder if not exists?
 * @return string
 */
function cot_files_tempDir($create = true)
{
    $tmpDir = sys_get_temp_dir();
    if (!empty($tmpDir) && @is_writable($tmpDir)) {
        $uplDir = $tmpDir . DIRECTORY_SEPARATOR . 'files_' . mb_substr(md5(Cot::$cfg['secret_key']), 10) .
            '_upload';
        if (!$create) {
            return $uplDir;
        }

        if (!file_exists($uplDir)) {
            mkdir($uplDir, Cot::$cfg['dir_perms'], true);
        }
        if (is_writable($uplDir)) {
            return $uplDir;
        }
    }

    // Fall back
    $uplDir = Cot::$cfg['files']['folder'] . DIRECTORY_SEPARATOR . '/' .
        mb_substr(md5(Cot::$cfg['secret_key']), 10).'_upload';
    if ($create && !file_exists($uplDir)) {
        mkdir($uplDir, Cot::$cfg['dir_perms'], true);
    }

    return $uplDir;
}

/**
 * Returns attachment thumbnail path. Generates the thumbnail first if
 * it does not exist.
 * @param File|int $id File ID or instance of File.
 * @param int $width Thumbnail width in pixels
 * @param int $height Thumbnail height in pixels
 * @param string $frame Framing mode: one of \image\Image::THUMBNAIL_XXX constants (for backwards compatibility 'auto' and 'crop' also supported)
 * @param bool $watermark - set watermark if Cot::$cfg['files']['thumb_watermark'] not empty?
 * @return string|null Thumbnail path on success or null on error
 *
 * @todo Проверка на конвертирование в JPEG. Если файл подлежит конвертированию, то миниатюра должна быть в JPEG.
 */
function cot_files_thumb($id, $width = 0, $height = 0, $frame = '', $watermark = true)
{
    if ($id instanceof File) {
        $row = $id;
        $id = $row->id;
    }

    // Validate arguments
    if (!is_numeric($id) || $id <= 0) {
        return '';
    }

    if ($watermark === '0' || mb_strtolower($watermark) === 'false') {
        $watermark = false;
    }

    if (
        empty($frame)
        || !in_array(
            $frame,
            [Image::THUMBNAIL_OUTBOUND, Image::THUMBNAIL_INSET, Image::THUMBNAIL_WIDTH, Image::THUMBNAIL_HEIGHT, 'auto', 'crop']
        )
    ) {
        $frame = Cot::$cfg['files']['thumb_framing'];
    }

    if ($width <= 0)  {
        $width  = (int) Cot::$cfg['files']['thumb_width'];
    }
    if ($height <= 0) {
        $height = (int) Cot::$cfg['files']['thumb_height'];
    }

    // Attempt to load from cache
    $thumbs_folder = Cot::$cfg['files']['folder'] . '/_thumbs';
    $cache_folder  = $thumbs_folder . '/' . $id;
    if (!file_exists($cache_folder)) {
        mkdir($cache_folder, Cot::$cfg['dir_perms'], true);
    }
    $thumbPath = cot_files_thumb_path($id, $width, $height, $frame);

    if (!$thumbPath || !file_exists($thumbPath)) {
        // Generate a new thumbnail
        if ($frame === 'crop') {
            $frame = Image::THUMBNAIL_OUTBOUND;
        } elseif ($frame === 'auto') {
            $frame = Image::THUMBNAIL_INSET;
        }

        if (!isset($row)) {
            $row = File::getById($id);
        }
        if (!$row || !$row->is_img || !in_array(mb_strtolower($row->ext), Image::supportedFormats())) {
            return null;
        }

        $originalFile = Cot::$cfg['files']['folder'] . '/' . $row->fullName;
        if (!is_readable($originalFile)) {
            return null;
        }

        $thumbs_folder = $thumbs_folder . '/' . $id;
        $thumbPath = $thumbs_folder . '/'
            . \Cot::$cfg['files']['prefix'] . $id . '-' . $width . 'x' . $height . '-' . $frame . '.' . $row->ext;

        try {
            $image = Image::load($originalFile)->thumbnail($width, $height, $frame, (bool)\Cot::$cfg['files']['upscale']);
        } catch (ImageException $e) {
            return null;
        }

        // Watermark
        if (
            $watermark
            && !empty(\Cot::$cfg['files']['thumb_watermark'])
            && is_readable(\Cot::$cfg['files']['thumb_watermark'])
            && $image->getWidth() >= \Cot::$cfg['files']['thumb_wm_widht']
            && $image->getHeight() >= \Cot::$cfg['files']['thumb_wm_height']
        ) {
            $watermarkImage = Image::load(\Cot::$cfg['files']['thumb_watermark']);
            $imageWidth = $image->getWidth();
            $imageHeight = $image->getHeight();
            $wmWidth = $watermarkImage->getWidth();
            $wmHeight = $watermarkImage->getHeight();
            if (
                ($wmWidth + 60) < $imageWidth
                && ($wmHeight + 40) < $imageHeight
            ) {
                // Insert watermark to the right bottom corner
                $image->paste($watermarkImage, $imageWidth - 40 - $wmWidth, $imageHeight - $wmHeight - 20);
            }
            unset($watermarkImage);
        }

        try {
            $image->save($thumbPath, (int) Cot::$cfg['files']['quality']);
        } catch (ImageException $e) {
            unset($image);
            return null;
        }
    }

    /* === Hook === */
    foreach (cot_getextplugins('files.thumbnail.done') as $pl) {
        include $pl;
    }
    /* ===== */

    unset($image);

    return $thumbPath;
}

/**
 * Calculates path for the file thumbnail.
 * @param  int    $id     File ID
 * @param  int    $width  Thumbnail width
 * @param  int    $height Thumbnail height
 * @param  int    $frame  Thumbnail framing mode
 * @return string         Path for the file on disk or false file was not found
 */
function cot_files_thumb_path($id, $width, $height, $frame)
{
    $thumbs_folder = Cot::$cfg['files']['folder'] . '/_thumbs/' . $id;
    $mask = $thumbs_folder . '/' . Cot::$cfg['files']['prefix'] . $id . '-' . $width . 'x' . $height . '-' . $frame . '.*';
    $files = glob($mask, GLOB_NOSORT);
    if (!$files || count($files) == 0) {
        return false;
    } else {
        return $files[0];
    }
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

    $sourceExt = cot_files_get_ext($source);
    $targetExt = cot_files_get_ext($target);

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
 * Garbage collect
 * Сборка мусора от несохраненных форм
 */
function cot_files_formGarbageCollect(){

    $yesterday = (int) (Cot::$sys['now'] - 60 * 60 * 24);
    if ($yesterday < 100) {
        return 0; // Just in case
    }

    //$dateTo = date('Y-m-d H:i:s',  );   // До вчерашнего дня
    $condition = [
        ['source', ['sfs', 'pfs'], '<>'],
        ['created', date('Y-m-d H:i:s',  $yesterday), '<'],
        ['unikey', '', '<>']
    ];

    $cnt = 0;

    $files = File::findByCondition($condition);
    if ($files) {
        foreach($files as $fileRow){
            $fileRow->delete();
            $cnt++;
        }
    }

    $tmpDir = cot_files_tempDir(false);
    if (is_dir($tmpDir)) {
        $objects = scandir($tmpDir);
        $yesterday2 = (int) (Cot::$sys['now'] - 60 * 60 * 24);
        if ($yesterday2 < 100) {
            return 0;
        }
        foreach ($objects as $file) {
            if ($file != "." && $file != "..") {
                if (filetype($tmpDir . DIRECTORY_SEPARATOR . $file) != 'dir') {
                    // Delete old temporary files
                    $currentModified = filectime($tmpDir . DIRECTORY_SEPARATOR . $file);
                    if ($currentModified < $yesterday2) {
                        @unlink($tmpDir . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
        }
    }

    return $cnt;
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
 * Recursive remove directory
 *
 * @param string $dir
 * @return bool
 */
function rrmdir($dir)
{
    if (empty($dir) && $dir != '0') {
        return false;
    }

    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") {
                    rrmdir($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
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
    $items = files_model_Folder::findByCondition([['user_id', $userId,],]);
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
 * Generates a avatar selecteion
 * Use it as CoTemplate callback.
 *
 * @param int $userId for admins only
 * @param string $tpl Template code
 * @return string
 *
 * @todo no cache parameter for css
 * @todo generate formUnikey
 */
function cot_files_avatarbox($userId = null, $tpl = 'files.avatarbox' )
{
    global $R, $cot_modules, $usr;

    list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');

    $source = 'pfs';
    $item = 0;
    $filed = '';

    $uid = Cot::$usr['id'];
    if($usr['isadmin']){
        $uid = $userId;

        if(is_null($uid)) $uid = cot_import('uid', 'G', 'INT');
        if(is_null($uid)) $uid = $usr['id'];
    }

    $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'linkFile': 'linkFileFooter';
    $nc = $cot_modules['files']["version"];

    // Подключаем jQuery-templates только один раз
//    static $jQtemlatesOut = false;
//    $jQtemlates = '';
    $modUrl = Cot::$cfg['modules_dir'].'/files';

    // CSS to style the file input field as button and adjust the Bootstrap progress bars
    Resources::$jsFunc($modUrl.'/lib/upload/css/jquery.fileupload.css');
    Resources::$jsFunc($modUrl.'/lib/upload/css/jquery.fileupload-ui.css');

    /* === Java Scripts === */
    // The jQuery UI widget factory, can be omitted if jQuery UI is already included
    Resources::linkFileFooter($modUrl.'/lib/upload/js/vendor/jquery.ui.widget.js?nc='.$nc, 'js');
    Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.iframe-transport.js?nc='.$nc);
    Resources::linkFileFooter($modUrl.'/lib/upload/js/jquery.fileupload.js?nc='.$nc);

    $formId = "{$source}_{$item}_{$filed}";
    $type = array('image');

    $type = json_encode($type);

    // Get current avatar
    $user = cot_user_data($uid);
    $avatar = cot_files_user_avatar($user['user_avatar'], $user);

    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    $limits = cot_files_getLimits($usr['id'], $source, $item, '');

    $unikey = mb_substr(md5($formId . '_' . rand(0, 99999999)), 0, 15);
    $params = base64_encode(serialize(array(
        'source'  => $source,
        'item'    => $item,
        'field'   => '',
        'limit'   => $limits['count_max'],
        'type'    => $type,
        'avatar'  => 1,
        'unikey'  => $unikey
    )));

    $action = 'index.php?e=files&m=upload&source='.$source.'&item='.$item;
    if($uid != $usr['id']){
        $t->assign(array(
            'UPLOAD_UID'     => $uid,
        ));
        $action .= '&uid='.$uid;
    }

    // Metadata
    $t->assign(array(
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
    ));

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
function cot_files_buildPfs($uid, $formName, $inputName, $title, $parser = '')
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

    if($res != '' && !$jsOut){

//        $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'cot_rc_link_file': 'cot_rc_link_footer';
        $jsFunc = (!defined('COT_HEADER_COMPLETE')) ? 'cot_rc_embed': 'cot_rc_embed_footer';

        $jsFunc("function files_pfs(id, c1, c2, parser){
    window.open(getBaseHref() + 'index.php?e=files&m=pfs&uid=' + id + '&c1=' + c1 + '&c2=' + c2 + '&parser=' + parser, 'PFS', 'status=1, toolbar=0,location=0,directories=0,menuBar=0,resizable=1,scrollbars=yes,width=754,height=512,left=32,top=16');
    }");
        $jsOut = true;
    }

    return($res);
}

/**
 * Renders attached items on page
 * @param  string $source   Target module/plugin code
 * @param  integer $item    Target item id
 * @param  string $field
 * @param  string $tpl      Template code
 * @param  string $type     Attachment type filter: 'files', 'images'. By default includes all attachments.
 * @param  int $limit
 * @param  string $order
 * @return string           Rendered output
 */
function cot_files_display($source, $item, $field = '',  $tpl = 'files.display', $type = 'all', $limit = 0, $order = '')
{
    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    $t->assign([
        'FILES_SOURCE'  => $source,
        'FILES_ITEM'    => $item,
        'FILES_FIELD'   => $field,
    ]);

    $condition = [['source', $source]];
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

    if ($field != '_all_') {
        $condition[] = ['source_field', $field];
    }

    if ($order == '') {
        $order = 'sort_order ASC';
    }

    if (is_array($item)) {
        $item = array_map('intval', $item);
    } else {
        $item = (int) $item;
    }
    $condition[] = ['source_id', $item];

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
        $t->assign(array(
            'FILES_COUNT'  => 0,
        ));
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
 * @param  string $source   Target module/plugin code
 * @param  integer $item    Target item id
 * @param  string $field
 * @param  string $tpl      Template code
 * @param  int $limit
 * @param  string $order
 * @return string           Rendered output
 */
function cot_files_downloads($source, $item, $field = '', $tpl = 'files.downloads', $limit = 0, $order = '')
{
    return cot_files_display($source, $item, $field, $tpl, 'files', $limit, $order);
}

/**
 * Generates a form input file
 * Use it as CoTemplate callback.
 *
 * @param $source
 * @param $item
 * @param string $name Input name
 * @param string $type File types. Comma separated 'all', 'file', 'image', 'audio', 'video'
 * @param int $limit file limit
 *      -1 - use plugin config value
 *       0 - unlimited
 * @param string $tpl Template code
 * @param int $standalone 0 - normal, 1 in new window like pfs, 2 - in iframe like cot_files_widget
 * @param int $userId   for admins only
 * @return string
 *
 * @todo no cache parameter for css
 * @todo generate formUnikey
 */
function cot_files_filebox(
    $source,
    $item,
    $name = '',
    $type = 'all',
    $limit = -1,
    $tpl = 'files.filebox',
    $standalone = 0,
    $userId = null
) {
    global $R, $cot_modules, $usr;

    list(Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']) = cot_auth('files', 'a');

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

    $nc = $cot_modules['files']["version"];

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

    $limits = cot_files_getLimits($usr['id'], $source, $item, $name);
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
        $t->assign(array(
            'UPLOAD_UID' => $uid,
        ));
        $action .= '&uid=' . $uid;
    }

    // Metadata
    $t->assign([
        'UPLOAD_ID'      => $formId,
        'UPLOAD_SOURCE'  => $source,
        'UPLOAD_ITEM'    => $item,
        'UPLOAD_FIELD'   => $name,
        'UPLOAD_LIMIT'   => $limit,
        'UPLOAD_TYPE'    => $type,
        'UPLOAD_PARAM'   => $params,
        'UPLOAD_CHUNK'   => (int) Cot::$cfg['files']['chunkSize'],
        'UPLOAD_EXTS'    => preg_replace('#[^a-zA-Z0-9,]#', '', Cot::$cfg['files']['exts']),
//        'UPLOAD_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',Cot::$cfg['plugin']['attach2']['accept']),
        'UPLOAD_MAXSIZE' => $limits['size_maxfile'],
        'UPLOAD_ACTION'  => $action,
        'UPLOAD_THUMB_WIDTH' => (int) Cot::$cfg['files']['thumb_width'],
        'UPLOAD_THUMB_HEIGHT' => (int) Cot::$cfg['files']['thumb_height'],
        'UPLOAD_X'       => Cot::$sys['xk'],
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
 * @param  string $source   Target module/plugin code
 * @param  integer $item    Target item id
 * @param  string $field
 * @param  string $tpl      Template code
 * @param  int $limit
 * @param  string $order
 * @return string           Rendered output
 */
function cot_files_gallery($source, $item, $field = '', $tpl = 'files.gallery', $limit = 0, $order = '')
{
    return cot_files_display($source, $item, $field, $tpl, 'images', $limit, $order);
}

/**
 * Get current avatar
 * @param $file_id
 * @param array|int $urr
 * @param int $width
 * @param int $height
 * @param string $frame
 * @return string
 */
function cot_files_user_avatar($file_id = 0, $urr = 0, $width = 0, $height = 0, $frame = '')
{

    $avatar = cot_rc('files_user_default_avatar');
    if ($file_id == 0 && is_array($urr) && isset($urr['user_avatar'])) {
        $file_id = $urr['user_avatar'];
    }
    $url = cot_files_user_avatar_url($file_id, $width, $height, $frame = '');
    $alt = Cot::$L['Avatar'];
    if (is_array($urr)) {
        $alt = htmlspecialchars(cot_user_full_name($urr));
    }
    if ($url) {
        $avatar = cot_rc('files_user_avatar', array(
            'src'=> $url,
            'alt' => $alt,
        ));
    }
    return $avatar;
}

/**
 * @param $fileId
 * @param int $width
 * @param int $height
 * @param string $frame
 * @return string
 */
function cot_files_user_avatar_url($fileId, $width = 0, $height = 0, $frame = '')
{
    // -------
    // Если залито обновление модуля до версии 2.0.0-beta1, а таблицы в БД еще не обновлены, эта функция может ломать админку.
    // @todo убрать через несколько релизов
    global $cot_modules;
    if (version_compare($cot_modules['files']['version'], '2.0.0-beta1', '<')) {
        return '';
    }
    // -------

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

    if (empty($frame) || !in_array($frame, array('width', 'height', 'auto', 'crop', 'border_auto'))) {
        $frame = Cot::$cfg['files']['avatar_framing'];
    }

    if ($width <= 0)  {
        $width  = (int) Cot::$cfg['files']['avatar_width'];
    }

    if ($height <= 0) {
        $height = (int) Cot::$cfg['files']['avatar_height'];
    }

    return cot_files_thumb($file, $width, $height, $frame);

}

/**
 * Generates a file upload/edit widget.
 * Use it as CoTemplate callback.
 *
 * @param  string $source Target module/plugin code.
 * @param  integer $item Target item id.
 * @param  string $field Target item field
 * @param  string $tpl Template code
 * @param string $width
 * @param string $height
 * @return string           Rendered widget
 */
function cot_files_widget($source, $item, $field = '', $tpl = 'files.widget', $width = '100%', $height = '300')
{
    global $files_widget_present, $cot_modules;

    $t = new XTemplate(cot_tplfile($tpl, 'module'));

    // Metadata
    $limits = cot_files_getLimits(Cot::$usr['id'], $source, $item, $field);

    $urlParams = array('m'=>'files', 'a'=>'display', 'source'=>$source, 'item'=>$item, 'field'=>$field,
        'nc'=>$cot_modules['files']['version']);

    $t->assign(array(
        'FILES_SOURCE'  => $source,
        'FILES_ITEM'    => $item,
        'FILESH_FIELD'  => $field,
        'FILES_EXTS'    => preg_replace('#[^a-zA-Z0-9,]#', '', Cot::$cfg['files']['exts']),
//        'FILES_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',$cfg['plugin']['attach2']['accept']),
        'FILES_MAXSIZE' => $limits['size_maxfile'],
        'FILES_WIDTH'   => $width,
        'FILES_HEIGHT'  => $height,
        'FILES_URL'     => cot_url('files', $urlParams, '', true),
    ));

    $t->parse();

    $files_widget_present = true;

    return $t->text();
}

/**
 * TPL files not allows to execute php code
 * So it will be here
 *
 * @todo not needed with View template engine
 * @todo remove checking if method exists after Cotonti 0.9.20 release
 */
function cot_files_loadBootstrap()
{
    $ret = '<!-- 654654654 -->';

    $canCheck = method_exists('Resources', 'isFileAdded');

    //if(Resources::getAlias('@bootstrap.js') !== null) $ret = Resources::getAlias('@bootstrap.js');
    $cssAlias = Resources::getAlias('@bootstrap.css');
    if($cssAlias) {
        if (!$canCheck || !Resources::isFileAdded('@bootstrap.css')) {
            $ret .= cot_rc("code_rc_css_file", array(
                    'url' => Resources::getAlias('@bootstrap.css')
                )) . "\n";

        }
    }

    $themeAlias = Resources::getAlias('@bootstrapTheme.css');
    if($themeAlias) {
        if (!$canCheck || !Resources::isFileAdded('@bootstrapTheme.css')) {
            $ret .= cot_rc("code_rc_css_file", array(
                    'url' => Resources::getAlias('@bootstrapTheme.css')
                )) . "\n";
        }
    }

    return $ret;
}