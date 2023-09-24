<?php

namespace cot\modules\files\services;

use cot\modules\files\dto\FileDto;
use cot\modules\files\model\File;
use image\exception\ImageException;
use image\Image;

/**
 * @package Files
 *
 * @todo при загрузке файлов проверять временную директорию и удалять фалы старше 2-х дней? \cot_files_tempDir()
 * c:/ospanel/userdata/temp\files_bd4fd02e3abc35abf77622_upload/
 */
class FileService
{
    public static $fileUploadErrors = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];

    /**
     * @param FileDto $file
     * @return FileDto
     */
    public static function processImageFile(FileDto $file)
    {
        $driver = Image::currentDriver();
        if (!$driver) {
            $file->addError(\Cot::$L['files_err_no_driver']);
            return $file;
        }

        // HEIF format isn't supported by the getimagesize() and exif_read_data().
        $sourceSize = getimagesize($file->getFullName());
        $getImageSizeSupported = !empty($sourceSize);

        // Check the image size and try to calculate and allocate the required RAM amount
        // cot_img_check_memory() works correctly with GD. It does not support all image formats that Imagick supports
        if ($getImageSizeSupported && !cot_img_check_memory($file->getFullName())) {
            $file->addError(\Cot::$L['files_err_toobig']);
            return $file;
        }

        if (!in_array(mb_strtolower($file->ext), Image::supportedFormats())) {
            // Can't process this image
            // Если установить error, то файл может быть удален контроллером
            return $file;
        }

        try {
            $image = Image::load($file->getFullName());
        } catch (ImageException $e) {
            // Can't process this image
            return $file;
        }
        $imageChanged = false;

        // Resize image if needed
        if (\Cot::$cfg['files']['image_resize']) {
            $neededWidth = (int) \Cot::$cfg['files']['image_maxwidth'];
            $neededHeight = (int) \Cot::$cfg['files']['image_maxheight'];
            if (
                ($neededWidth > 0 && $image->getWidth() > $neededWidth)
                || ($neededHeight > 0 && $image->getHeight() > $neededHeight)
            ) {
                // Check the image size and try to calculate and allocate the required RAM amount
                if (
                    $getImageSizeSupported
                    && !cot_img_check_memory(
                        $file->getFullName(),
                        (int) ceil($neededWidth * $neededHeight * 4 / 1048576)
                    )
                ) {
                    $file->addError(\Cot::$L['files_err_toobig']);
                    unset($image);
                    return $file;
                }

                try {
                    $image->thumbnail($neededWidth, $neededHeight, Image::THUMBNAIL_INSET);
                } catch (\Exception $e) {
                    $message = "Can't resize image";
                    if (\Cot::$usr['isadmin']) {
                        $msg = $e->getMessage();
                        if (!empty($msg)) {
                            $message .= ': ' . $msg;
                        }
                    }
                    $file->addError($message);
                    unset($image);
                    return $file;
                }

                $imageChanged = true;
            }
        }

        // Fix orientation
        if ($image->getOrientation() >= Image::ORIENTATION_TOPRIGHT && $image->getOrientation() <= Image::ORIENTATION_LEFTBOTTOM) {
            // Gettimg memory size required to process the image
            $depth = (!empty($sourceSize['bits']) && $sourceSize['bits'] > 8) ? ($sourceSize['bits'] / 8) : 1;
            $channels = (!empty($sourceSize['channels']) && $sourceSize['channels'] > 0) ? $sourceSize['channels'] : 4;
            // imagerotate() consumes a lot of memory. Try to allocate for 1.5 times more
            $needExtraMem = (int) ceil($image->getWidth() * $image->getHeight() * $depth * $channels / 1048576 * 1.5);
            if (!$getImageSizeSupported || cot_img_check_memory($file->getFullName(), $needExtraMem)) {
                $image->fixOrientation();
                $imageChanged = true;
            }
        }

        // Convert to JPEG
        if (FileService::isNeedToConvertToJpeg($file->file_name)) {
            $file->ext = 'jpg';
            $file->file_name = pathinfo($file->file_name, PATHINFO_FILENAME) . '.' . $file->ext;
            $file->original_name = pathinfo($file->original_name, PATHINFO_FILENAME) . '.' . $file->ext;
            $imageChanged = true;
        }

        if ($imageChanged) {
            try {
                $image->save($file->getFullName(), (int) \Cot::$cfg['files']['quality']);
            } catch (\Exception $e) {
                $message = "Can't save image '" . $file->getFullName() . "'";
                if (\Cot::$usr['isadmin']) {
                    $msg = $e->getMessage();
                    if (!empty($msg)) {
                        $message .= ': ' . $msg;
                    }
                }
                $file->addError($message);
                unset($image);
                return $file;
            }

            $file->size = filesize($file->getFullName());
        }

        unset($image);

        return $file;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public static function isNeedToConvertToJpeg($fileName)
    {
        if (!\Cot::$cfg['files']['image_convert']) {
            return false;
        }

        $toConvert = [];
        if (!empty(\Cot::$cfg['files']['image_to_convert'])) {
            $tmp = str_replace([' ', '.'], '', \Cot::$cfg['files']['image_to_convert']);
            $tmp = explode(',', $tmp);
            if (!empty($tmp)) {
                foreach ($tmp as $ext) {
                    $ext = trim($ext);
                    if (!empty($ext)) {
                        $toConvert[] =  mb_strtolower($ext);
                    }
                }
            }
        }

        $ext = cot_files_get_ext($fileName);
        if (
            (empty($toConvert) && !in_array($ext, ['jpg', 'jpeg']))
            || !empty($toConvert) && in_array($ext, $toConvert)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get file type icon by extension
     * @param string $ext
     * @param int $size
     * @return string
     */
    public static function typeIcon($ext, $size = 48)
    {
        $iconUrl = '';
        if (isset(\Cot::$R["files_icon_type_{$size}_{$ext}"])) {
            $iconUrl = \Cot::$R["files_icon_type_{$size}_{$ext}"];

        } elseif(isset(\Cot::$R["files_icon_type_48_{$ext}"])) {
            $iconUrl = \Cot::$R["files_icon_type_48_{$ext}"];
        }

        if (!empty($iconUrl)) {
            return $iconUrl;
        }

        if (!file_exists(\Cot::$cfg['modules_dir'] . "/files/img/types/$size")) {
            $size = 48;
        }

        if (file_exists(\Cot::$cfg['modules_dir'] . "/files/img/types/$size/{$ext}.png")) {
            return \Cot::$cfg['modules_dir'] . "/files/img/types/$size/{$ext}.png";
        }

        if (in_array($ext, ['avif','bmp','gif','jpg','jpeg','heic','png','tga','tpic','wbmp','webp','xbm'])) {
            return \Cot::$cfg['modules_dir'] . "/files/img/types/$size/image.png";
        }

        return \Cot::$cfg['modules_dir'] . "/files/img/types/$size/archive.png";
    }

    /**
     * Thumbnail folder absolute path
     * @return string
     */
    public static function thumbnailDirectory()
    {
        return \Cot::$cfg['files']['folder'] . '/_thumbs';
    }

    /**
     * Absolute path to the file's thumbnail folder.
     * @param int $id File ID
     * @return string
     */
    public static function fileThumbnailDirectory($id)
    {
        $hash = mb_substr(md5($id . \Cot::$cfg['site_id']), 0, 20);
        return static::thumbnailDirectory() . '/' . $id . 'a' . $hash;
    }

    /**
     * Absolute path for the file's thumbnail.
     * @param int $id File ID
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @param int $frame Thumbnail framing mode
     * @param string $extension
     * @return string Path for the file on disk or false file was not found
     */
    public static function thumbnailPath($id, $width, $height, $frame, $extension = '')
    {
        if (empty($extension)) {
            $extension = '.jpg';
        }
        $hash = mb_substr(md5($id . \Cot::$cfg['files']['prefix'] . \Cot::$cfg['site_id'] . $width . $height . $frame), 0, 20);
        return static::fileThumbnailDirectory($id) . '/' . \Cot::$cfg['files']['prefix'] . $hash . '-' . $width . 'x' . $height
            . '-' . $frame . '.' . $extension;
    }

    /**
     * Get existing thumbnail for file with any file extension
     * @param int $id File ID
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @param int $frame Thumbnail framing mode
     * @return string|false Absolute path to the file on disk or false file was not found
     */
    public static function getExistingThumbnail($id, $width, $height, $frame)
    {
        $mask = static::thumbnailPath($id, $width, $height, $frame, '*');
        $files = glob($mask, GLOB_NOSORT);
        if (!$files || count($files) == 0) {
            return false;
        } else {
            return $files[0];
        }
    }

    /**
     * Calculates new file path.
     * Return path relative to Cot::$cfg['files']['folder']
     *
     * @param File $file
     * @return ?string  Path for the file on disk
     */
    public static function generateFileRelativePath(File $file)
    {
        if (empty($file->source) || empty($file->ext)) {
            return null;
        }

        $source_id = (int) $file->source_id;
        $sourceId = max($source_id, 0);

        $filesPath = $file->source . '/' . $sourceId;
        if ($file->source === 'pfs') {
            $uid = (int) $file->user_id;
            if ($uid === 0) {
                $uid = \Cot::$usr['id'];
            }
            $filesPath = $file->source . '/'. $uid. '/' . $sourceId;
        }
        $hash = mb_substr(
            md5(
                $file->source . $sourceId . $file->original_name . $file->id . \Cot::$cfg['files']['prefix'] . \Cot::$cfg['site_id'] . mt_rand()
            ),
            0,
            20
        );
        return $filesPath . '/' . \Cot::$cfg['files']['prefix'] . $file->id . 'a' . $hash . '.' . $file->ext;
    }
}