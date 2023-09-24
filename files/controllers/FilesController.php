<?php

namespace cot\modules\files\controllers;

use cot\modules\files\dto\FileDto;
use cot\modules\files\model\File;
use cot\modules\files\services\FileService;
use image\Image;

defined('COT_CODE') or die('Wrong URL.');

/**
 * Files Controller class for the Files module
 *
 * Функционал для работы с файлами, который не входит в стандартный jQuery Uploader
 * 
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */
class FilesController
{
    public function displayAction()
    {
        $source = cot_import('source', 'G', 'ALP');
        $item = cot_import('item', 'G', 'INT');
        $field = (string) cot_import('field', 'G', 'TXT');
        $limit = cot_import('limit', 'G', 'INT');
        if (is_null($limit)) {
            $limit = -1;
        }
        $type = (string) cot_import('type', 'G', 'TXT');
        if (!$type) {
            $type = 'all';
        }

        $html = cot_files_filebox($source, $item, $field, $type, $limit, 'files.files', 2);

        echo $html;
        exit;
    }

    /**
     * File download
     */
    public function downloadAction()
    {
        $id = cot_import('id', 'G', 'INT');
        if (!$id) {
            cot_die_message(404);
        }

        $file = File::getById($id);
        if (!$file) {
            cot_die_message(404);
        }

        // Increase downloads counter
        $file->downloads_count += 1;
        $file->save();

        $filePath = \Cot::$cfg['files']['folder'] . '/' . $file->fullName;

        // Detect MIME type if possible
        $contenttype = cot_files_getMime($filePath);

        // Avoid sending unexpected errors to the client - we should be serving a file,
        // we don't want to corrupt the data we send
        @error_reporting(0);

        // Clear and disable output buffer
        while (ob_get_level() > 0){
            ob_end_clean();
        }

        // Make sure the files exists, otherwise we are wasting our time
        if (!file_exists($filePath)) {
            $file->delete();
            cot_die_message(404);
        }

        // Get the 'Range' header if one was sent
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE']; // IIS/Some Apache versions

        } elseif (function_exists('apache_request_headers') && $apache = apache_request_headers()) {
            // Try Apache again
            $headers = array();
            foreach ($apache as $header => $val) $headers[strtolower($header)] = $val;
            if (isset($headers['range'])) {
                $range = $headers['range'];
            } else {
                // We can't get the header/there isn't one set
                $range = FALSE;
            }

        } else {
            // We can't get the header/there isn't one set
            $range = FALSE;
        }

        // Get the data range requested (if any)
        $filesize = filesize($filePath);
        if ($range) {
            $partial = true;
            list($param, $range) = explode('=', $range);
            if (strtolower(trim($param)) != 'bytes') {
                // Bad request - range unit is not 'bytes'
                cot_die_message(400);
            }
            $range = explode(',', $range);
            $range = explode('-', $range[0]); // We only deal with the first requested range
            if (count($range) != 2) {
                // Bad request - 'bytes' parameter is not valid
                cot_die_message(400);
            }

            if ($range[0] === '') {
                // First number missing, return last $range[1] bytes
                $end = $filesize - 1;
                $start = $end - intval($range[0]);
            } elseif ($range[1] === '') {
                // Second number missing, return from byte $range[0] to end
                $start = intval($range[0]);
                $end = $filesize - 1;
            } else {
                // Both numbers present, return specific range
                $start = intval($range[0]);
                $end = intval($range[1]);
                if ($end >= $filesize || (!$start && (!$end || $end == ($filesize - 1)))) {
                    // Invalid range/whole file specified, return whole file
                    $partial = false;
                }
            }
            $length = $end - $start + 1;

        } else {
            // No range requested
            $partial = false;
        }

        // Send standard headers
        header("Content-Type: $contenttype");
        header("Content-Length: $filesize");
        header('Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($filePath)));
        header('Content-Disposition: attachment; filename="' . $file->original_name . '"');
        header('Accept-Ranges: bytes');

        if ($partial) {
            // if requested, send extra headers and part of file...
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$filesize");
            if (!$fp = fopen($filePath, 'r')) {
                // Error out if we can't read the file
                cot_die_message(500);
            }

            if ($start) {
                fseek($fp,$start);
            }

            while ($length) {
                // Read in blocks of 8KB so we don't chew up memory on the server
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                echo fread($fp,$read);
            }

            fclose($fp);
        } else {
            // ...otherwise just send the whole file
            readfile($filePath);
        }

        exit();
    }

