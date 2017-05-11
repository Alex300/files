<?php
defined('COT_CODE') or die('Wrong URL.');

$source = cot_import('source', 'R', 'ALP');
$item = cot_import('item', 'R', 'INT');
$field = (string)cot_import('field', 'R', 'TXT');

$filename = cot_import('file', 'R', 'TXT');
if (!is_null($filename)) {
    $filename = mb_basename(stripslashes($filename));
}

/**
 * Upload Controller class for the Files module
 *
 * @package Files
 * @subpackage pfs
 * @author Cotonti Team
 * @author Kalnov Alexey    <kalnovalexey@yandex.ru>
 */
class UploadController
{
    protected $options = array(
            'input_stream' => 'php://input',

            // Use exif_imagetype on all files to correct file extensions.
            'correct_image_extensions' => true,

            // Add development info in output. Turn it off on production sites.
            'debug' => false
        );

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
     *
     * @return array             Data for JSON response
     */
    public function get($print_response = true)
    {
        global $source, $item, $field, $filename, $cot_extrafields;

        $uid = cot_import('uid', 'G', 'INT');
        if(is_null($uid)) $uid = cot::$usr['id'];

        $res = array();
        $condition = array(
            'sourse' => array('file_source', $source),
            'item'   => array('file_item', $item),
            'field'  => array('file_field', $field),
        );

        if($source == 'pfs'){
            if($item == 0) $condition['user'] = array('user_id', $uid);
        }

        if(!in_array($source, array('sfs', 'pfs')) && $item == 0){
            $unikey = cot_import('unikey', 'G', 'TXT');
            if($unikey) $condition['unikey'] = array('file_unikey', $unikey);
        }

        if (is_null($filename) || empty($filename))
        {
            $multi = true;
            $files = files_model_File::findByCondition($condition, 0, 0, 'file_order ASC');

        } else {
            $multi = false;
            $condition[] = array('file_name', $filename);
            $files = files_model_File::findByCondition($condition, 1);
        }
        if (!$files){
            return $this->generate_response(array(), $print_response);
        }
        
	/* === Hook - Part1 : Set === */
	$extp = cot_getextplugins('files.upload.row');
	/* ===== */
    
        foreach ($files as $row){
            $file = array(
                'id'          => $row->file_id,
                'name'        => $row->file_name,
                'ext'         => htmlspecialchars($row->file_ext),
                'size'        => (int)$row->file_size,
                'url'         => cot::$cfg['mainurl'] . '/' . cot_files_path($source, $item, $row->file_id, $row->file_ext),
                'deleteType'  => 'POST',
                'deleteUrl'   => cot::$cfg['mainurl'] . '/index.php?e=files&m=upload&id='.$row->file_id.'&_method=DELETE&x='.cot::$sys['xk'],
                'title'       => htmlspecialchars($row->file_title),
                'lastmod'     => strtotime($row->file_updated),
                'isImage'     => $row->file_img
            );


            $editForm = array(
                0 => array(
                    'title'   => cot::$L['Title'],
                    'element' => cot_inputbox('text', 'file_title', $row->file_title,
                        array('class' => 'form-control file-edit', 'placeholder' => cot::$L['Title']))
                )
            );
            // Extra fields
            if(!empty($cot_extrafields[files_model_File::tableName()])) {
                foreach ($cot_extrafields[files_model_File::tableName()] as $exfld) {
                    $uname = strtoupper($exfld['field_name']);
                    $exfld_name = 'file_'.$exfld['field_name'];
                    $exfld_element = cot_build_extrafields('file_' . $exfld['field_name'], $exfld, $row->{$exfld_name});
                    $exfld_title = isset(cot::$L['files_' . $exfld['field_name'] . '_title']) ?
                        cot::$L['files_' . $exfld['field_name'] . '_title'] : $exfld['field_description'];

                    $file[$exfld_name] = cot_build_extrafields_data('files', $exfld, $row->{$exfld_name});

                    $editForm[] = array(
                        'title'   => $exfld_title,
                        'element' => $exfld_element,
                    );
                }
            }
            // /Extra fields
            $file['editForm'] = $editForm;

            if ($row->file_img){
                $file['thumbnailUrl'] = cot::$cfg['mainurl'] . '/' . cot_files_thumb($row->file_id) . '?lastmod=' .
                    strtotime($row->file_updated);
                $file['thumbnail'] = cot::$cfg['mainurl'] . '/' . cot_files_thumb($row->file_id);
            }else{
                $file['thumbnailUrl'] = cot::$cfg['mainurl'] . '/' . $row->icon;
            }
	
	/* === Hook - Part2 : Include === */
	foreach ($extp as $pl)
	{
		include $pl;
	}
	/* ===== */
        
            if (!$multi){
                return $this->generate_response($file, $print_response);

            } else {
                $res['files'][] = $file;
            }
        }


        return $this->generate_response($res, $print_response);
    }

