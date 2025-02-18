<?php

declare(strict_types=1);

namespace cot\modules\files\inc;

use Cot;
use cot\modules\files\services\FileService;
use Resources;

/**
 * Assets for Files module
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 */
class FilesAssets
{
    private static $loaded = false;

    private static $classInstances = [];

    /**
     * @return static
     * @todo use system GetInstanceTrait after 0.9.26 release
     */
    public static function getInstance(): self
    {
        $class = static::class;
        if (!isset(self::$classInstances[$class])) {
            self::$classInstances[$class] = new static();
        }
        return self::$classInstances[$class];
    }

    public function load(): void
    {
        global $cot_modules;

        if (static::$loaded) {
            return;
        }

        static::$loaded = true;

        $modUrl = Cot::$cfg['modules_dir'] . '/files';
        $nc = $cot_modules['files']['version'];

        Resources::addFile($modUrl . '/assets/files.min.css?nc=' . $nc);
        Resources::linkFileFooter($modUrl . '/assets/files.min.js?nc=' . $nc);

        // Table Drag&Drop plugin for reordering
        Resources::linkFileFooter('js/jquery.tablednd.min.js?nc=' . $nc);

        $jsConfig = [
            'allowedExtensions' => FileService::getInstance()->getAllowedExtensions(),
            'dataType' => 'json',
            'previewMaxWidth' => (int) Cot::$cfg['files']['thumb_width'],
            'previewMaxHeight' => Cot::$cfg['files']['thumb_height'],
            'autoUpload' => (bool) Cot::$cfg['files']['autoupload'],
            'sequentialUploads' => (bool) Cot::$cfg['files']['sequential'],
            'loadImageFileTypes' => '^image\/(avif|bmp|gif|jpeg|heic|heif|png|svg\+xml|x-tga|webp)$',
            'loadImageMaxFileSize' => 60000000, // 60MB todo is it needed?
            'x' => Cot::$sys['xk'],
        ];

        $chunkSize = (int) Cot::$cfg['files']['chunkSize'];
        if ($chunkSize > 0) {
            $jsConfig['maxChunkSize'] = $chunkSize;
        }

        if (
            Cot::$cfg['files']['image_resize']
            && Cot::$cfg['files']['imageResizeInBrowser']
            && Cot::$cfg['files']['image_maxwidth'] > 0
            && Cot::$cfg['files']['image_maxheight'] > 0
        ) {
            $jsConfig['disableImageResize'] = false;
            $jsConfig['imageMaxWidth'] = (int) Cot::$cfg['files']['image_maxwidth'];
            $jsConfig['imageMaxHeight'] = (int) Cot::$cfg['files']['image_maxheight'];
        }

        Resources::embedFooter('window.cotFiles.init(' . json_encode($jsConfig) . ')');
    }
}