    /**
     * Update field value via Ajax
     */
    public function updateValueAction()
    {
        $response = ['error' => ''];

        $extFields = \Cot::$extrafields[File::tableName()];

        $id = cot_import('id', 'P', 'INT');
        $field = cot_import('key', 'P', 'ALP');
        $value = cot_import('value', 'P', 'TXT');

        if (!$id || !$field) {
            cot_sendheaders('application/json', cot_files_ajax_get_status(404));
            exit;
        }

        $file = File::getById($id);
        if (!$file) {
            cot_files_ajax_die(404);
        }

        // Можно изменить только title или что-то из экстраполей
        if ($field != 'title') {
            if (empty($extFields)) {
                cot_sendheaders('application/json', cot_files_ajax_get_status(404));
                exit;
            }
            $extfName = str_replace('file_', '', $field); // @todo
            if (!array_key_exists($extfName, $extFields)) {
                cot_sendheaders('application/json', cot_files_ajax_get_status(404));
                exit;
            }
            $value = cot_import_extrafields($_POST['value'], $extFields[$extfName], 'D', $file->{$field});
        }

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        if (cot_error_found()) {
            $response['error'] = cot_implode_messages();
            cot_clear_messages();
            echo json_encode($response);
            exit;
        }


        if (!cot_auth('files', 'a', 'A') && $file->user_id != \Cot::$usr['id']){
            $response['error'] = \Cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        $file->{$field} = $value;
        $file->save();

        $response['written'] = 1;

        echo json_encode($response);
        exit;
    }



    public function reorderAction()
    {
        global $db_files;

        $source = cot_import('source', 'P', 'ALP');
        $item = cot_import('item', 'P', 'INT');
        $field = cot_import('field', 'P', 'TXT');

        $response = array( 'error' => '');

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        // Check permission
        if (
            !cot_auth('files', 'a', 'A')
            && \Cot::$db->query(
                "SELECT COUNT(*) FROM $db_files WHERE source = ? AND source_id = ? AND user_id = ?",
                [$source, $item, \Cot::$usr['id']]
            )->fetchColumn() == 0)
        {
            $response['error'] = \Cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        $orders = cot_import('orders', 'P', 'ARR');
        foreach ($orders as $order => $id) {
            \Cot::$db->update(
                $db_files,
                ['sort_order' => $order],
                "id = ? AND source = ? AND source_id = ? AND source_field = ? AND sort_order != ?",
                [(int) $id, $source, $item, $field, $order]
            );
        }

        $response['status'] = 1;

        echo json_encode($response);
        exit;
    }

    /**
     * Замена файла
     */
    public function replaceAction()
    {
        $id = cot_import('id', 'P', 'INT');

        $response = ['error' => ''];

        if (!$id) {
            cot_files_ajax_die(404);
        }

        $file = File::getById($id);
        if (!$file) {
            cot_files_ajax_die(404);
        }

        $fileData = new FileDto();
        $fileData->original_name = $this->getFilename('file');
        $fileData->ext = mb_strtolower(cot_files_get_ext($fileData->original_name));
        $fileData->path = cot_files_tempDir();
        $fileData->file_name = $file->source . '_' . $file->source_id . '_' . $file->id. '_' . \Cot::$usr['id'] . '_' . time() . '_tmp.'
            . $fileData->ext;

        $limits = cot_files_getLimits(\Cot::$usr['id'], $file->source, $file->source_id);
        $upload = $this->getUploadedFile('file', $limits);

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        if (!cot_files_isExtensionAllowed($fileData->ext)) {
            cot_error(\Cot::$L['files_err_type']);
        }

        if (cot_error_found()) {
            $messages = cot_get_messages();
            $errors = [];
            foreach ($messages as $msg) {
                $errors[] = isset(\Cot::$L[$msg['text']]) ? \Cot::$L[$msg['text']] : $msg['text'];
            }
            cot_clear_messages();

            if (empty($errors)) {
                $errors[] = \Cot::$L['error'];
            }
            $response['error'] = implode(',', $errors);

            echo json_encode($response);
            exit;
        }

        if (!cot_auth('files', 'a', 'A') && $file->user_id != \Cot::$usr['id']){
            $response['error'] = \Cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        if (!$this->saveUploadedFile($upload, $fileData->getFullName())) {
            $response['error'] = \Cot::$L['files_err_move'];
            echo json_encode($response);
            exit;
        }

        $validExts = explode(',', \Cot::$cfg['files']['exts']);
        $validExts = array_map('trim', $validExts);
        if (!in_array('php', $validExts)) {
            $handle = fopen($fileData->getFullName(), "rb");
            $tmp = fread($handle, 10);
            fclose($handle);
            if (mb_stripos(trim($tmp), '<?php') === 0) {
                @unlink($fileData->getFullName());
                $response['error'] = \Cot::$L['files_err_type'];
                echo json_encode($response);
                exit;
            }
        }

        // @todo Fix File extension

        $fileData->size = filesize($fileData->getFullName());
        $fileData->isImage = cot_files_isValidImageFile($fileData->getFullName()) ? 1 : 0;

        if ($fileData->isImage) {
            FileService::processImageFile($fileData);
        }
        if (!empty($fileData->getErrors())) {
            if (file_exists($fileData->getFullName())) {
                unlink($fileData->getFullName());
            }
            $response['error'] = implode('; ', $fileData->getErrors());
            echo json_encode($response);
            exit;
        }

        $path = \Cot::$cfg['files']['folder'] . '/' . $file->fullName;
        $file->removeThumbnails();
        if (file_exists($path)) {
            if (!@unlink($path)) {
                @unlink($fileData->getFullName());
                $response['error'] = \Cot::$L['files_err_replace'];
                echo json_encode($response);
                exit;
            }
        }

        // Fill new data
        $file->size = $fileData->size;
        $file->ext = $fileData->ext;
        $file->is_img = $fileData->isImage;
        $file->original_name =  $fileData->original_name;

        $relativeFileName = FileService::generateFileRelativePath($file);
        // Path relative to site root directory
        $fileFullName = \Cot::$cfg['files']['folder'] . '/' . $relativeFileName;

        if (!@rename($fileData->getFullName(), $fileFullName)) {
            // Fail to move file from temporary directory to the files directory
            // Delete temporary file
            @unlink($fileData->getFullName());
            unset($fileData->path, $fileData->file_name, $fileData->ext);
            $response['error'] = \Cot::$L['files_err_replace'];
            echo json_encode($response);
            exit;
        }

        $fileData->path = dirname($fileFullName);
        $fileData->file_name = basename($fileFullName);

        $file->path = dirname($relativeFileName);
        $file->file_name = $fileData->file_name;
        $file->save();

        echo json_encode($fileData->toArray());
        exit;
    }

    /**
     * Returns original name of a file being uploaded
     * @param string $input Input name
     * @return string Original file name and extension
     */
    public function getFilename($input){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return $_FILES[$input]['name'];
        } else {
            return $_GET[$input];
        }
    }

    /**
     * Returns size of a file being uploaded
     * @param  string $input Input name
     * @return integer       File size in bytes
     *
     * @deprecated
     */
    public function att_get_filesize($input)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            return $_FILES[$input]['size'];
        }
        else
        {
            return (int) $_SERVER['CONTENT_LENGTH'];
        }
    }

