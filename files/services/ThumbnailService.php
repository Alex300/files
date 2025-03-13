<?php

declare(strict_types=1);

namespace cot\modules\files\services;

use Cot;
use cot\modules\files\models\File;
use filesystem\LocalFilesystem;
use image\Image;
use League\Flysystem\Filesystem;
use Throwable;

class ThumbnailService
{
    /**
     * Thumbnail folder path. Absolute or relative to files folder
     */
    public static function thumbnailDirectory(bool $relative = false): string
    {
        return $relative ? '_thumbs' : Cot::$cfg['files']['folder'] . '/_thumbs';
    }

    /**
     * Calculate path to the file's thumbnail folder. Absolute or relative to files folder
     * @param int $id File ID
     * @param bool $relative
     * @return string
     */
    public static function fileThumbnailDirectory(int $id, bool $relative = false): string
    {
        $hash = mb_substr(md5($id . Cot::$cfg['site_id']), 0, 20);
        return static::thumbnailDirectory($relative) . '/' . $id . 'a' . $hash;
    }

    /**
     * Calculate path for the file's thumbnail. Absolute or relative to files folder
     * @param int $id File ID
     * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
     * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
     * @param string $frame Thumbnail framing mode
     * @param string $extension
     * @param bool $relative
     * @return string
     */
    public static function thumbnailPath(int $id, $width, $height, string $frame, string $extension = '', bool $relative = false): string
    {
        if (empty($extension)) {
            $extension = '.jpg';
        }

        $width = str_replace('%', 'p', (string) $width);
        $height = str_replace('%', 'p', (string) $height);

        $hash = mb_substr(md5($id . Cot::$cfg['files']['prefix'] . Cot::$cfg['site_id'] . $width . $height . $frame), 0, 20);
        return static::fileThumbnailDirectory($id, $relative) . '/' . Cot::$cfg['files']['prefix'] . $hash . '-' . $width . 'x' . $height
            . '-' . $frame . '.' . $extension;
    }

