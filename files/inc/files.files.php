<?php
defined('COT_CODE') or die('Wrong URL.');

/**
 * Files Controller class for the Files module
 *
 *  Функционал для работы с файлами, который не входит в стандартный jQuery Uploader
 * 
 * @package Files
 * @subpackage pfs
 * @author Cotonti Team
 * @copyright (c) Cotonti Team 2008-2014
 */
class FilesController
{
    public function displayAction() {
        $source = cot_import('source', 'G', 'ALP');
        $item = cot_import('item', 'G', 'INT');
        $field = (string) cot_import('field', 'G', 'TXT');
        $limit = cot_import('limit', 'G', 'INT');
        if (is_null($limit)) $limit = -1;
        $type = (string) cot_import('type', 'G', 'TXT');
        if(!$type) $type = 'all';

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

        $file = files_model_File::getById($id);
        if (!$file) {
            cot_die_message(404);
        }

        // Increase downloads counter
        $file->file_count += 1;
        $file->save();

        $filePath = Cot::$cfg['files']['folder'] . '/' . $file->file_path;

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
        header('Content-Disposition: attachment; filename="'.$file->file_name.'"');
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
        global $cot_extrafields;

        $response = array( 'error' => '');

        $extFields = $cot_extrafields[files_model_File::tableName()];

        $id = cot_import('id', 'P', 'INT');
        $field = cot_import('key', 'P', 'ALP');
        $value = cot_import('value', 'P', 'TXT');

        if(!$id || !$field){
            cot_sendheaders('application/json', cot_files_ajax_get_status(404));
            exit;
        }

        $file = files_model_File::getById($id);
        if (!$file) cot_files_ajax_die(404);

        // Можно изменить только title или что то из экстраполей
        if($field != 'file_title') {
            if(empty($extFields)) {
                cot_sendheaders('application/json', cot_files_ajax_get_status(404));
                exit;
            }
            $extfName = str_replace('file_', '', $field);
            if(!array_key_exists($extfName, $extFields)){
                cot_sendheaders('application/json', cot_files_ajax_get_status(404));
                exit;
            }
            $value = cot_import_extrafields($_POST['value'], $extFields[$extfName], 'D', $file->{$field});
        }

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        if(cot_error_found()) {
            $response['error'] = cot_implode_messages();
            cot_clear_messages();
            echo json_encode($response);
            exit;
        }


        if (!cot_auth('files', 'a', 'A') && $file->user_id != Cot::$usr['id']){
            $response['error'] = Cot::$L['files_err_perms'];
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
        if (!cot_auth('files', 'a', 'A') &&
            Cot::$db->query("SELECT COUNT(*) FROM $db_files WHERE file_source = ? AND file_item = ? AND user_id = ?",
                array($source, $item, Cot::$usr['id']))->fetchColumn() == 0)
        {
            $response['error'] = Cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        $orders = cot_import('orders', 'P', 'ARR');
        foreach ($orders as $order => $id){
            Cot::$db->update($db_files, array('file_order' => $order),
                "file_id = ? AND file_source = ? AND file_item = ? AND file_field = ? AND file_order != ?",
                array((int)$id, $source, $item, $field, $order));
        }

        $response['status'] = 1;

        echo json_encode($response);
        exit;
    }

    /**
     * Замена файла
     * @todo все отправить в UploadController::handle_image_file()
     * @see UploadController::handle_image_file()
     */
    public function replaceAction()
    {
        $id = cot_import('id', 'P', 'INT');

        $response = array( 'error' => '');

        if(!$id) {
            cot_sendheaders('application/json', cot_files_ajax_get_status(404));
            exit;
        }

        $file = files_model_File::getById($id);
        if (!$file) cot_files_ajax_die(404);

        $file->file_name = $this->getFilename('file');
        $file->file_ext = cot_files_get_ext($file->file_name);

        $limits = cot_files_getLimits(Cot::$usr['id'], $file->file_source, $file->file_item);
        $upload = $this->getUploadedFile('file', $limits);

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        //if (cot_files_checkFile($file->file_ext) && !cot_error_found()){
        if (cot_files_isExtensionAllowed($file->file_ext) && !cot_error_found()){
            if (!cot_auth('files', 'a', 'A') && $file->user_id != Cot::$usr['id']){
                $response['error'] = Cot::$L['files_err_perms'];
                echo json_encode($response);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $uploaded_file = $_FILES['file']['tmp_name'];
                if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                    $valid_exts = explode(',', Cot::$cfg['files']['exts']);
                    $valid_exts = array_map('trim', $valid_exts);

                    $handle = fopen($uploaded_file, "rb");
                    $tmp = fread ($handle , 10);
                    fclose($handle);
                    if (!in_array('php', $valid_exts) && (mb_stripos(trim($tmp), '<?php') === 0))  {
                        $response['error'] = Cot::$L['files_err_type'];
                        echo json_encode($response);
                        exit;
                    }

                }
            }

            $path = Cot::$cfg['files']['folder'] .'/'. $file->file_path;
            $file->remove_thumbs();
            if (file_exists($path)) {
                if (!@unlink($path)) {
                    $response['error'] = Cot::$L['files_err_replace'];
                    echo json_encode($response);
                    exit;
                }
            }

            $file->file_path = cot_files_path($file->file_source, $file->file_item, $file->file_id, $file->file_ext, $file->user_id);
            $filePath = Cot::$cfg['files']['folder'] .'/'. $file->file_path;

            if ($this->saveUploadedFile($upload, $filePath)) {
                $file->file_size = filesize($filePath);
                $file->file_img = cot_files_isValidImageFile($filePath) ? 1 : 0;

                if ($file->file_img) {
                    // @todo все отправить в handle_image_file()
                    // Image resize
                    if (Cot::$cfg['files']['image_resize']) {
                        list($width_orig, $height_orig) = getimagesize($filePath);
                        if ($width_orig > Cot::$cfg['files']['image_maxwidth'] || $height_orig > Cot::$cfg['files']['image_maxheight']) {
                            $input_file = $filePath;
                            $tmp_file   = $filePath.'tmp.'.$file->file_ext;
                            cot_files_thumbnail($input_file, $tmp_file, Cot::$cfg['files']['image_maxwidth'],
                                Cot::$cfg['files']['image_maxheight'], 'auto', (int) Cot::$cfg['files']['quality']);
                            @unlink($input_file);
                            @rename($tmp_file, $input_file);
                            $file->file_size = filesize($filePath);
                        }
                    }
                }

                $file->save();

            } else {
                $response['error'] = Cot::$L['files_err_move'];
                echo json_encode($response);
                exit;
            }
        } else {
            $messages = cot_get_messages();
            $errors = array();
            foreach ($messages as $msg) {
                $errors[] = isset(Cot::$L[$msg['text']]) ? Cot::$L[$msg['text']] : $msg['text'];
            }
            cot_clear_messages();

            if (empty($errors)) $errors[] = Cot::$L['error'];
            $response['error'] = implode(',', $errors);
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Returns original name of a file being uploaded
     * @param  string $input Input name
     * @return string        Original file name and extension
     */
    public function getFilename($input){
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            return $_FILES[$input]['name'];
        }
        else
        {
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
     * @return mixed         Uploaded file stream (for GET, PUT, etc.) or input name (only for POST)
     */
    public function getUploadedFile($input = '', $limits = false){
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            if ($_FILES[$input]['size'] > 0 && is_uploaded_file($_FILES[$input]['tmp_name'])){
                if ($_FILES[$input]['size'] > $limits['size_maxfile']){
                    cot_error(Cot::$L['files_err_toobig']);
                }
                if ($_FILES[$input]['size'] > $limits['size_left']){
                    cot_error(Cot::$L['files_err_nospace']);
                }

            }else{
                cot_error(Cot::$L['files_err_upload']);
            }
            return $input;

        } else {
            $input = fopen('php://input', 'r');
            $temp = '';
            while (!feof($input)) {
                $temp .= fread($input, att_get_filesize(''));
            }
            $temp = tmpfile();
            $size = stream_copy_to_stream($input, $temp);
            fclose($input);

            if (!$size){
                cot_error(Cot::$L['files_err_upload']);

            } else {
                if ($size > $limits['size_maxfile']){
                    cot_error(Cot::$L['files_err_toobig']);
                }

                if ($size > $limits['size_left']){
                    cot_error(Cot::$L['files_err_nospace']);
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
            if (file_exists($_FILES[$input]['tmp_name']) && file_exists($path) && is_writable($path)) {
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