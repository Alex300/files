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
defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('files', 'module');

if (!function_exists('files_thumb_bbcode'))
{

    /**
     * Replaces att_thumb bbcode with the thumbnail image alone
     *
     * @param $m
     * @global files_model_File[] $files_item_cache
     * @return string
     */
    function files_thumb_bbcode($m){
		global $files_item_cache;

		parse_str(htmlspecialchars_decode($m[1]), $params);

		if (!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0)
		{
			return $m[0].'err';
		}
		$params['id'] = (int) $params['id'];
		$src = cot_files_thumb($params['id'], $params['width'], $params['height'], $params['frame']);
		if (!$src)
		{
			return $m[0].'err2';
		}
		$html = '<img src="'.$src.'"';
		if (empty($params['alt']))
		{
			if (!isset($files_item_cache[$params['id']])){
                $row = files_model_File::getById($params['id']);
				if (!$row || !$row->file_img) return $m[0].'err';

				$files_item_cache[$params['id']] = $row;
			}
			$params['alt'] = $files_item_cache[$params['id']]->file_title;
		}
		$html .= ' alt="' . htmlspecialchars($params['alt']) . '"';
		if (!empty($params['class']))
		{
			$html .= ' class="' . $params['class'] . '"';
		}
		$html .= ' />';
		return $html;
	}

    /**
     * Replaces att_image bbcode with a thumbnail wrapped with a link to full image
     *
     * @param $m
     * @global files_model_File[] $files_item_cache
     * @return string
     */
	function files_image_bbcode($m){
		global $files_item_cache;

		parse_str(htmlspecialchars_decode($m[1]), $params);

		if (!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0)
		{
			return $m[0].'err';
		}
		$params['id'] = (int) $params['id'];

		if (!isset($files_item_cache[$params['id']])){
            $row = files_model_File::getById($params['id']);
            if (!$row || !$row->file_img) return $m[0].'err';

			$files_item_cache[$params['id']] = $row;

        }else{
			$row = $files_item_cache[$params['id']];
		}

		$img = files_thumb_bbcode($m);

		$html = '<a href="' . cot_files_path($row->file_source, $row->file_item, $row->file_id, $row->file_ext) .
            '" title="' . htmlspecialchars($row->file_title) . '" rel="att_image_preview">' . $img . '</a>';
		return $html;
	}

    // Replaces pfs_gallery bbcode with the thumbnail galery
    function pfs_gallery_bbcode($m){

        parse_str(htmlspecialchars_decode($m[1]), $params);

        if (!isset($params['f']) || !is_numeric($params['f']) || $params['f'] <= 0)
        {
            return $m[0].'err';
        }
        $params['f'] = (int) $params['f'];
        $folder = files_model_Folder::getById($params['f']);
        if(!$folder) return $m[0].'err - NotFound';
        $source = $folder->user_id > 0 ? 'pfs' : 'sfs';

        $tpl = 'files.gallery';
        if(!empty($params['tpl'])) $tpl = $params['tpl'];

        $order = '';
        if(!empty($params['order'])){
            if($params['order'] == 'desc'){
                $order = 'file_order DESC';
            }elseif($params['order'] == 'rand'){
                $order = 'RAND()';
            }
        }

        $html = cot_files_gallery($source, $folder->ff_id, '', $tpl, 0, $order);
        if (!$html) return $m[0].'err2';

        return $html;
    }
}

$text = preg_replace_callback('`\[files_thumb\?(.+?)\]`i', 'files_thumb_bbcode', $text);
$text = preg_replace_callback('`\[files_image\?(.+?)\]`i', 'files_image_bbcode', $text);
$text = preg_replace_callback('`\[pfs_gallery\?(.+?)\]`i', 'pfs_gallery_bbcode', $text);