    /**
     * Returns file's thumbnail path. Generates the thumbnail first if it does not exist.
     * @param File|int $id File ID or instance of File.
     * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
     * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
     * @param string $frame Framing mode: one of \image\Image::THUMBNAIL_XXX constants (for backwards compatibility 'auto' and 'crop' also supported)
     * @param bool $watermark - set watermark if Cot::$cfg['files']['thumb_watermark'] not empty?
     * @param string $localFileFullPath The full path to the local file. If passed, this file will be used to generate a thumbnail
     * @return array{path: string, url: string, fileSystem: LocalFilesystem|Filesystem}|null Thumbnail path and url on success or null on error
     *
     * @todo можно вообще кешировать. Но тут вопрос, как зачищать кеш? Например при удалении всех миниатюр...
     */
    public static function thumbnail(
        $id,
        $width = 0,
        $height = 0,
        ?string $frame = null,
        bool $watermark = true,
        string $localFileFullPath = '',
        ?string $fileSystemName = null
    ): ?array {
        $file = null;
        if ($id instanceof File) {
            $file = $id;
            $id = $file->id;
        }
        $id = (int) $id;

        // Validate arguments
        if ($id <= 0) {
            return null;
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

        // Support for old framing modes from module version 1.x.
        if ($frame === 'crop') {
            $frame = Image::THUMBNAIL_OUTBOUND;
        } elseif ($frame === 'auto') {
            $frame = Image::THUMBNAIL_INSET;
        }

        if (empty($width) || (is_numeric($width) && (int) $width <= 0))  {
            $width  = (int) Cot::$cfg['files']['thumb_width'];
        }
        if (empty($height) || (is_numeric($width) && (int) $height <= 0)) {
            $height = (int) Cot::$cfg['files']['thumb_height'];
        }

        // Attempt to load from cache
        // Try to load thumbnail locally
        $mask = static::thumbnailPath($id, $width, $height, $frame, '*');
        $files = glob($mask, GLOB_NOSORT);
        if (!empty($files[0])) {
            $ext = pathinfo($files[0], PATHINFO_EXTENSION);;
            $relativePath = static::thumbnailPath($id, $width, $height, $frame, $ext, true);
            $thumbnailFileSystem = new LocalFilesystem(Cot::$cfg['files']['folder']);

            return [
                'path' => $relativePath,
                'url' => $thumbnailFileSystem->publicUrl($relativePath),
                'fileSystem' => $thumbnailFileSystem,
            ];
        }

        // Локальный файл не найден. Возможно используется удаленное хранилище
        if (empty($file)) {
            $file = File::getById($id);
        }
        if (!$file || !$file->is_img || !in_array(mb_strtolower($file->ext), Image::supportedFormats())) {
            return null;
        }

        if (empty($fileSystemName)) {
            $thumbnailFileSystem = FileService::getFilesystem($file->source, $file->source_field, true);
        } else {
            $thumbnailFileSystem = FileService::getFilesystemByName($fileSystemName);
        }

        $thumbExtension = $file->ext;
        if (FileService::isNeedToConvert($file->file_name)) {
            $thumbExtension = Cot::$cfg['files']['image_convert'];
        }

        $thumbRelativePath = ThumbnailService::thumbnailPath($id, $width, $height, $frame, $thumbExtension, true);

        if (!($thumbnailFileSystem instanceof LocalFilesystem)) {
            if ($thumbnailFileSystem->fileExists($thumbRelativePath)) {
                try {
                    $thumbnailUrl = $thumbnailFileSystem->publicUrl($thumbRelativePath);
                } catch (Throwable $e) {
                    $thumbnailUrl = '';
                }
                return [
                    'path' => $thumbRelativePath,
                    'url' => $thumbnailUrl,
                    'fileSystem' => $thumbnailFileSystem,
                ];
            }
        }

        if (!in_array(mb_strtolower($file->ext), Image::supportedFormats())) {
            // Can't process this image
            return null;
        }

        if ($localFileFullPath) {
            if (!is_file($localFileFullPath)) {
                return null;
            }
        } else {
            $fileSystem = FileService::getFilesystemByName($file->filesystem_name);
            if (!$fileSystem->fileExists($file->fullName)) {
                return null;
            }
        }

        // Generate a new thumbnail
        try {
            if ($localFileFullPath) {
                $image = Image::load($localFileFullPath);
            } else {
                $resource = $fileSystem->readStream($file->fullName);
                $image = Image::load($resource);
                fclose($resource);
            }
            $image->thumbnail($width, $height, $frame, (bool) Cot::$cfg['files']['upscale']);
        } catch (Throwable $e) {
            return null;
        }

        // Watermark
        if (
            $watermark
            && !empty(Cot::$cfg['files']['thumb_watermark'])
            && is_readable(Cot::$cfg['files']['thumb_watermark'])
            && $image->getWidth() >= Cot::$cfg['files']['thumb_wm_widht']
            && $image->getHeight() >= Cot::$cfg['files']['thumb_wm_height']
        ) {
            $watermarkImage = Image::load(Cot::$cfg['files']['thumb_watermark']);
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

        $saveToRemote = !($thumbnailFileSystem instanceof LocalFilesystem);
        try {
            if ($saveToRemote) {
                $thumbnailFileSystem->write(
                    $thumbRelativePath,
                    $image->encode($thumbExtension, (int) Cot::$cfg['files']['quality'])
                );
            } else {
                $thumbRelativeDir = dirname($thumbRelativePath);
                if (!$thumbnailFileSystem->directoryExists($thumbRelativeDir)) {
                    $thumbnailFileSystem->createDirectory($thumbRelativeDir);
                }
                $thumbPath = ThumbnailService::thumbnailPath($id, $width, $height, $frame, $thumbExtension, false);
                $image->save($thumbPath, (int) Cot::$cfg['files']['quality']);
            }
        } catch (Throwable $e) {
            unset($image);
            return null;
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.createThumbnail.done') as $pl) {
            include $pl;
        }
        /* ============ */

        unset($image);

        return [
            'path' => $thumbRelativePath,
            'url' => $thumbnailFileSystem->publicUrl($thumbRelativePath),
            'fileSystem' => $thumbnailFileSystem,
        ];
    }

    /**
     * Get existing thumbnail for file with any file extension
     * @param int $id File ID
     * @param int|string $width Thumbnail width absolute in pixels (int) or percent (string: '10%')
     * @param int|string $height Thumbnail height absolute in pixels (int) or percent (string: '10%')
     * @param string $frame Thumbnail framing mode
     * @return ?string Absolute path to the file on disk or false file was not found
     * @deprecated
     */
//    public static function getExistingThumbnail($id, $width, $height, $frame): ?string
//    {
//        $mask = static::thumbnailPath($id, $width, $height, $frame, '*');
//        $files = glob($mask, GLOB_NOSORT);
//        if (!$files || count($files) == 0) {
//            return null;
//        } else {
//            return $files[0];
//        }
//    }
}