    /**
     * Checks if the file has been uploaded and the size is
     * acceptable and returns the file stream if necessary.
     * @param  string $input Input name (only for POST)
     * @return false|resource|string Uploaded file stream (for GET, PUT, etc.) or input name (only for POST)
     */
    public function getUploadedFile($input = '', $limits = false)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            if ($_FILES[$input]['size'] > 0 && is_uploaded_file($_FILES[$input]['tmp_name'])){
                if ($_FILES[$input]['size'] > $limits['size_maxfile']){
                    cot_error(\Cot::$L['files_err_toobig']);
                }
                if ($_FILES[$input]['size'] > $limits['size_left']){
                    cot_error(\Cot::$L['files_err_nospace']);
                }

            } else {
                cot_error(\Cot::$L['files_err_upload']);
            }
            return $input;

        } else {
            $input = fopen('php://input', 'r');
//            $temp = '';
//            while (!feof($input)) {
//                $temp .= fread($input, att_get_filesize(''));
//            }
            $temp = tmpfile();
            $size = stream_copy_to_stream($input, $temp);
            fclose($input);

            if (!$size) {
                cot_error(\Cot::$L['files_err_upload']);

            } else {
                if ($size > $limits['size_maxfile']){
                    cot_error(\Cot::$L['files_err_toobig']);
                }

                if ($size > $limits['size_left']){
                    cot_error(\Cot::$L['files_err_nospace']);
                }
            }
            return $temp;
        }
    }

    /**
     * Saves an uploaded file regardless of request method.
     * @param  string   $input A value returned by FilesController::getUploadedFile
     * @see FilesController::getUploadedFile()
     * @param  string  $path  Target path
     * @return boolean        true on success, false on error
     *
     * @todo убедиться, что не остается мусора, если файл залит не через $_POST
     */
    protected  function saveUploadedFile($input, $path)
    {
        if (cot_error_found()) {
            return false;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $directory = dirname($path);
            if (file_exists($_FILES[$input]['tmp_name']) && is_writable($directory)) {
                return move_uploaded_file($_FILES[$input]['tmp_name'], $path);
            }
            return false;

        } else {
            $target = fopen($path, 'w');
            if (!$target) {
                return false;
            }
            fseek($input, 0, SEEK_SET);
            stream_copy_to_stream($input, $target);
            fclose($target);
            return true;
        }
    }
}