    public function post($print_response = true)
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }

        $param_name = 'files';
        $upload = isset($param_name) ? $_FILES[$param_name] : null;

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

        $files = array();
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $files[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }

        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ? $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ? $upload['size'] : $this->get_server_var('CONTENT_LENGTH')),
                isset($upload['type']) ? $upload['type'] : $this->get_server_var('CONTENT_TYPE'),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.upload.done') as $pl) {
            include $pl;
        }
        /* ===== */

        return $this->generate_response(
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
        if(!$id){
            $this->generate_response($res, $print_response);
            exit();
        }

        $file = files_model_File::getById($id);
        if(!$file){
            $this->generate_response($res, $print_response);
            exit();
        }
        if ($file->user_id != cot::$usr['id'] && !cot_auth('files', 'a', 'A')) {
            $this->generate_response($res, $print_response);
            exit();
        }

        $res['success'] = $file->delete();

        $this->generate_response($res, $print_response);
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
        $condition = array(
            array('file_source', $source),
            array('file_item', $item),
        );
        if($field != '_all_') $condition[] = array('file_field', $field);

        return files_model_File::count($condition);
    }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    protected function generate_response($content, $print_response = true)
    {
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ?
                stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                header('Location: '.sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
                $files = isset($content[$this->options['param_name']]) ?
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
        $tmp = cot_files_safeName($source.'_'.$item.'_'.$this->fix_file_extension($file_path, $name, $type));

        return $this->get_unique_filename($tmp, $content_range);
    }

    protected function get_file_size($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                clearstatcache(true, $file_path);

            } else {
                clearstatcache();
            }
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }

    protected function get_unique_filename($name, $content_range)
    {
        $tmpDir = cot_files_tempDir().DIRECTORY_SEPARATOR;
        while(is_dir($tmpDir.$name)) {
            $name = $this->upcount_name($name);
        }

        // Keep an existing filename if this is part of a chunked upload:
        $uploaded_bytes = $this->fix_integer_overflow(intval($content_range[1]));
        while(is_file($tmpDir.$name)) {
            if ($uploaded_bytes === $this->get_file_size( $tmpDir.$name)) {
                break;
            }
            $name = $this->upcount_name($name);
        }

        return $name;
    }

    protected function get_upload_path($source, $item)
    {
        return cot::$cfg['files']['folder'] . '/' . $source . '/' . $item;
    }

    protected function get_server_var($id)
    {
        return isset($_SERVER[$id]) ? $_SERVER[$id] : '';
    }

    /**
     * Обработка загрузки файла
     * @param $uploaded_file
     * @param $name
     * @param $size
     * @param $type
     * @param $error
     * @param null $index
     * @param null $content_range
     * @return stdClass
     *
     * @todo если пришел uid и пользователь админ, то сохранять файлы от пользователя с указанным uid
     */
    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null,
        $content_range = null)
    {
        global $source, $item, $field, $usr, $cot_extrafields;

        $file = new \stdClass();
        $file->file_name = trim(mb_basename(stripslashes($name)));
        $file->name = $this->get_file_name($uploaded_file, $name, $source, $item, $type, $content_range);
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;

        list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('files', 'a');

        if (!$this->preValidate($uploaded_file, $file, $error, $index)) {
            $file->debug['path'] = $file->path;
            $file->debug['uploaded_file'] = $uploaded_file;
            $file->debug['upload_dir'] = cot_files_tempDir();

            $file->error = !empty($file->error) ? $file->error : 'Unknown error';
            unset($file->path);
            unset($file->file_name);
            if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);

            return $file;
        }


        $file->ext = cot_files_get_ext($file->name);

        $this->handle_form_data($file, $index);

        $upload_dir = cot_files_tempDir();
        if (!is_dir($upload_dir)) mkdir($upload_dir, cot::$cfg['dir_perms'], true);

        $file_path = $upload_dir. '/'.$file->name;

        $append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);
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
        if(!cot_files_memory_allocate($file_size + 2097152)) {
            if($file_path && file_exists($file_path)) unlink($file_path);
            unset($file->path, $file->file_name);
            if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);
            return $file;
        }

        // File is uploaded
        if ($file_size === $file->size) {
            $file->path = $file_path;
            $file->isImage = false;
            if (cot_files_isValidImageFile($file_path)) {
                $file->isImage = true;
            }

            // Validate uploaded file
            if(!$this->validate($file) || $file->error) {
                if($file_path && file_exists($file_path)) unlink($file_path);
                unset($file->path, $file->file_name);
                if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);
                return $file;
            }

            if($file->isImage) $this->handle_image_file($file);

            if($file->error) {
                if($file_path && file_exists($file_path)) unlink($file_path);
                unset($file->path, $file->file_name);
                if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);
                return $file;
            }

            $params = cot_import('param', 'R', 'HTM');
            if(!empty($params)){
                $params = unserialize(base64_decode($params));
            }

            $uid = $usr['id'];
            if($usr['isadmin']){
                $uid = cot_import('uid', 'G', 'INT');
                if(is_null($uid)) $uid = $usr['id'];
            }

            // saving
            $objFile = new files_model_File();
            $objFile->file_name = $file->file_name;
            $objFile->user_id = $uid;
            $objFile->file_source = $source;
            $objFile->file_item = $item;
            $objFile->file_field = $field;
            $objFile->file_ext = $file->ext;
            $objFile->file_img = $file->isImage;
            $objFile->file_size = $file->size;

            if(!in_array($source, array('sfs', 'pfs')) && $item == 0){
                $unikey = cot_import('unikey', 'G', 'TXT');
                if($unikey) $objFile->file_unikey = $unikey;
            }

            /* === Hook === */
            foreach (cot_getextplugins('files.upload.before_save') as $pl) {
                include $pl;
            }
            /* ===== */

            if($id = $objFile->save()) {
                $file->name = $file->file_name;
                $objFile->file_path = cot_files_path($source, $item, $objFile->file_id, $file->ext, $objFile->user_id);
                $file_dir = dirname($objFile->file_path);
                if (!is_dir($file_dir)) {
                    mkdir($file_dir, cot::$cfg['dir_perms'], true);
                }
                if(!@rename($file->path, $objFile->file_path)){
                    @unlink($file->path);
                    unset($file->path, $file->file_name);
                    if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);
                    $file->error = cot::$L['files_err_upload'];
                    $objFile->delete();
                    return $file;
                }
                $objFile->save();

                // Avatar support
                if(!empty($params['avatar']) && $objFile->file_img && $objFile->file_source == 'pfs'){
                    $objFile->makeAvatar();
                }

                $file->url = cot::$cfg['mainurl'] . '/' . $objFile->file_path;
                $file->thumbnailUrl = $file->thumbnail = ($file->isImage) ? cot::$cfg['mainurl'] . '/' . cot_files_thumb($id) :
                    cot::$cfg['mainurl'] . '/' . $objFile->icon;
                $file->id = $id;
                $file->deleteUrl = cot::$cfg['mainurl'] . '/index.php?e=files&m=upload&id='.$objFile->file_id.
                    '&_method=DELETE&x='.cot::$sys['xk'];
                $file->deleteType = 'POST';


                $editForm = array(
                    0 => array(
                        'title'   => cot::$L['Title'],
                        'element' => cot_inputbox('text', 'file_title', '',
                            array('class' => 'form-control file-edit', 'placeholder' => cot::$L['Title']))
                    )
                );
                // Extra fields
                if(!empty($cot_extrafields[files_model_File::tableName()])) {
                    foreach ($cot_extrafields[files_model_File::tableName()] as $exfld) {
                        $uname = strtoupper($exfld['field_name']);
                        $exfld_name = 'file_'.$exfld['field_name'];
                        $exfld_element = cot_build_extrafields('file_' . $exfld['field_name'], $exfld, '');
                        $exfld_title = isset(cot::$L['files_' . $exfld['field_name'] . '_title']) ?
                            cot::$L['files_' . $exfld['field_name'] . '_title'] : $exfld['field_description'];

                        $file->{$exfld_name} = cot_build_extrafields_data('files', $exfld, '');
                        $editForm[] = array(
                            'title'   => $exfld_title,
                            'element' => $exfld_element,
                        );
                    }
                }
                // /Extra fields
                $file->editForm = $editForm;

                /* === Hook === */
                foreach (cot_getextplugins('files.upload.after_save') as $pl) {
                    include $pl;
                }
                /* ===== */

            } else {
                if($file_path && file_exists($file_path)) unlink($file_path);
                unset($file->path, $file->file_name);
                if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);
                $file->error = cot::$L['files_err_upload'];
                return $file;
            }

        } else {
            $file->size = $file_size;
//                if (!$content_range && $this->options['discard_aborted_uploads']) {
            if (!$content_range) {
                unlink($file_path);
                $file->error = cot::$L['files_err_abort'];
            }
        }
        $this->set_additional_file_properties($file);

        unset($file->path);
        unset($file->file_name);
        if(!$this->options['debug'] && isset($file->debug)) unset($file->debug);

        return $file;
    }

    protected function handle_form_data() {

    }

    public function head() {
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

    protected function handle_image_file($file)
    {
        // Check the image size and try to calculate and allocate the required RAM amount
        if(!cot_img_check_memory($file->path)){
            @unlink($file->path);
            $file->error = cot::$L['files_err_toobig'];
            return $file;
        }

        // Automatic JPG conversion feature
        if (cot::$cfg['files']['image_convert'] && $file->ext != 'jpg' && $file->ext != 'jpeg') {
            $input_file = $file->path;

            $newName = pathinfo($file->name, PATHINFO_FILENAME) . '.jpg';
            $output_file = dirname($file->path).'/'.$newName;
            if ($file->ext == 'png') {
                $input = imagecreatefrompng($input_file);

            } else {
                $input = imagecreatefromgif($input_file);
            }
            list($width, $height) = getimagesize($input_file);
            $output = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($output,  255, 255, 255);
            imagefilledrectangle($output, 0, 0, $width, $height, $white);
            imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
            imagejpeg($output, $output_file);

            @unlink($input_file);
            imagedestroy($input);
            imagedestroy($output);

            $file->path = $output_file;
            $file->size = $this->get_file_size($file->path);
            $file->ext = 'jpg';
            $file->name = pathinfo($file->name, PATHINFO_FILENAME) . '.jpg';
        }

        // Fix image orientation via EXIF if possible
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->path);
            if($exif !== false) {
                $orientation = (!empty($exif['Orientation'])) ? (int)$exif['Orientation'] : 0;

                if ($orientation >= 2 && $orientation <= 8) {

                    // Gettimg memory size required to process the image
                    $source_size = getimagesize($file->path);

                    $width = $source_size[0];
                    $height = $source_size[1];
                    $depth = ($source_size['bits'] > 8) ? ($source_size['bits'] / 8) : 1;
                    $channels = $source_size['channels'] > 0 ? $source_size['channels'] : 4;
                    // imagerotate потребляет много памяти. Попросим в 1.5 раза больше
                    $needExtraMem = $width * $height * $depth * $channels / 1048576 * 1.5;

                    $size_ok = cot_img_check_memory($file->path, (int)ceil($needExtraMem));

                    if ($size_ok) {
                        $newImage = null;
                        switch ($file->ext) {
                            case 'gif':
                                $sourceImage = imagecreatefromgif($file->path);

                                // Get a transparent color
                                $transparent_source_index = imagecolortransparent($sourceImage);
                                if($transparent_source_index !== -1) {
                                    $transparent_color = imagecolorsforindex($sourceImage, $transparent_source_index);
                                }

                                break;

                            case 'png':
                                $sourceImage = imagecreatefrompng($file->path);
                                imagecolortransparent($sourceImage, imagecolorallocatealpha($sourceImage, 0, 0, 0, 127));
                                imagealphablending($sourceImage, false);
                                imagesavealpha($sourceImage, true);
                                break;

                            default:
                                $sourceImage = imagecreatefromjpeg($file->path);
                                break;
                        }

                        // NOTE: Values 2, 4, 5, 7 are uncommon since they represent "flipped" orientations.
                        switch ($orientation) {
                            case 2:
                                $newImage = $this->gd_imageflip($sourceImage,
                                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                                );
                                break;

                            case 3:
                                // 180 rotate left
                                $newImage = imagerotate($sourceImage, 180, 0);
                                break;

                            case 4:
                                $newImage = $this->gd_imageflip($sourceImage,
                                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                                );
                                break;

                            case 5:
                                $tmp_img = $this->gd_imageflip($sourceImage,
                                    defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
                                );
                                $newImage = imagerotate($tmp_img, 270, 0);
                                imagedestroy($tmp_img);
                                break;

                            case 6:
                                // 90 rotate right
                                $newImage = imagerotate($sourceImage, -90, 0);
                                break;

                            case 7:
                                $tmp_img = $this->gd_imageflip($sourceImage,
                                    defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
                                );
                                $newImage = imagerotate($tmp_img, 270, 0);
                                imagedestroy($tmp_img);
                                break;

                            case 8:
                                // 90 rotate left
                                $newImage = imagerotate($sourceImage, 90, 0);
                                break;
                        }

                        switch ($file->ext) {
                            case 'gif':
                                // Chek if we have a transparency
                                if($transparent_source_index !== -1) {
                                    // Add color to the palette of the new image, and set it as transparent
                                    $transparent_destination_index = imagecolorallocatealpha($newImage,
                                        $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'],
                                        $transparent_color['alpha']);
                                    imagecolortransparent($newImage, $transparent_destination_index);
                                }
                                imagegif($newImage, $file->path);
                                break;

                            case 'png':
                                imagealphablending($newImage, false);
                                imagesavealpha($newImage, true);
                                imagepng($newImage, $file->path);
                                break;

                            default:
                                imagejpeg($newImage, $file->path, 96);
                                break;
                        }

                        imagedestroy($sourceImage);
                        if(!empty($newImage)) imagedestroy($newImage);
                    }
                }
            }
        }

        // Image resize
        if(cot::$cfg['files']['image_resize']){
            list($width_orig, $height_orig) = getimagesize($file->path);
            if ($width_orig > cot::$cfg['files']['image_maxwidth'] || $height_orig > cot::$cfg['files']['image_maxheight']){
                // Check the image size and try to calculate and allocate the required RAM amount
                if(!cot_img_check_memory($file->path, (int)ceil(cot::$cfg['files']['image_maxwidth'] *
                    cot::$cfg['files']['image_maxheight'] * 4 / 1048576))){
                    @unlink($file->path);
                    $file->error = cot::$L['files_err_toobig'];
                    return $file;
                }

                $input_file = $file->path;
                $tmp_file =  $file->path.'tmp.'.$file->ext;
                cot_files_thumbnail($input_file, $tmp_file, cot::$cfg['files']['image_maxwidth'],
                    cot::$cfg['files']['image_maxheight'], 'auto', (int)cot::$cfg['files']['quality']);
                @unlink($input_file);
                @rename($tmp_file, $input_file);
                $file->size = $this->get_file_size($file->path);
            }
        }

    }

    /**
     * Flip image
     *
     * @param resource  $image
     * @param int       $mode
     * @return bool|resource
     *
     * @todo get extra memory if needed
     */
    protected function gd_imageflip($image, $mode)
    {
        $new_width = $src_width = imagesx($image);
        $new_height = $src_height = imagesy($image);

        if(!cot_files_memory_allocate($new_width * $new_height * 4 * 1.9)) {
            return false;
        }

        $new_img = imagecreatetruecolor($new_width, $new_height);

        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        $colorTransparent = imagecolorallocatealpha($new_img, 0, 0, 0, 127);
        imagefill($new_img, 0, 0, $colorTransparent);

        if (function_exists('imageflip')) {
            imagecopy($new_img, $image, 0,0, 0,0,$new_width, $new_height);
            imageflip($new_img, $mode);
            return $new_img;
        }

        $src_x = 0;
        $src_y = 0;
        switch ($mode) {
            case '1': // flip on the horizontal axis
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                break;

            case '2': // flip on the vertical axis
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;

            case '3': // flip on both axes
                $src_y = $new_height - 1;
                $src_height = -$new_height;
                $src_x  = $new_width - 1;
                $src_width = -$new_width;
                break;

            default:
                return $image;
        }
        imagecopyresampled(
            $new_img,
            $image,
            0,
            0,
            $src_x,
            $src_y,
            $new_width,
            $new_height,
            $src_width,
            $src_height
        );
        return $new_img;
    }

    protected function set_additional_file_properties($file) { }

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

        $valid_exts = explode(',', cot::$cfg['files']['exts']);
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
     * @param $uploaded_file
     * @param $file
     * @param $error
     * @param $index
     * @return bool
     */
    protected function preValidate($uploaded_file, $file, $error, $index)
    {
        global $source, $item, $field;

        if(!cot_auth('files', 'a', 'W')){
            $file->error = cot::$L['files_err_perms'];
            return false;
        }

        if ($error) {
            $file->error = $error;
            return false;
        }
        if (!$file->name) {
            $file->error = 'missingFileName';
            return false;
        }

        $file_ext = cot_files_get_ext($file->name);
        if (!cot_files_isExtensionAllowed($file_ext)) {
            $file->error = cot::$L['files_err_type'];
            return false;
        }

        $content_length = $this->fix_integer_overflow(intval(
            $this->get_server_var('CONTENT_LENGTH')
        ));

        /** @var int $file_size current file size (chunk size) */
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            if($this->options['debug']) $file->debug['is_uploaded_file'] = true;
            $file_size = $this->get_file_size($uploaded_file);

        } else {
            $file_size = $content_length;
        }

        if($this->options['debug']) {
            $file->debug['file'] = $uploaded_file;
            $file->debug['current_file_size'] = $file_size;
            $file->debug['file_size'] = $file->size;
        }

        $limits = cot_files_getLimits(cot::$usr['id'], $source, $item);
        if ($file_size > $limits['size_maxfile'] || $file->size > $limits['size_maxfile']) {
            $file->error = cot::$L['files_err_toobig'];
            return false;
        }

        if ($file_size > $limits['size_left'] || $file->size > $limits['size_left']) {
            $file->error = cot::$L['files_err_nospace'];
            return false;
        }

        if(!isset($params['field'])) $params['field'] = $field;
        if(!isset($params['limit'])) {
            if($limits['count_left'] == 0) {
                $file->error = cot::$L['files_err_count'];
                return false;
            }
        } else {
            // Это не касается несуществующих объяектов
            if ($item > 0 && $params['limit'] > 0 && ($this->count_file_objects($source, $item, $params['field']) >= $params['limit'])) {
                $file->error = cot::$L['files_err_count'];
                return false;
            }

        }

        return true;
    }

    /**
     * Uploaded file validation
     * @param $file
     * @return bool
     *
     * @todo validate mime-type
     */
    protected function validate($file)
    {
        /* === Hook === */
        foreach (cot_getextplugins('files.upload.before.validate') as $pl) {
            include $pl;
        }
        /* ===== */

        $valid_exts = explode(',', cot::$cfg['files']['exts']);
        $valid_exts = array_map('trim', $valid_exts);

        $handle = fopen($file->path, "rb");
        $tmp = fread ($handle , 10);
        fclose($handle);
        if(!in_array('php', $valid_exts) && (mb_stripos(trim($tmp), '<?php') === 0))  {
            $file->error = cot::$L['files_err_type'];
            return false;
        }
        unset($tmp);

        $mime = cot_files_getMime($file->path);

        if($this->options['debug']) $file->debug['mime'] = $mime;

        $params = cot_import('param', 'R', 'HTM');
        if(!empty($params)){
            $params = unserialize(base64_decode($params));
            if(!empty($params['type'])){
                $params['type'] = json_decode($params['type']);
                $typeOk = false;
                if(in_array('all' , $params['type'])){
                    $typeOk = true;

                } elseif(in_array('image' , $params['type']) && cot_files_isValidImageFile($file->path)) {
                    $typeOk = true;

                } elseif(in_array('video' , $params['type']) && mb_stripos($mime, 'video') !== false) {
                    $typeOk = true;

                } elseif(in_array('audio' , $params['type']) && mb_stripos($mime, 'audio') !== false) {
                    $typeOk = true;
                }

                if(!$typeOk) {
                    $file->error = cot::$L['files_err_type'];
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
