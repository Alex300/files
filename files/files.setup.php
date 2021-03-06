<?php
/* ====================
[BEGIN_COT_EXT]
Code=files
Name=Files
Description=Personal File Space and attach files to posts and pages
Category=files-media
Version=1.0.4
Date=May 03, 2017
Author=Cotonti Team, Kalnov Alexey    <kalnovalexey@yandex.ru>
Copyright=(c) Cotonti Team, Portal30 Studio http://portal30.ru 2014-2017
Notes=DO NOT FORGET to create a writable folder for the files
Auth_guests=R
Lock_guests=12345A
Auth_members=RW
Recommends_modules=page,users
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
folder=01:string::datas/files:Directory for files
prefix=02:string::file_:File prefix
exts=03:text::gif,jpg,jpeg,png,zip,rar,7z,gz,bz2,pdf,djvu,mp3,ogg,wma,avi,divx,mpg,mpeg,swf,txt,doc,docx,xls,xlsx:Allowed extensions (comma separated, no dots and spaces)
filecheck=04:radio::1:
nomimepass=05:radio::1:
maxFoldersPerPage=07:string::15:
pfs_winclose=08:radio::0:

upl_separator=10:separator:::

autoupload=11:radio::0:Start uploading automatically
sequential=12:radio::0:Sequential uploading instead of concurrent
chunkSize=13:string::2000000:Chunk size (in bytes) (0 - Disable chunked file uploads)

img_separator=20:separator:::

image_convert=21:radio::0:Convert all images to JPG on upload
image_resize=22:radio::0:auto:Resize uploaded images
image_maxwidth=23:string::1920:Image max width for resize
image_maxheight=24:string::1080:Image max height for resize
quality=25:string::85:JPEG quality in %

th_separator=30:separator:::

thumbs=31:radio::1:Display image thumbnails
thumb_width=32:string::160:Default thumbnail width
thumb_height=33:string::160:Default thumbnail height
thumb_framing=34:select:height,width,auto,border_auto,crop:auto:Default thumbnail framing mode
thumb_border=36:string::1:
thumb_bordercolor=37:string::999999:
thumb_bgcolor=38:string::FFFFFF:
upscale=39:radio::0:Upscale images smaller than thumb size

wm_separator=40:separator:::

thumb_watermark=41:string:::Add watermark for thumbs (Filename. Empty for disable)
thumb_wm_widht=42:string::200:Image max width for resize
thumb_wm_height=43:string::200:Image max width for resize

av_separator=50:separator:::

avatar_width=51:string::160:Default avatar width
avatar_height=52:string::160:Default avatar height
avatar_framing=53:select:height,width,auto,border_auto,crop:crop:Default avatar framing mode
[END_COT_EXT_CONFIG]
==================== */

/**
 * module Files for Cotonti Siena
 *
 * @package Files
 *
 * @author Cotonti Team
 * @author Kalnov Alexey    <kalnovalexey@yandex.ru>
 * @copyright (c) Cotonti Team
 * @copyright (c) Portal30 Studio http://portal30.ru 2014-2017
 *
 * В настройках все размеры указываются в байтах
 * в ограничениях 0 - не ограничено, -1 - запрещено
 *
 * Файлы по папкам делятся так:
 * file_source = 'pfs' / 'sfs'
 * file_item   = id папки
 * user_id     = id пользователя реально добавившего файл или 0 для sfs
 *
 * В свой pfs добавлять файлы пользователь может только сам
 */
defined('COT_CODE') or die('Wrong URL');