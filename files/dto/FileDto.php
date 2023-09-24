<?php

namespace cot\modules\files\dto;

use cot\modules\files\model\File;
use cot\modules\files\services\FileService;
use image\Image;

/**
 * Class for transfer file data between methods (functions) or to the frontend
 * @package Files
 */
class FileDto
{
    private $errors = [];

    private $debug = [];

    /**
     * @var File
     */
    private $file;

    /**
     * @var string Full path to file relative to site root. (Note: File model use path relative to Cot::$cfg['files']['folder'])
     */
    public $path;
    public $file_name;
    public $original_name;
    public $ext;
    public $isImage;
    public $size;
    public $mimeType;

    private $extraData = [];

    public function __get($name)
    {
        return isset($this->extraData[$name]) ? $this->extraData[$name] : null;
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

    public function getDebug()
    {
        return $this->debug;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getFullName()
    {
        return $this->path . '/' . $this->file_name;
    }

    public function setFullName($fullPath)
    {
        $this->path = dirname($fullPath);
        $this->file_name = basename($fullPath);
        $this->ext = mb_strtolower(cot_files_get_ext($this->file_name));
    }

    /**
     * @param File $file
     * @return self
     */
    public static function createFromFile(File $file)
    {
        $dto = new FileDto();
        $dto->loadFromFile($file);
        return $dto;
    }

    public function loadFromFile(File $file)
    {
        $this->file = $file;
        $this->extraData['id'] = (int) $file->id;
        $this->path = \Cot::$cfg['files']['folder'] . '/' . $file->path;
        $this->file_name = $file->file_name;
        if (isset($file->original_name)) {
            $this->original_name = $file->original_name;
        }
        if (isset($file->ext)) {
            $this->ext = mb_strtolower($file->ext);
        }
        if (isset($file->is_img)) {
            $this->isImage = $file->is_img;
        }
        if (isset($file->size)) {
            $this->size = $file->size;
        }
        if (isset($file->title)) {
            $this->extraData['title'] = $file->title;
        }

        $this->mimeType = cot_files_getMime($this->getFullName());

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
     * @return array
     */
    public function toArray()
    {
        $id = 0;
        if (!empty($this->extraData['id'])) {
            $id = $this->extraData['id'];
        } elseif (!empty($this->file)) {
            $id = $this->file->id;
        }

        $result = !empty($this->extraData) ? $this->extraData : [];

        $fileExists = false;
        if (!empty($this->path) && !empty($this->file_name)) {
            $fileExists = file_exists($this->getFullName());
//            if (!$fileExists) {
//                $this->addError('File is not exists');
//            }
        }

        if (!empty($this->original_name)) {
            $result['name'] = htmlspecialchars($this->original_name);
        }

        if (!empty($this->ext)) {
            $result['ext'] = $this->ext;
        }

        if (!empty($this->size)) {
            $result['size'] = $this->size;
            //$result['size'] = $fileExists ? $this->size : 0;
        }

        if (isset($this->isImage)) {
            $result['isImage'] = $this->isImage && in_array(mb_strtolower($this->ext), Image::supportedFormats()) ? 1 : 0;
        }

        if (!empty($this->path) && !empty($this->file_name)) {
            $result['url'] = \Cot::$cfg['mainurl'] . '/' . $this->getFullName();

            $thumbParam = null;

            if (!empty($this->file) && $this->file->id > 0) {
                $thumbParam = $this->file;
            } elseif (!empty($this->extraData['id'])) {
                $thumbParam = $this->extraData['id'];
            }

            if (!$fileExists) {
                $result['thumbnail'] = '';
            } elseif (($result['isImage'] && $thumbParam)) {
                $thumbPath = cot_files_thumb($thumbParam);
                $result['thumbnail'] = '';
                if ($thumbPath && file_exists($thumbPath)) {
                    $result['thumbnailUrl'] = $result['thumbnail'] = \Cot::$cfg['mainurl'] . '/' . $thumbPath;
                    $result['thumbnailUrl'] .= '?lastmod=' . filemtime($thumbPath);
                }
            } else {
                $result['thumbnail'] = \Cot::$cfg['mainurl'] . '/' . FileService::typeIcon($this->ext);
            }

            if ($fileExists) {
                $result['lastmod'] = filemtime($this->getFullName());
            }
        }

        if ($id > 0) {
            $result['deleteUrl'] = \Cot::$cfg['mainurl'] . '/index.php?e=files&m=upload&id=' . $id . '&_method=DELETE&x=' . \Cot::$sys['xk'];
            $result['deleteType'] = 'POST';

            $result['editForm'] = [
                [
                    'title' => \Cot::$L['Title'],
                    'element' => cot_inputbox(
                        'text',
                        'title',
                        isset($this->extraData['title']) ? $this->extraData['title'] : '',
                        ['class' => 'form-control file-edit', 'placeholder' => \Cot::$L['Title']]
                    )
                ]
            ];

            // Extra fields
            if (!empty(\Cot::$extrafields[File::tableName()])) {
                foreach (\Cot::$extrafields[File::tableName()] as $exfld) {
                    $fieldName = $exfld['field_name'];
                    $value = isset($this->extraData[$fieldName]) ? $this->extraData[$fieldName] : null;
                    $title = isset(\Cot::$L['files_' . $exfld['field_name'] . '_title'])
                        ? \Cot::$L['files_' . $exfld['field_name'] . '_title']
                        : $exfld['field_description'];

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