<?php
/* ====================
[BEGIN_COT_EXT]
Code=files
Name=Files
Description=Personal File Space and attach files to posts and pages
Category=files-media
Version=2.1.1
Date=2025-03-13
Author=Alexey Kalnov https://github.com/Alex300
Copyright=(c) 2014-2025 Lily Software https://lily-software.com
Notes=DO NOT FORGET to create a writable folder for the files
Auth_guests=R
Lock_guests=12345A
Auth_members=RW
Recommends_modules=page,users
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
folder=01:string::datas/files:Directory for files
prefix=02:string::file-:File prefix
checkAllowedType=03:radio::1:
exts=04:text::avif,bmp,gif,jpg,jpeg,heic,heif,png,tga,tpic,wbmp,webp,xbm,zip,rar,7z,gz,bz2,pdf,djvu,mp3,ogg,wma,avi,divx,mpg,mpeg,swf,txt,doc,docx,xls,xlsx:Allowed extensions (comma separated, no dots and spaces)
fixExtensionsByMime=05:radio::1:Fix files extensions by mime type
loadAssetsGlobally=06:radio::0:
maxFoldersPerPage=07:string::15:
pfs_winclose=08:radio::0:

upl_separator=10:separator:::

autoupload=11:radio::0:Start uploading automatically
sequential=12:radio::0:Sequential uploading instead of concurrent
chunkSize=13:string::2000000:Chunk size (in bytes) (0 - Disable chunked file uploads)

img_separator=20:separator:::

image_convert=21:select:jpg,png,webp,no:jpg:Convert images
image_to_convert=22:text::avif,bmp,heic,heif,tga,tpic,wbmp,xbm:Image extensions/types to convert to jpeg, if the option above is enabled. If empty, all images will be converted. (comma separated, no dots and spaces)
image_resize=23:radio::0:Resize uploading images
image_maxwidth=24:string::3840:Image max width for resize
image_maxheight=25:string::2160:Image max height for resize
imageResizeInBrowser=26:radio::1:Resize uploading images in browser if possible
quality=27:string::85:JPEG quality in %

th_separator=30:separator:::

thumbs=31:radio::1:Display image thumbnails
thumb_width=32:string::160:Default thumbnail width
thumb_height=33:string::160:Default thumbnail height
thumb_framing=34:select:height,width,inset,outbound:inset:Default thumbnail framing mode
upscale=39:radio::0:Upscale images smaller than thumb size

wm_separator=40:separator:::

thumb_watermark=41:string:::Add watermark for thumbs (Filename. Empty for disable)
thumb_wm_widht=42:string::200:Image max width for resize
thumb_wm_height=43:string::200:Image max width for resize

av_separator=50:separator:::

avatar_width=51:string::160:Default avatar width
avatar_height=52:string::160:Default avatar height
avatar_framing=53:select:height,width,inset,outbound:outbound:Default avatar framing mode
[END_COT_EXT_CONFIG]
==================== */

/**
 * @todo test extrafields
 * @todo min PHP version 7.4
 *
 * Files module for Cotonti Siena
 *
 * @package Files
 *
 * @author Alexey Kalnov <kalnovalexey@yandex.ru>
 * @copyright (c) 2014-2025 Lily Software https://lily-software.com
 *
 * В настройках все размеры указываются в байтах
 * в ограничениях 0 - не ограничено, -1 - запрещено
 *
 * Файлы по папкам делятся так:
 * source     = 'pfs' / 'sfs'
 * source_id  = id папки
 * user_id    = id пользователя реально добавившего файл или 0 для sfs
 *
 * В свой pfs добавлять файлы пользователь может только сам
 *
 * Flysystem adapter для Яндекс.Диск
 * https://github.com/jack-theripper/yandex-disk-flysystem
 */
defined('COT_CODE') or die('Wrong URL');