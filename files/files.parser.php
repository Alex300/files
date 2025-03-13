<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=parser.last
[END_COT_EXT]
==================== */

/**
 * bb-codes processing
 *
 * @package Files
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2014
 * @license BSD
 *
 * @todo дописать
 */

use cot\modules\files\models\File;

defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('files', 'module');

if (!function_exists('files_thumb_bbcode')) {

    /**
     * Replaces att_thumb bbcode with the thumbnail image alone
     *
     * @param array $m
     * @global File[] $files_item_cache
     * @return string
     */
    function files_thumb_bbcode($m)
    {
		global $files_item_cache;

		parse_str(htmlspecialchars_decode($m[1]), $params);

		if (!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0) {
			return $m[0] . 'err';
		}
		$params['id'] = (int) $params['id'];

		$src = cot_filesThumbnailUrl(
            $params['id'],
            $params['width'] ?? null,
            $params['height'] ?? null,
            $params['frame'] ?? null
        );
		if (!$src) {
			return $m[0].'err2';
		}
		$html = '<img src="'.$src.'"';
		if (empty($params['alt'])) {
			if (!isset($files_item_cache[$params['id']])) {
                $row = File::getById($params['id']);
				if (!$row || !$row->is_img) {
                    return $m[0].'err';
                }
				$files_item_cache[$params['id']] = $row;
			}
			$params['alt'] = $files_item_cache[$params['id']]->file_title;
		}
		$html .= ' alt="' . htmlspecialchars($params['alt'] ?? '') . '"';
		if (!empty($params['class'])) {
			$html .= ' class="' . $params['class'] . '"';
		}
		$html .= ' />';
		return $html;
	}

    /**
     * Replaces att_image bbcode with a thumbnail wrapped with a link to full image
     *
     * @param array $m
     * @global File[] $files_item_cache
     * @return string
     */
	function files_image_bbcode($m)
    {
		global $files_item_cache;

		parse_str(htmlspecialchars_decode($m[1]), $params);

		if (!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0) {
			return $m[0].'err';
		}
		$params['id'] = (int) $params['id'];

		if (!isset($files_item_cache[$params['id']])){
            $row = File::getById($params['id']);
            if (!$row || !$row->is_img) {
                return $m[0].'err';
            }
			$files_item_cache[$params['id']] = $row;
        } else {
			$row = $files_item_cache[$params['id']];
		}

		$img = files_thumb_bbcode($m);

        $path = Cot::$cfg['files']['folder'] . '/' . $row->fullName;
        $url  = Cot::$cfg['mainurl'] . '/' . $path;

        return '<a href="'.$url.'" title="' . htmlspecialchars($row->title) . '" rel="att_image_preview">' . $img . '</a>';
	}

    /**
     * Replaces pfs_gallery bbcode with the thumbnail gallery
     * @param array $m
     * @return string
     */
    function pfs_gallery_bbcode($m)
    {
        parse_str(htmlspecialchars_decode($m[1]), $params);

        if (!isset($params['f']) || !is_numeric($params['f']) || $params['f'] <= 0) {
            return $m[0].'err';
        }
        $params['f'] = (int) $params['f'];
        $folder = files_models_Folder::getById($params['f']);
        if (!$folder) {
            return $m[0].'err - NotFound';
        }
        $source = $folder->user_id > 0 ? 'pfs' : 'sfs';

        $tpl = 'files.gallery';
        if (!empty($params['tpl'])) {
            $tpl = $params['tpl'];
        }

        $order = '';
        if (!empty($params['order'])){
            if($params['order'] == 'desc'){
                $order = 'file_order DESC';
            }elseif($params['order'] == 'rand'){
                $order = 'RAND()';
            }
        }

        $html = cot_filesGallery($source, $folder->ff_id, '', $tpl, 0, $order);
        if (!$html) return $m[0].'err2';

        return $html;
    }
}

if (!empty($text)) {
    $text = preg_replace_callback('`\[files_thumb\?(.+?)\]`i', 'files_thumb_bbcode', $text);
    $text = preg_replace_callback('`\[files_image\?(.+?)\]`i', 'files_image_bbcode', $text);
    $text = preg_replace_callback('`\[pfs_gallery\?(.+?)\]`i', 'pfs_gallery_bbcode', $text);
}