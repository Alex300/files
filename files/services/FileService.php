<?php

declare(strict_types=1);

namespace cot\modules\files\services;

use Cot;
use cot\modules\files\dto\FileDto;
use cot\modules\files\models\File;
use filesystem\exceptions\UnableToMoveFile;
use filesystem\FilesystemFactory;
use filesystem\LocalFilesystem;
use image\exceptions\ImageException;
use image\Image;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use Throwable;

/**
 * @package Files
 *
 * @todo при загрузке файлов проверять временную директорию и удалять фалы старше 2-х дней? \cot_filesTempDir()
 * c:/ospanel/userdata/temp\files_bd4fd02e3abc35abf77622_upload/
 */
class FileService
{
    public const FILE_UPLOAD_ERRORS = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];

    public const MIME_TYPES = [
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
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe'  => 'image/jpeg',
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
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    /**
     * Configuration example
     *
     * File systems configuration example:
     * $cfg['filesystem'] = [
     *    'Yandex.Cloud' => [
     *       'adapter' => '\League\Flysystem\AwsS3V3\AwsS3V3Adapter',
     *       'config' => [
     *          'bucket' => 'my-bucket-name',
     *          'endpoint' => 'https://storage.yandexcloud.net',
     *          'region' => 'ru-central1',
     *          'accessKey' => 'MyAccessKey',
     *          'secretKey' => 'MySecretKey',
     *          'pathPrefix' => 'a/path/prefix',
     *       ],
     *     ]
     * ];
     *
     *  $cfg['files']['storages'] = [
     *    'Yandex.Cloud' => [
     *       ['source' => 'page'],
     *    ],
     *    'Google.Drive' => ['page'],
     *    'SomeStorage' => [
     *       ['source' => 'forums', 'field' => 'attachments'],
     *       ['source' => 'page', 'field' => ['gallery', 'someField']], // Так тоже можно
     *    ],
     *  ];
     */
    public static function getFilesystemName($fileSource = '', $fileField = '', $isThumbnail = false): string
    {
        $fileSystemName = 'default';

        // Calculate storage name
        // Разбор конфига
        if (!empty(Cot::$cfg['files']['storages']) && !empty($fileSource)) {
            foreach (Cot::$cfg['files']['storages'] as $name => $storage) {
                if (empty($storage) || !is_array($storage)) {
                    continue;
                }
                foreach ($storage as $sources) {
                    if (empty($sources)) {
                        continue;
                    }
                    if (!is_array($sources)) {
                        if ($sources === $fileSource) {
                            $fileSystemName = $name;
                            if (empty($fileField)) {
                                break 2;
                            }
                            continue;
                        }
                    }

                    if (isset($sources['source']) && $sources['source'] === $fileSource) {
                        if (empty($sources['field'])) {
                            $fileSystemName = $name;
                            if (empty($fileField)) {
                                break 2;
                            }
                            continue;
                        }
                        if (!is_array($sources['field'])) {
                            $sources['field'] = [$sources['field']];
                        }
                        if (in_array($fileField, $sources['field'])) {
                            $fileSystemName = $sources['filesystem'];
                            break 2;
                        }
                    }
                }
            }
        }

        return $fileSystemName;
    }

    /**
     * @return FilesystemOperator|LocalFilesystem
     * @see FilesystemFactory::getFilesystem()
     */
    public static function getFilesystem($fileSource = '', $fileField = '', $isThumbnail = false)
    {
        $fileSystemName = null;
        $fileSystem = null;

        /* === Hook === */
        foreach (cot_getextplugins('files.getFileSystem') as $pl) {
            include $pl;
        }
        /* ============ */

        if (!empty($fileSystem)) {
            return $fileSystem;
        }

        if (!empty($fileSystemName)) {
            return static::getFilesystemByName($fileSystemName);
        }

        if (function_exists('cot_filesGetFilesystem')) {
            $result = cot_filesGetFilesystem($fileSource, $fileField = '',$isThumbnail);
            if ($result !== null) {
                return $result;
            }
        }

        $fileSystemName = static::getFilesystemName($fileSource, $fileField, $isThumbnail);

        if ($fileSystemName === 'default' && empty(Cot::$cfg['filesystem']['default'])) {
            $fileSystemName = 'local';
        }

        return static::getFilesystemByName($fileSystemName);
    }

    /**
     * @return FilesystemOperator|LocalFilesystem
     * @see FilesystemFactory::getFilesystem()
     */
    public static function getFilesystemByName(string $fileSystemName = 'local')
    {
        if (empty($fileSystemName)) {
            $fileSystemName = 'local';
        }
        return FilesystemFactory::getFilesystem($fileSystemName, Cot::$cfg['files']['folder']);
    }

    /**
     * @param FileDto $file
     * @return FileDto
     */
    public static function processImageFile(FileDto $file)
    {
        $driver = Image::currentDriver();
        if (!$driver) {
            $file->addError(Cot::$L['files_err_no_driver']);
            return $file;
        }

        // HEIF format isn't supported by the getimagesize() and exif_read_data().
        $sourceSize = getimagesize($file->getFullName());
        $getImageSizeSupported = !empty($sourceSize);

        // Check the image size and try to calculate and allocate the required RAM amount
        // cot_img_check_memory() works correctly with GD. It does not support all image formats that Imagick supports
        if ($getImageSizeSupported && !cot_img_check_memory($file->getFullName())) {
            $file->addError(Cot::$L['files_err_toobig']);
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
        if (Cot::$cfg['files']['image_resize']) {
            $neededWidth = (int) Cot::$cfg['files']['image_maxwidth'];
            $neededHeight = (int) Cot::$cfg['files']['image_maxheight'];
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
                    $file->addError(Cot::$L['files_err_toobig']);
                    unset($image);
                    return $file;
                }

                try {
                    $image->thumbnail($neededWidth, $neededHeight, Image::THUMBNAIL_INSET);
                } catch (Throwable $e) {
                    $message = "Can't resize image";
                    if (Cot::$usr['isadmin']) {
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
        if (FileService::isNeedToConvertToJpeg($file->fileName)) {
            $file->ext = 'jpg';
            $file->fileName = pathinfo($file->fileName, PATHINFO_FILENAME) . '.' . $file->ext;
            $file->originalName = pathinfo($file->originalName, PATHINFO_FILENAME) . '.' . $file->ext;
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
            $mime = cot_filesGetMime($file->getFullName());
            $file->mimeType = $mime ?? '';
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

        $ext = cot_filesGetExtension($fileName);
        if (
            (empty($toConvert) && !in_array($ext, ['jpg', 'jpeg']))
            || !empty($toConvert) && in_array($ext, $toConvert)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get file type icon by extension or mime type
     */
    public static function typeIcon(string $ext, string $mimeType = '', int $size = 48): string
    {
        if (isset(Cot::$R["files_icon_type_{$size}_{$ext}"])) {
            return Cot::$R["files_icon_type_{$size}_{$ext}"];
        }
        if (isset(Cot::$R["files_icon_type_48_{$ext}"])) {
            return Cot::$R["files_icon_type_48_{$ext}"];
        }

        if (!file_exists(Cot::$cfg['modules_dir'] . "/files/img/types/{$size}")) {
            $size = 48;
        }

        if (file_exists(Cot::$cfg['modules_dir'] . "/files/img/types/{$size}/{$ext}.png")) {
            return Cot::$cfg['modules_dir'] . "/files/img/types/{$size}/{$ext}.png";
        }

        if (in_array($ext, ['avif','bmp','gif','jpg','jpeg','heic','png','tga','tpic','wbmp','webp','xbm'])) {
            return \Cot::$cfg['modules_dir'] . "/files/img/types/$size/image.png";
        }

        if (!empty($mimeType)) {
            $mimeParts = explode('/', $mimeType);

            if (isset(Cot::$R["files_icon_type_{$size}_{$mimeParts[1]}"])) {
                return Cot::$R["files_icon_type_{$size}_{$mimeParts[1]}"];
            }
            if (isset(Cot::$R["files_icon_type_48_{$mimeParts[1]}"])) {
                return Cot::$R["files_icon_type_48_{$mimeParts[1]}"];
            }

            $fileName = Cot::$cfg['modules_dir'] . "/files/img/types/{$size}/{$mimeParts[1]}.png";
            if (file_exists($fileName)) {
                return $fileName;
            }

            if ($mimeParts[0] !== 'application') {
                if (isset(Cot::$R["files_icon_type_{$size}_{$mimeParts[0]}"])) {
                    return Cot::$R["files_icon_type_{$size}_{$mimeParts[0]}"];
                }
                if (isset(Cot::$R["files_icon_type_48_{$mimeParts[0]}"])) {
                    return Cot::$R["files_icon_type_48_{$mimeParts[0]}"];
                }

                $fileName = Cot::$cfg['modules_dir'] . "/files/img/types/{$size}/{$mimeParts[0]}.png";
                if (file_exists($fileName)) {
                    return $fileName;
                }
            }
        }

        return Cot::$cfg['modules_dir'] . "/files/img/types/$size/archive.png";
    }

    /**
     * Calculates new file path.
     * Return path relative to Cot::$cfg['files']['folder']
     *
     * @param File $file
     * @return ?string Path for the file on disk
     */
    public static function generateFileRelativePath(File $file): ?string
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
                $uid = Cot::$usr['id'];
            }
            $filesPath = $file->source . '/'. $uid. '/' . $sourceId;
        }
        $hash = mb_substr(
            md5(
                $file->source . $sourceId . $file->original_name . $file->id . Cot::$cfg['files']['prefix']
                . Cot::$cfg['site_id'] . mt_rand()
            ),
            0,
            20
        );
        return $filesPath . '/' . Cot::$cfg['files']['prefix'] . $file->id . 'a' . $hash . '.' . $file->ext;
    }

    /**
     * Get file extension by Mime type
     */
    public static function getFileExtensionByMimeType(string $mimeType): ?string
    {
        if (class_exists('\League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap')) {
            $map = new GeneratedExtensionToMimeTypeMap();
            $ext = $map->lookupExtension($mimeType);
        } else {
            $ext = array_search($mimeType, static::MIME_TYPES);
        }

        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        return $ext ?: null;
    }

    /**
     * Get all file extensions by Mime type
     * @return string[]
     */
    public static function getAllFileExtensionsByMimeType(string $mimeType): array
    {
        if (class_exists('\League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap')) {
            $map = new GeneratedExtensionToMimeTypeMap();
            $extensions = $map->lookupAllExtensions($mimeType);
        } else {
            $extensions = array_keys(static::MIME_TYPES, $mimeType, true);
        }

        return $extensions;
    }

    /**
     * Temporary folder for file upload
     * @param bool $create Create folder if not exists?
     * @return string
     */
    public static function getTemporaryDirectory(bool $create = true): string
    {
        $tmpDir = sys_get_temp_dir();
        if (!empty($tmpDir) && @is_writable($tmpDir)) {
            $uplDir = $tmpDir . DIRECTORY_SEPARATOR . 'files_' . mb_substr(md5(Cot::$cfg['secret_key']), 10) . '_upload';
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
        $uplDir = Cot::$cfg['files']['folder'] . '/' . mb_substr(md5(Cot::$cfg['secret_key']), 10) . '_upload';
        if ($create && !file_exists($uplDir)) {
            mkdir($uplDir, Cot::$cfg['dir_perms'], true);
        }

        return $uplDir;
    }

    /**
     * Fix file extension by mime type
     */
    public static function fixFileExtension(string $fileName): string
    {
        $mimeType = mime_content_type($fileName);
        if (empty($mimeType) || $mimeType === 'application/octet-stream') {
            return $fileName;
        }
        $ext = cot_filesGetExtension($fileName);

        $possibleExtensions = FileService::getAllFileExtensionsByMimeType($mimeType);
        if (empty($possibleExtensions) || in_array($ext, $possibleExtensions)) {
            return $fileName;
        }

        $rightExt = $possibleExtensions[0];
        if ($rightExt === 'jpeg') {
            $rightExt = 'jpg';
        }

        if (
            empty($rightExt)
            || $ext === $rightExt
            || (in_array($ext, ['jpg', 'jpeg'], true) && $rightExt === 'jpg')
            || ($ext === 'csv' && $rightExt === 'txt')
            || ($ext === 'heic' && $rightExt === 'heif')
        ) {
            return $fileName;
        }

        if (empty($ext) || mb_strlen($ext) > 4) {
            $newName = $fileName . '.' . $rightExt;
        } else {
            $newName = mb_substr($fileName, 0, mb_strrpos($fileName, $ext) - 1) . '.' . $rightExt;
        }

        if (!@rename($fileName, $newName)) {
            throw UnableToMoveFile::fromLocationTo($fileName, $newName);
        }

        return $newName;
    }

    public static function fixFileExtensionByDTO(FileDto $file): void
    {
        $fileName = $file->getFullName();
        $newName = static::fixFileExtension($fileName);
        if ($newName === $fileName) {
            return;
        }
        $file->setFullName($newName);
        $rightExt = cot_filesGetExtension($newName);
        if (isset($file->originalName)) {
            $file->originalName .= '.' . $rightExt;
        }
    }

    /**
     * Garbage collect
     * Сборка мусора от несохраненных форм
     */
    public static function formGarbageCollect()
    {
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

        $tmpDir = FileService::getTemporaryDirectory(false);
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
     * Checks if file extension is allowed for upload. Returns error message or empty string.
     * Emits error messages via cot_error().
     *
     * @param  string $ext File extension
     * @return bool true if all checks passed, false if something was wrong
     */
    public static function isExtensionAllowed(string $ext): bool
    {
        if (!Cot::$cfg['files']['checkAllowedType']) {
            return true;
        }

        $validExtensions = explode(',', Cot::$cfg['files']['exts']);
        $validExtensions = array_map('trim', $validExtensions);
        if (empty($ext) || !in_array($ext, $validExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Привязка ранее загруженных файлов к только что созданному объекту
     *
     * @param string $source
     * @param int $item
     */
    public static function linkFiles(string $source, int $item): void
    {
        $formId = "{$source}_0";

        $unikey = cot_import('cf_' . $formId, 'P', 'TXT');
        if (!$unikey) {
            $unikey = cot_import('cf_' . $formId, 'G', 'TXT');
        }
        //$unikey = cot_import_buffered('cf_'.$formId, $unikey);

        if ($unikey && $item > 0) {
            $condition = [
                ['source', $source],
                ['source_id', 0],
                ['unikey', $unikey],
            ];

            $files = File::findByCondition($condition);

            if ($files) {
                foreach ($files as $fileRow) {
                    $fileRow->source_id = $item;

                    $newRelativePath = FileService::generateFileRelativePath($fileRow);

                    $fileSystem = FileService::getFilesystemByName($fileRow->filesystem_name);
                    $targetFileSystemName = FileService::getFilesystemName($fileRow->source, $fileRow->source_field);

                    try {
                        if ($fileRow->filesystem_name === $targetFileSystemName) {
                            $fileSystem->move($fileRow->getFullName(), $newRelativePath);
                        } else {
                            $targetFileSystem = FileService::getFilesystemByName($targetFileSystemName);
                            $stream = $fileSystem->readStream($fileRow->getFullName());
                            $targetFileSystem->writeStream($newRelativePath, $stream);
                            if (is_resource($stream) && get_resource_type($stream) === 'stream') {
                                fclose($stream);
                            }
                            $fileSystem->delete($fileRow->getFullName());
                        }
                    } catch (Throwable $e) {
                        cot_error(Cot::$L['files_err_upload']);
                        $fileRow->delete();
                        continue;
                    }

                    $fileRow->path = dirname($newRelativePath);
                    $fileRow->file_name = basename($newRelativePath);
                    $fileRow->unikey = '';
                    $fileRow->filesystem_name = $targetFileSystemName;
                    $fileRow->save();
                }
            }
        }

        self::formGarbageCollect();
    }
}