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
class FilesController{

    /**
     * @todo generate formUnikey
     */
    public function displayAction(){

        $source = cot_import('source', 'G', 'ALP');
        $item = cot_import('item', 'G', 'INT');
        $field = (string)cot_import('field', 'G', 'TXT');
        $limit = cot_import('limit', 'G', 'INT');
        $type = (string)cot_import('type', 'G', 'TXT');

        $formId = "{$source}_{$item}_{$field}";

        $t = new XTemplate(cot_tplfile('files.files', 'module'));

        // Metadata
        $limits = cot_files_getLimits(cot::$usr['id'], $source, $item, $field);
        if($limit == 0){
            $limit = 100000000000000000;
        }elseif($limit == -1){
            $limit = $limits['count_max'];
        }

        $type = str_replace(' ', '', $type);
        if(empty($type)){
            $type = array('all');
        }else{
            $type = explode(',', $type);
        }
        $type = json_encode($type);

        $tpl = new XTemplate(cot_tplfile('files.templates.widget', 'module'));
        $tpl->parse();

        $unikey = mb_substr(md5($formId . '_' . rand(0, 99999999)), 0, 15);
        $params = base64_encode(serialize(array(
            'source'  => $source,
            'item'    => $item,
            'field'   => $field,
            'limit'   => $limit,
            'type'    => $type,
            'unikey'  => $unikey
        )));

        $action = 'index.php?e=files&m=upload&source='.$source.'&item='.$item;
        if(!empty($field)) $action .= '&field='.$field;

        $t->assign(array(
            'UPLOAD_ID'      => $formId,
            'UPLOAD_SOURCE'  => $source,
            'UPLOAD_ITEM'    => $item,
            'UPLOAD_FIELD'   => $field,
            'UPLOAD_LIMIT'   => $limit,
            'UPLOAD_TYPE'    => $type,
            'UPLOAD_PARAM'   => $params,
            'UPLOAD_CHUNK'   => (int)cot::$cfg['files']['chunkSize'],
            'UPLOAD_EXTS'    => preg_replace('#[^a-zA-Z0-9,]#', '', cot::$cfg['files']['exts']),
//        'UPLOAD_ACCEPT'  => preg_replace('#[^a-zA-Z0-9,*/-]#', '',cot::$cfg['plugin']['attach2']['accept']),
            'UPLOAD_MAXSIZE' => $limits['size_maxfile'],
            'UPLOAD_ACTION'  => $action,
            'UPLOAD_X'       => cot::$sys['xk'],
        ));

        $t->parse();
        $t->out();
        exit;
    }

