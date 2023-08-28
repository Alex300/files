<?php

namespace cot\modules\files\controllers;

use cot\modules\files\dto\FileDto;
use cot\modules\files\model\File;
use cot\modules\files\services\FileService;

defined('COT_CODE') or die('Wrong URL.');

/**
 * Upload Controller class for the Files module
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */
class UploadController
{
    protected $options = [
        'input_stream' => 'php://input',

        // Use exif_imagetype on all files to correct file extensions.
        'correct_image_extensions' => true,

        // Add development info in output. Turn it off on production sites.
        'debug' => false,
    ];

    /**
     * UploadController constructor.
     * @param array $options
     */
    public function __construct($options = null, $initialize = true, $error_messages = null)
    {
        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    /**
     * @return string
     */
    public function indexAction()
    {
        switch ($this->get_server_var('REQUEST_METHOD')) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;

            case 'GET':
                $this->get();
                break;

            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post();
                break;

            case 'DELETE':
                $this->delete();
                break;

            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    /**
     * Fetches AJAX data for a given file or all files attached
     * @param bool $print_response
     * @return string Json encoded data
     */
    private function get($print_response = true)
    {
        $source = cot_import('source', 'R', 'ALP');
        $item = cot_import('item', 'R', 'INT');
        $field = (string) cot_import('field', 'R', 'TXT');

        $filename = cot_import('file', 'R', 'TXT');
        if (!is_null($filename)) {
            $filename = mb_basename(stripslashes($filename));
        }

        $uid = cot_import('uid', 'G', 'INT');
        if (is_null($uid)) {
            $uid = \Cot::$usr['id'];
        }

        $res = [];
        $condition = [
            'source' => ['source', $source],
            'sourceId' => ['source_id', $item],
            'sourceField' => ['source_field', $field],
        ];

        if ($source == 'pfs') {
            if ($item == 0) {
                $condition['user'] = ['user_id', $uid];
            }
        }

        if (!in_array($source, ['sfs', 'pfs']) && $item === 0) {
            $unikey = cot_import('unikey', 'G', 'TXT');
            if ($unikey) {
                $condition['unikey'] = ['unikey', $unikey];
            }
        }

        if (empty($filename)) {
            $multi = true;
            $files = File::findByCondition($condition, 0, 0, 'sort_order ASC');

        } else {
            $multi = false;
            $condition[] = ['original_name', $filename];
            $files = File::findByCondition($condition, 1);
        }
        if (!$files) {
            return $this->generateResponse([], $print_response);
        }

        foreach ($files as $file) {
            $fileData = FileDto::createFromFile($file);
            $fileFullName = \Cot::$cfg['files']['folder'] . '/' . $file->fullName;
            if (!file_exists($fileFullName)) {
                $fileData->addError('File is not exists');
            }

            if (!$multi) {
                return $this->generateResponse($fileData->toArray(), $print_response);
            }

            $res['files'][] = $fileData->toArray();
        }


        return $this->generateResponse($res, $print_response);
    }

    private function post($print_response = true)
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }

        $param_name = 'files';
        $upload = isset($_FILES[$param_name]) ? $_FILES[$param_name] : null;

        // Parse the Content-Disposition header, if available:
        $file_name = $this->get_server_var('HTTP_CONTENT_DISPOSITION') ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $this->get_server_var('HTTP_CONTENT_DISPOSITION')
            )) : null;

        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = $this->get_server_var('HTTP_CONTENT_RANGE') ?
            preg_split('/[^0-9]+/', $this->get_server_var('HTTP_CONTENT_RANGE')) : null;
        $size =  $content_range ? $content_range[3] : null;

        $files = [];
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $result = $this->handleFileUpload(
                    $upload['tmp_name'][$index],
                    $file_name ?: $upload['name'][$index],
                    $size ?: $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
                $files[] = $result->toArray();
            }

        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $result = $this->handleFileUpload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ?: (isset($upload['name']) ? $upload['name'] : null),
                $size ?: (isset($upload['size']) ? $upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
                isset($upload['type']) ? $upload['type'] : $this->get_server_var('CONTENT_TYPE'),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
            $files[] = $result->toArray();
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.upload.done') as $pl) {
            include $pl;
        }
        /* ===== */

        return $this->generateResponse(
            array($param_name => $files),
            $print_response
        );
    }

    /**
     * Ajax delete file
     * @param bool $print_response
     */
    public function delete($print_response = true)
    {
        $res = array(
            'success' => false
        );
        $id = cot_import('id', 'R', 'INT');
        if (!$id) {
            $this->generateResponse($res, $print_response);
            exit();
        }

        $file = File::getById($id);
        if (!$file) {
            $this->generateResponse($res, $print_response);
            exit();
        }
        if ($file->user_id != \Cot::$usr['id'] && !cot_auth('files', 'a', 'A')) {
            $this->generateResponse($res, $print_response);
            exit();
        }

        $res['success'] = $file->delete();

        $this->generateResponse($res, $print_response);
        exit;
    }

    /**
     * Returns the number of files already attached to an item
     * @param  string $source Target module/plugin code.
     * @param  integer $item Target item id.
     * @param  string $field
     * @return integer
     */
    protected function count_file_objects($source, $item, $field = '_all_')
    {
        $condition = [
            ['source', $source],
            ['source_id', $item],
        ];
        if ($field != '_all_') {
            $condition[] = ['source_field', $field];
        }

        return File::count($condition);
    }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    protected function generateResponse($content, $print_response = true)
    {
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect'])
                ? stripslashes($_REQUEST['redirect'])
                : null;
            if ($redirect) {
                header('Location: ' . sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
                $files = isset($this->options['param_name']) && isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    header('Range: 0-'.(
                            $this->fix_integer_overflow(intval($files[0]->size)) - 1
                        ));
                }
            }
            echo ($json);
        }
        return $content;
    }

    protected function get_file_name($file_path, $name, $source, $item, $type, $content_range)
    {
        $name = $this->trim_file_name($file_path, $name, $type);
        $tmp = cot_files_safeName($source . '_' . $item . '_' . $this->fix_file_extension($file_path, $name, $type));

        return $this->get_unique_filename($tmp, $content_range);
    }

    protected function get_file_size($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            clearstatcache(true, $file_path);
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }

    protected function get_unique_filename($name, $content_range)
    {
        $tmpDir = cot_files_tempDir() . DIRECTORY_SEPARATOR;
        while(is_dir($tmpDir.$name)) {
            $name = $this->upcount_name($name);
        }

        // Keep an existing filename if this is part of a chunked upload:
        $tmp = isset($content_range[1]) ? intval($content_range[1]) : 0;
        $uploaded_bytes = $this->fix_integer_overflow($tmp);
        while (is_file($tmpDir . $name)) {
            if ($uploaded_bytes === $this->get_file_size($tmpDir . $name)) {
                break;
            }
            $name = $this->upcount_name($name);
        }

        return $name;
    }

    /**
     * @deprecated
     */
    protected function get_upload_path($source, $item)
    {
        return \Cot::$cfg['files']['folder'] . '/' . $source . '/' . $item;
    }

    protected function get_server_var($id)
    {
        return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
    }

    /**
     * Обработка загрузки файла
     * @param string $uploaded_file Uploaded full filename (temp name)
     * @param string $name Original filename
     * @param int $size
     * @param string $type Mime type
     * @param int $error Upload Error code
     * @param int $index
     * @param array|null $content_range
     * @return FileDto
     *
     * @todo если пришел uid и пользователь админ, то сохранять файлы от пользователя с указанным uid
     */
    protected function handleFileUpload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
    {
        $source = cot_import('source', 'R', 'ALP');
        $item = cot_import('item', 'R', 'INT');
        $field = (string) cot_import('field', 'R', 'TXT');

        $params = cot_import('param', 'R', 'HTM');
        if (!empty($params)) {
            $params = unserialize(base64_decode($params));
        }

        $fileData = new FileDto();
        $fileData->original_name = trim(mb_basename(stripslashes($name)));
        $fileData->file_name = $this->get_file_name($uploaded_file, $name, $source, $item, $type, $content_range);
        $fileData->ext = mb_strtolower(cot_files_get_ext($fileData->file_name));
        $fileData->size = $this->fix_integer_overflow((int) $size);
        $fileData->mimeType = $type;

        if ($this->options['debug']) {
            $fileData->addDebug('uploaded_file', $uploaded_file);
            $fileData->addDebug('upload_dir', cot_files_tempDir());
        }

        list(\Cot::$usr['auth_read'], \Cot::$usr['auth_write'], \Cot::$usr['isadmin']) = cot_auth('files', 'a');

        if (!$this->preValidate($uploaded_file, $fileData, $source, $item, $field, $params, $error)) {
            if (empty($fileData->getErrors())) {
                $fileData->addError(\Cot::$L['files_err_unknown']);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            return $fileData;
        }

        $uploadDir = cot_files_tempDir();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, \Cot::$cfg['dir_perms'], true);
        }

        $file_path = $uploadDir. '/' . $fileData->file_name;

        $append_file = $content_range && is_file($file_path) && $fileData->size > $this->get_file_size($file_path);
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            // multipart/formdata uploads (POST method uploads)
            if ($append_file) {
                file_put_contents(
                    $file_path,
                    fopen($uploaded_file, 'r'),
                    FILE_APPEND
                );

            } else {
                move_uploaded_file($uploaded_file, $file_path);
            }

        } else {
            // Non-multipart uploads (PUT method support)
            file_put_contents(
                $file_path,
                fopen($this->options['input_stream'], 'r'),
                $append_file ? FILE_APPEND : 0
            );
        }

        $file_size = $this->get_file_size($file_path, $append_file);

        // Fist of all we need memory to process this file
        // 2 MB for other script processing
        if (!cot_memory_allocate($file_size + 2097152)) {
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            return $fileData;
        }

        // File is not fully uploaded
        if ($file_size !== $fileData->size) {
            $fileData->size = $file_size;
//            if (!$content_range && $this->options['discard_aborted_uploads']) {
            if (!$content_range) {
                unlink($file_path);
                $fileData->addError(\Cot::$L['files_err_abort']);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            return $fileData;
        }

        // File is uploaded
        $fileData->setFullName($file_path);
        $fileData->isImage = cot_files_isValidImageFile($file_path);

        // Validate uploaded file
        if (!$this->validate($fileData) || !empty($fileData->getErrors())) {
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            return $fileData;
        }

        if ($fileData->isImage) {
            FileService::processImageFile($fileData);
        }
        if (!empty($fileData->getErrors())) {
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            return $fileData;
        }

        $uid = \Cot::$usr['id'];
        if (\Cot::$usr['isadmin']) {
            $uid = cot_import('uid', 'G', 'INT');
            if (is_null($uid)) {
                $uid = \Cot::$usr['id'];
            }
        }

        // Saving
        $objFile = new File();
        $objFile->original_name = $fileData->original_name;
        $objFile->user_id = $uid;
        $objFile->source = $source;
        $objFile->source_id = $item;
        $objFile->source_field = $field;
        $objFile->ext = $fileData->ext;
        $objFile->is_img = $fileData->isImage ? 1 : 0;
        $objFile->size = $fileData->size;

        if (!in_array($source, ['sfs', 'pfs']) && $item == 0) {
            $unikey = cot_import('unikey', 'G', 'TXT');
            if ($unikey) {
                $objFile->unikey = $unikey;
            }
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.upload.before_save') as $pl) {
            include $pl;
        }
        /* ===== */

        if (!($id = $objFile->save())) {
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            $fileData->addError(\Cot::$L['files_err_upload']);
            return $fileData;
        }

        // Path relative to files root directory
        $relativeFileName = cot_files_path($source, $item, $objFile->id, $objFile->ext, $objFile->user_id);
        $objFile->path = dirname($relativeFileName);
        $objFile->file_name = basename($relativeFileName);

        // Path relative to site root directory
        $fileFullName = \Cot::$cfg['files']['folder'] . '/' . $relativeFileName;
        $file_dir = dirname($fileFullName);

        if (!is_dir($file_dir)) {
            mkdir($file_dir, \Cot::$cfg['dir_perms'], true);
        }
        if (!@rename($fileData->getFullName(), $fileFullName)) {
            // Fail to move file from temporary directory to the files directory
            // Delete temporary file
            @unlink($fileData->getFullName());
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            if (!$this->options['debug'] && !empty($fileData->getDebug())) {
                $fileData->clearDebug();
            }
            $fileData->addError(\Cot::$L['files_err_upload']);
            $objFile->delete();
            return $fileData;
        }
        $objFile->save();

        // Avatar support
        if (!empty($params['avatar']) && $objFile->is_img && $objFile->source === 'pfs') {
            $objFile->makeAvatar();
        }

        // Дозаполнить DTO
        $fileData->loadFromFile($objFile);

        /* === Hook === */
        foreach (cot_getextplugins('files.upload.after_save') as $pl) {
            include $pl;
        }
        /* ===== */

        if (!$this->options['debug'] && !empty($fileData->getDebug())) {
            $fileData->clearDebug();
        }

        return $fileData;
    }

    public function head()
    {
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        header('X-Content-Type-Options: nosniff');
//        if ($this->options['access_control_allow_origin']) {
//            $this->send_access_control_headers();
//        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

        $this->send_content_type_header();
    }

    protected function upcount_name_callback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' ('.$index.')'.$ext;
    }

    /**
     * @todo использовать лямбда функцию вместо callbacka
     */
    protected function upcount_name($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }

    protected function send_content_type_header()
    {
        header('Vary: Accept');
        if (strpos($this->get_server_var('HTTP_ACCEPT'), 'application/json') !== false) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
    }

    /**
     *  Remove path information and dots around the filename, to prevent uploading
     *  into different directories or replacing hidden system files.
     *  Also remove control characters and spaces (\x00..\x20) around the filename:
     *
     * @param $file_path
     * @param $name
     * @param $type
     *
     * @return mixed|string
     */
    protected function trim_file_name($file_path, $name, $type)
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");

        $valid_exts = explode(',', \Cot::$cfg['files']['exts']);
        $valid_exts = array_map('trim', $valid_exts);
        if(!in_array('php', $valid_exts)) str_replace('.php.', '.pp.', $name);

        // Use a timestamp for empty filenames:
        if (!$name) $name = str_replace('.', '-', microtime(true));

        return $name;
    }

    protected function fix_file_extension($file_path, $name, $type)
    {
        // Add missing file extension for known image types:
        if (strpos($name, '.') === false && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $name .= '.'.$matches[1];
        }

        if ($this->options['correct_image_extensions'] && function_exists('exif_imagetype')) {
            switch (@exif_imagetype($file_path)){
                case IMAGETYPE_JPEG:
                    $extensions = array('jpg', 'jpeg');
                    break;

                case IMAGETYPE_PNG:
                    $extensions = array('png');
                    break;

                case IMAGETYPE_GIF:
                    $extensions = array('gif');
                    break;
            }

            // Adjust incorrect image file extensions:
            if (!empty($extensions)) {
                $parts = explode('.', $name);
                $extIndex = count($parts) - 1;
                $ext = strtolower(@$parts[$extIndex]);
                if (!in_array($ext, $extensions)) {
                    $parts[$extIndex] = $extensions[0];
                    $name = implode('.', $parts);
                }
            }
        }
        return $name;
    }

    /**
     * Preliminary file validation
     *
     * At upload stage, chunk
     *
     * @param string $uploaded_file Uploaded full filename (temp name)
     * @param FileDto $file
     * @param string $source
     * @param string $item
     * @param string $field
     * @param array $params
     * @param int|string $error
     * @return bool
     */
    protected function preValidate($uploaded_file, FileDto $file, $source, $item, $field, $params, $error)
    {
        if (!cot_auth('files', 'a', 'W')) {
            $file->addError(\Cot::$L['files_err_perms']);
            return false;
        }

        if ($error) {
            if (is_int($error)) {
                $file->addError(
                    isset(FileService::$fileUploadErrors[$error]) ? FileService::$fileUploadErrors[$error] : \Cot::$L['files_err_unknown']
                );
            } else {
                $file->addError($error);
            }
            return false;
        }
        if (empty($file->file_name)) {
            $file->addError('missingFileName');
            return false;
        }

        $file_ext = cot_files_get_ext($file->file_name);
        if (!cot_files_isExtensionAllowed($file_ext)) {
            $file->addError(\Cot::$L['files_err_type']);
            return false;
        }

        $content_length = $this->fix_integer_overflow(
            (int) $this->get_server_var('CONTENT_LENGTH')
        );

        /** @var int $file_size current file size (chunk size) */
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            if ($this->options['debug']) {
                $file->addDebug('is_uploaded_file', true);
            }
            $file_size = $this->get_file_size($uploaded_file);

        } else {
            $file_size = $content_length;
        }

        if ($this->options['debug']) {
            $file->addDebug('uploaded_file', $uploaded_file);
            $file->addDebug('current_file_size', $file_size);
            $file->addDebug('file_size', $file->size);
        }

        $limits = cot_files_getLimits(\Cot::$usr['id'], $source, $item);
        if ($file_size > $limits['size_maxfile'] || $file->size > $limits['size_maxfile']) {
            $file->addError(\Cot::$L['files_err_toobig']);
            return false;
        }

        if ($file_size > $limits['size_left'] || $file->size > $limits['size_left']) {
            $file->addError(\Cot::$L['files_err_nospace']);
            return false;
        }

        if (!isset($params['field'])) {
            $params['field'] = $field;
        }
        if (!isset($params['limit'])) {
            if ($limits['count_left'] == 0) {
                $file->addError(\Cot::$L['files_err_count']);
                return false;
            }
        } else {
            // Это не касается несуществующих объектов
            if ($item > 0 && $params['limit'] > 0 && ($this->count_file_objects($source, $item, $params['field']) >= $params['limit'])) {
                $file->addError(\Cot::$L['files_err_count']);
                return false;
            }
        }

        return true;
    }

    /**
     * Uploaded file validation
     * @param FileDto $file
     * @return bool
     *
     * @todo validate mime-type
     */
    protected function validate(FileDto $file)
    {
        /* === Hook === */
        foreach (cot_getextplugins('files.upload.before.validate') as $pl) {
            include $pl;
        }
        /* ===== */

        $valid_exts = explode(',', \Cot::$cfg['files']['exts']);
        $valid_exts = array_map('trim', $valid_exts);

        $handle = @fopen($file->getFullName(), "rb");
        if ($handle === false) {
            $file->addError(\Cot::$usr['isadmin'] ? 'Can\'t open file: "' . $file->getFullName() . '"' : 'Can\'t open file');
            return false;
        }
        $tmp = fread($handle, 10);
        fclose($handle);
        if (!in_array('php', $valid_exts) && (mb_stripos(trim($tmp), '<?php') === 0)) {
            $file->addError(\Cot::$L['files_err_type']);
            return false;
        }
        unset($tmp);

        $mime = cot_files_getMime($file->getFullName());

        if ($this->options['debug']) {
            $file->addDebug('mimeType', $mime);
        }

        $params = cot_import('param', 'R', 'HTM');
        if (!empty($params)) {
            $params = unserialize(base64_decode($params));
            if (!empty($params['type'])) {
                $params['type'] = json_decode($params['type']);
                $typeOk = false;
                if (in_array('all' , $params['type'])) {
                    $typeOk = true;

                } elseif (in_array('image' , $params['type']) && cot_files_isValidImageFile($file->getFullName())) {
                    $typeOk = true;

                } elseif (in_array('video' , $params['type']) && mb_stripos($mime, 'video') !== false) {
                    $typeOk = true;

                } elseif (in_array('audio' , $params['type']) && mb_stripos($mime, 'audio') !== false) {
                    $typeOk = true;
                }

                if (!$typeOk) {
                    $file->addError(\Cot::$L['files_err_type']);
                    return false;
                }
            }
        }

        $result = true;

        /* === Hook === */
        foreach (cot_getextplugins('files.upload.validate') as $pl) {
            include $pl;
        }
        /* ===== */

        return $result;
    }
}
