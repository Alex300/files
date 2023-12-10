<?php

declare(strict_types=1);

namespace cot\modules\files\dto;

use Cot;
use cot\modules\files\models\File;
use cot\modules\files\services\FileService;
use filesystem\LocalFilesystem;
use image\Image;

/**
 * Class for transfer file data between methods (functions) or to the frontend
 * @package Files
 */
class FileDto
{
    private array $errors = [];

    private array $debug = [];

    /**
     * @var File
     */
    private File $file;

    /**
     * @var string Full path to file relative to site root. (Note: File model use path relative to Cot::$cfg['files']['folder'])
     */
    public string $path;
    public string $fileName;
    public string $originalName;
    public string $ext = '';
    public bool $isImage = false;
    public int $size;
    public string $mimeType = '';

    public ?string $thumbnailUrl = null;
    public ?bool $fileExists = null;
    public ?int $lastModified = null;

    private array $extraData = [];

    public function __get($name)
    {
        return $this->extraData[$name] ?? null;
    }

    public function __isset($name)
    {
        try {
            $tmp = $this->__get($name);
            return $tmp !== null;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function __set($name, $value)
    {
        $this->extraData[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->extraData[$name]);
    }

    public function addDebug($key, $value)
    {
        $this->debug[$key] = $value;
    }

    public function clearDebug()
    {
        $this->debug = [];
    }

    public function getDebug(): array
    {
        return $this->debug;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFullName(): string
    {
        return $this->path . '/' . $this->fileName;
    }

    public function setFullName(string $fullPath): void
    {
        $this->path = dirname($fullPath);
        $this->fileName = basename($fullPath);
        $this->ext = mb_strtolower(cot_filesGetExtension($this->fileName));
    }

    public static function createFromFile(File $file): FileDto
    {
        $dto = new FileDto();
        $dto->loadFromFile($file);
        return $dto;
    }

    public function loadFromFile(File $file): void
    {
        $this->file = $file;
        $this->extraData['id'] = (int) $file->id;

        //$this->path = \Cot::$cfg['files']['folder'] . '/' . $file->path;
        $this->fileName = $file->file_name;
        if (isset($file->original_name)) {
            $this->originalName = $file->original_name;
        }
        if (isset($file->ext)) {
            $this->ext = mb_strtolower($file->ext);
        }
        if (isset($file->is_img)) {
            $this->isImage = (bool) $file->is_img;
        }
        if (isset($file->size)) {
            $this->size = (int) $file->size;
        }

        if (isset($file->mime_type)) {
            $this->mimeType = $file->mime_type;
        }

        if (!empty($file->updated)) {
            $this->lastModified = strtotime($file->updated);
        }

        if (isset($file->title)) {
            $this->extraData['title'] = $file->title;
        }

        // Extra fields
        if (!empty($cot_extrafields[File::tableName()])) {
            foreach ($cot_extrafields[File::tableName()] as $exfld) {
                $fieldName = $exfld['field_name'];
                $this->extraData[$fieldName] = isset($file->{$fieldName}) ? $file->{$fieldName} : null;
            }
        }
        // /Extra fields
    }

    /**
     * Preparing data for frontend
     * path и file_name не надо выводить. Это внутренние данные
     */
    public function toArray(): array
    {
        $id = 0;
        if (!empty($this->extraData['id'])) {
            $id = $this->extraData['id'];
        } elseif (!empty($this->file)) {
            $id = $this->file->id;
        }

        $result = !empty($this->extraData) ? $this->extraData : [];

        if ($this->fileExists !== null && !$this->fileExists) {
            $this->addError('File is not exists');
        }

        if (!empty($this->originalName)) {
            $result['name'] = htmlspecialchars($this->originalName);
        }

        if (!empty($this->ext)) {
            $result['ext'] = $this->ext;
        }

        if (!empty($this->size)) {
            $result['size'] = $this->size;
            //$result['size'] = $fileExists ? $this->size : 0;
        }

        //$result['isImage'] = 0;
        if (isset($this->isImage)) {
            $result['isImage'] = $this->isImage && !empty($this->ext) && in_array(mb_strtolower($this->ext), Image::supportedFormats()) ? 1 : 0;
        }

        if (!empty($this->file) && ($this->fileExists === null || $this->fileExists)) {
            $fileSystem = FileService::getFilesystemByName($this->file->filesystem_name);
            $result['url'] = $fileSystem->publicUrl($this->file->getFullName());
        }

        $thumbParam = null;

        if (!empty($this->file) && $this->file->id > 0) {
            $thumbParam = $this->file;
        } elseif (!empty($this->extraData['id'])) {
            $thumbParam = $this->extraData['id'];
        }

        $result['thumbnail'] = $result['thumbnailUrl'] = Cot::$cfg['mainurl'] . '/'
            . FileService::typeIcon($this->ext ?? '', $this->mimeType ?? '');

        if (
            ($this->fileExists === null || $this->fileExists)
            && isset($result['isImage'])
            && $result['isImage']
        ) {
            //$result['thumbnailUrl'] ??= '';
            if ($this->thumbnailUrl) {
                $result['thumbnailUrl'] = $result['thumbnail'] = $this->thumbnailUrl;
                if (!empty($this->lastModified)) {
                    $result['thumbnailUrl'] .= '?lm=' . $this->lastModified;
                }
            }
        }

        if (!empty($this->ext) || !empty($this->mimeType)) {
            $result['thumbnail'] = $result['thumbnailUrl'] = Cot::$cfg['mainurl'] . '/'
                . FileService::typeIcon($this->ext ?? '', $this->mimeType ?? '');
        }
        if ($this->fileExists !== null && !$this->fileExists) {
            unset($result['thumbnail'], $result['thumbnailUrl']);
        } elseif (isset($result['isImage']) && $result['isImage']) {
            $result['thumbnailUrl'] ??= '';
            if ($this->thumbnailUrl) {
                $result['thumbnailUrl'] = $result['thumbnail'] = $this->thumbnailUrl;
                if (!empty($this->lastModified)) {
                    $result['thumbnailUrl'] .= '?lm=' . $this->lastModified;
                }
            }
        }

        if (!empty($this->lastModified)) {
            $result['lastModified'] = $this->lastModified;
        }

        if ($id > 0) {
            $result['deleteUrl'] = Cot::$cfg['mainurl'] . '/index.php?e=files&m=upload&id=' . $id . '&_method=DELETE&x=' . Cot::$sys['xk'];
            $result['deleteType'] = 'POST';

            $result['editForm'] = [
                [
                    'title' => Cot::$L['Title'],
                    'element' => cot_inputbox(
                        'text',
                        'title',
                        $this->extraData['title'] ?? '',
                        ['class' => 'form-control file-edit', 'placeholder' => Cot::$L['Title']]
                    )
                ]
            ];

            // Extra fields
            if (!empty(Cot::$extrafields[File::tableName()])) {
                foreach (Cot::$extrafields[File::tableName()] as $exfld) {
                    $fieldName = $exfld['field_name'];
                    $value = $this->extraData[$fieldName] ?? null;
                    $title = Cot::$L['files_' . $exfld['field_name'] . '_title'] ?? $exfld['field_description'];

                    $result[$fieldName] = cot_build_extrafields_data('files', $exfld, $value);
                    $result['editForm'][] = [
                        'title' => $title,
                        'element' => cot_build_extrafields($exfld['field_name'], $exfld, $value),
                    ];
                }
            }
            // /Extra fields
        }

        if (!empty($this->errors)) {
            $result['error'] = implode('; ', $this->errors);
        }

        if (!empty($this->debug)) {
            $result['debug'] = $this->debug;
        }

        /* === Hook === */
        foreach (cot_getextplugins('files.dto.to_array.done') as $pl) {
            include $pl;
        }
        /* ===== */

        return $result;
    }
}