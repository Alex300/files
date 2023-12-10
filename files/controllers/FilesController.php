<?php

namespace cot\modules\files\controllers;

use Cot;
use cot\modules\files\dto\FileDto;
use cot\modules\files\models\File;
use cot\modules\files\services\FileService;
use cot\modules\files\services\ThumbnailService;
use filesystem\exceptions\UnableToMoveFile;
use filesystem\LocalFilesystem;
use Throwable;

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

        $html = cot_filesFileBox($source, $item, $field, $type, $limit, 'files.files', 2);

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
        $contenttype = cot_filesGetMime($filePath);

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
            [$param, $range] = explode('=', $range);
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

        $extFields = Cot::$extrafields[File::tableName()];

        $id = cot_import('id', 'P', 'INT');
        $field = cot_import('key', 'P', 'ALP');
        $value = cot_import('value', 'P', 'TXT');

        if (!$id || !$field) {
            cot_ajaxResult(null, 404);
        }

        $file = File::getById($id);
        if (!$file) {
            cot_ajaxResult(null, 404);
        }

        // Можно изменить только title или что-то из экстраполей
        if ($field != 'title') {
            if (empty($extFields)) {
                cot_ajaxResult(null, 404);
            }
            $extfName = str_replace('file_', '', $field); // @todo
            if (!array_key_exists($extfName, $extFields)) {
                cot_ajaxResult(null, 404);
            }
            $value = cot_import_extrafields($_POST['value'], $extFields[$extfName], 'D', $file->{$field});
        }

        if (cot_error_found()) {
            $response['error'] = cot_implode_messages();
            cot_ajaxResult($response);
        }

        if (!cot_auth('files', 'a', 'A') && $file->user_id != Cot::$usr['id']){
            $response['error'] = Cot::$L['files_err_perms'];
            cot_ajaxResult($response);
        }

        $file->{$field} = $value;
        $file->save();

        $response['written'] = 1;
        cot_ajaxResult($response);
    }



    public function reorderAction()
    {
        global $db_files;

        $source = cot_import('source', 'P', 'ALP');
        $item = cot_import('item', 'P', 'INT');
        $field = cot_import('field', 'P', 'TXT');

        $response = array( 'error' => '');

        // Check permission
        if (
            !cot_auth('files', 'a', 'A')
            && Cot::$db->query(
                "SELECT COUNT(*) FROM $db_files WHERE source = ? AND source_id = ? AND user_id = ?",
                [$source, $item, Cot::$usr['id']]
            )->fetchColumn() == 0)
        {
            $response['error'] = Cot::$L['files_err_perms'];
            cot_ajaxResult($response);
        }

        $orders = cot_import('orders', 'P', 'ARR');
        foreach ($orders as $order => $id) {
            Cot::$db->update(
                $db_files,
                ['sort_order' => $order],
                "id = ? AND source = ? AND source_id = ? AND source_field = ? AND sort_order != ?",
                [(int) $id, $source, $item, $field, $order]
            );
        }

        $response['status'] = 1;
        cot_ajaxResult($response);
    }

    /**
     * Замена файла
     */
    public function replaceAction()
    {
        $id = cot_import('id', 'P', 'INT');

        $response = ['error' => ''];

        if (!$id) {
            cot_ajaxResult(null, 404);
        }

        $file = File::getById($id);
        if (!$file) {
            cot_ajaxResult(null, 404);
        }

        [Cot::$usr['auth_read'], Cot::$usr['auth_write'], Cot::$usr['isadmin']] = cot_auth('files', 'a');

        $fileData = new FileDto();
        $fileData->originalName = $this->getFilename('file');
        $fileData->ext = mb_strtolower(cot_filesGetExtension($fileData->originalName));
        $fileData->path = FileService::getTemporaryDirectory();
        $fileData->fileName = $file->source . '_' . $file->source_id . '_' . $file->id. '_' . Cot::$usr['id'] . '_' . time() . '_tmp.'
            . $fileData->ext;

        $limits = cot_filesGetLimits(\Cot::$usr['id'], $file->source, $file->source_id);
        $upload = $this->getUploadedFile('file', $limits);

        if (cot_error_found()) {
            $messages = cot_get_messages();
            $errors = [];
            foreach ($messages as $msg) {
                $errors[] = isset(Cot::$L[$msg['text']]) ? Cot::$L[$msg['text']] : $msg['text'];
            }
            cot_clear_messages();

            if (empty($errors)) {
                $errors[] = Cot::$L['error'];
            }
            $response['error'] = implode(',', $errors);
            cot_ajaxResult($response);
        }

        if (!cot_auth('files', 'a', 'A') && (int) $file->user_id !== (int) Cot::$usr['id']){
            $response['error'] = Cot::$L['files_err_perms'];
            cot_ajaxResult($response);
        }

        if (!$this->saveUploadedFile($upload, $fileData->getFullName())) {
            $response['error'] = Cot::$L['files_err_move'];
            cot_ajaxResult($response);
        }

        $validExts = explode(',', Cot::$cfg['files']['exts']);
        $validExts = array_map('trim', $validExts);
        if (!in_array('php', $validExts)) {
            $handle = fopen($fileData->getFullName(), "rb");
            $tmp = fread($handle, 10);
            fclose($handle);
            if (mb_stripos(trim($tmp), '<?php') === 0) {
                @unlink($fileData->getFullName());
                $response['error'] = Cot::$L['files_err_type'];
                cot_ajaxResult($response);
            }
        }

        if (Cot::$cfg['files']['fixExtensionsByMime']) {
            try {
                FileService::fixFileExtensionByDTO($fileData);
            } catch (Throwable $e) {
                $error = Cot::$L['files_err_upload'];
                if (Cot::$usr['isadmin']) {
                    $errorMessage = $e->getMessage();
                    if (!empty($errorMessage)) {
                        $error .= ': ' . $errorMessage;
                    }
                }
                if (file_exists($fileData->getFullName())) {
                    @unlink($fileData->getFullName());
                }
                $response['error'] = $error;
                cot_ajaxResult($response);
            }
        }

        if (!FileService::isExtensionAllowed($fileData->ext)) {
            if (file_exists($fileData->getFullName())) {
                @unlink($fileData->getFullName());
            }
            $response['error'] = Cot::$L['files_err_type'];
            cot_ajaxResult($response);

        }

        $fileData->size = filesize($fileData->getFullName());
        $fileData->mimeType = mime_content_type($fileData->getFullName());
        $fileData->isImage = cot_filesIsValidImageFile($fileData->getFullName()) ? 1 : 0;

        if ($fileData->isImage) {
            FileService::processImageFile($fileData);
        }

        if (!empty($fileData->getErrors())) {
            if (file_exists($fileData->getFullName())) {
                @unlink($fileData->getFullName());
            }
            $response['error'] = implode('; ', $fileData->getErrors());
            cot_ajaxResult($response);
        }

        $file->removeThumbnails();

        // Fill new data
        $file->size = $fileData->size;
        $file->ext = $fileData->ext;
        $file->is_img = $fileData->isImage;
        $file->original_name =  $fileData->originalName;
        $file->mime_type = $fileData->mimeType;

        // Until the file is sent to remote storage, we need to make a thumbnail
        $thumbnail = null;
        if ($file->is_img) {
            $thumbnail = ThumbnailService::thumbnail($file, 0, 0, '', true, $fileData->getFullName());
            if ($thumbnail) {
                $fileData->thumbnailUrl = $thumbnail['url'];
            }
        }

        // Local filesystem relative to upload directory
        $uploadDirFileSystem = new LocalFileSystem($fileData->path);
        $targetFileSystem = FileService::getFilesystemByName($file->filesystem_name);
        $oldFilePath = $file->getFullName();

        $relativeFileName = FileService::generateFileRelativePath($file);
        $file->path = dirname($relativeFileName);
        $file->file_name = basename($relativeFileName);

        try {
            if ($targetFileSystem instanceof LocalFileSystem) {
                // Save file locally
                $targetDirectory = dirname($relativeFileName);
                if (!$targetFileSystem->directoryExists($targetDirectory)) {
                    $targetFileSystem->createDirectory($targetDirectory);
                }
                // Path relative to site root directory
                $fileFullName = Cot::$cfg['files']['folder'] . '/' . $relativeFileName;
                if (!@rename($fileData->getFullName(), $fileFullName)) {
                    throw UnableToMoveFile::fromLocationTo($fileData->getFullName(), $fileFullName);
                }
            } else {
                // Upload to remote server
                $resource = $uploadDirFileSystem->readStream($fileData->getFullName());
                $targetFileSystem->writeStream($relativeFileName, $resource);
                fclose($resource);
                $uploadDirFileSystem->delete($fileData->getFullName());
            }
        } catch (Throwable $e) {
            // Fail to move file from temporary directory to the files directory
            // Delete temporary file
            @unlink($fileData->getFullName());
            unset($fileData->path, $fileData->fileName, $fileData->ext);
            $file->removeThumbnails();

            $error = Cot::$L['files_err_replace'];
            if (Cot::$usr['isadmin']) {
                $errorMessage = $e->getMessage();
                if (!empty($errorMessage)) {
                    $error .= ': ' . $errorMessage;
                }
            }
            $response = ['error' => $error];

            cot_ajaxResult($response);
        }

        // New file is uploaded. Let's delete the old one
        try {
            $targetFileSystem->delete($oldFilePath);
        } catch (Throwable $e) {
            // Can't delete old file. But the new one is uploaded successfully.
        }

        $file->save();

        $fileData->loadFromFile($file);
        $fileData->fileExists = true;
        $fileData->lastModified = Cot::$sys['now'];

        /* === Hook === */
        foreach (cot_getextplugins('files.replace.after_save') as $pl) {
            include $pl;
        }
        /* =========== */

        cot_ajaxResult($fileData->toArray());
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
     * @param  string  $path  Target path
     * @return boolean        true on success, false on error
     * @see FilesController::getUploadedFile()
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