    public function updateTitleAction(){
        $id = cot_import('id', 'P', 'INT');

        $response = array( 'error' => '');

        if(!$id){
            cot_sendheaders('application/json', cot_files_ajax_get_status(404));
            exit;
        }

        $file = files_model_File::getById($id);
        if (!$file) cot_files_ajax_die(404);

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        if (!cot_auth('files', 'a', 'A') && $file->user_id != cot::$usr['id']){
            $response['error'] = cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        $file->file_title = cot_import('title', 'P', 'TXT');
        $file->save();

        $response['written'] = 1;

        echo json_encode($response);
        exit;
    }

    public function reorderAction(){
        global $db_files;

        $source = cot_import('source', 'P', 'ALP');
        $item = cot_import('item', 'P', 'INT');
        $field = cot_import('field', 'P', 'TXT');

        $response = array( 'error' => '');

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        // Check permission
        if (!cot_auth('files', 'a', 'A') &&
            cot::$db->query("SELECT COUNT(*) FROM $db_files WHERE file_source = ? AND file_item = ? AND user_id = ?",
                array($source, $item, cot::$usr['id']))->fetchColumn() == 0)
        {
            $response['error'] = cot::$L['files_err_perms'];
            echo json_encode($response);
            exit;
        }

        $orders = cot_import('orders', 'P', 'ARR');
        foreach ($orders as $order => $id){
            cot::$db->update($db_files, array('file_order' => $order),
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
    public function replaceAction(){
        $id = cot_import('id', 'P', 'INT');

        $response = array( 'error' => '');

        if(!$id){
            cot_sendheaders('application/json', cot_files_ajax_get_status(404));
            exit;
        }

        $file = files_model_File::getById($id);
        if (!$file) cot_files_ajax_die(404);

        $file->file_name = $this->getFilename('file');
        $file->file_ext = cot_files_get_ext($file->file_name);

        $limits = cot_files_getLimits(cot::$usr['id'], $file->file_source, $file->file_item);
        $upload = $this->getUploadedFile('file', $limits);

        cot_sendheaders('application/json', cot_files_ajax_get_status(200));

        if (cot_files_checkFile($file->file_ext) && !cot_error_found()){
            if (!cot_auth('files', 'a', 'A') && $file->user_id != cot::$usr['id']){
                $response['error'] = cot::$L['files_err_perms'];
                echo json_encode($response);
                exit;
            }
            $path = $file->file_path;
            $file->remove_thumbs();
            if(file_exists($path)){
                if(!@unlink($path)){
                    $response['error'] = cot::$L['files_err_replace'];
                    echo json_encode($response);
                    exit;
                }
            }

            $file->file_path = cot_files_path($file->file_source, $file->file_item, $file->file_id, $file->file_ext);

            if ($this->saveUploadedFile($upload, $file->file_path)){
                $file->file_size = filesize($file->file_path);
                $file->file_img = cot_files_isValidImageFile($file->file_path) ? 1 : 0;

                if($file->file_img){
                    // @todo все отправить в handle_image_file()
                    // Image resize
                    if(cot::$cfg['files']['image_resize']){
                        list($width_orig, $height_orig) = getimagesize($file->file_path);
                        if ($width_orig > cot::$cfg['files']['image_maxwidth'] || $height_orig > cot::$cfg['files']['image_maxheight']){
                            $input_file = $file->file_path;
                            $tmp_file =  $file->file_path.'tmp.'.$file->file_ext;
                            cot_files_thumbnail($input_file, $tmp_file, cot::$cfg['files']['image_maxwidth'],
                                cot::$cfg['files']['image_maxheight'], 'auto', (int)cot::$cfg['files']['quality']);
                            @unlink($input_file);
                            @rename($tmp_file, $input_file);
                            $file->file_size = filesize($file->file_path);
                        }
                    }
                }
                $file->save();

            }else{
                $response['error'] = cot::$L['files_err_move'];
                echo json_encode($response);
                exit;
            }
        }else{
            $messages = cot_get_messages();
            $errors = array();
            foreach ($messages as $msg){
                $errors[] = isset(cot::$L[$msg['text']]) ? cot::$L[$msg['text']] : $msg['text'];
            }
            cot_clear_messages();

            if(empty($errors)) $errors[] = cot::$L['error'];
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
                    cot_error(cot::$L['files_err_toobig']);
                }
                if ($_FILES[$input]['size'] > $limits['size_left']){
                    cot_error(cot::$L['files_err_nospace']);
                }

            }else{
                cot_error(cot::$L['files_err_upload']);
            }
            return $input;

        }else{
            $input = fopen('php://input', 'r');
            $temp = '';
            while (!feof($input))
                $temp .= fread($input, att_get_filesize(''));
            $temp = tmpfile();
            $size = stream_copy_to_stream($input, $temp);
            fclose($input);

            if (!$size){
                cot_error(cot::$L['files_err_upload']);

            }else{
                if ($size > $limits['size_maxfile']){
                    cot_error(cot::$L['files_err_toobig']);
                }

                if ($size > $limits['size_left']){
                    cot_error(cot::$L['files_err_nospace']);
                }
            }
            return $temp;
        }
    }

    /**
     * Saves an uploaded file regardless of request method.
     * @param  mixed   $input A value returned by FilesController::getUploadedFile
     * @see FilesController::getUploadedFile()
     * @param  string  $path  Target path
     * @return boolean        true on success, false on error
     *
     * @todo убедиться, что не остается мусора, если файл залит не через $_POST
     */
    protected  function saveUploadedFile($input, $path){
        if (cot_error_found()){
            return false;
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            return move_uploaded_file($_FILES[$input]['tmp_name'], $path);
        }
        else
        {
            $target = fopen($path, 'w');
            if (!$target)
            {
                return false;
            }
            fseek($input, 0, SEEK_SET);
            stream_copy_to_stream($input, $target);
            fclose($target);
            return true;
        }
    }

}