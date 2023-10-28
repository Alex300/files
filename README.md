Files
=====

Files module for - [Cotonti CMF](https://www.cotonti.com) 

Module page: https://lily-software.com/free-scripts/cotonti-files (russian)

The module organizes the management of attachments to pages, posts and any other objects, photo galleries creation for pages and posts. 
It also combines the users personal file storage, site file storage for site administrators, avatars and user albums management.  
It is a replacement for the PFS module, the "User images" plugin included in the Cotonti "box" and the "Attach2" plugin.

### Features

- The ability to store files both in the local file system on the server and in remote file storages, such as Amazon AWS S3,
  Azure Blob Storage, Yandex Objet Storage, BunnyCdn, Google Cloud Storage, WebDAV, FTP, SFTP, etc.
- Modern upload mechanism based on [jQuery File Upload](https://github.com/blueimp/jQuery-File-Upload)
- Support for multiloading.
- Dragging files with the mouse directly to the upload form.
- Downloading files in parts ("Chunks") allows you to bypass the 2 MB file upload limit via $_POST.
- Use both the [GD library](https://www.php.net/manual/ru/book.image.php) and [ImageMagick](https://www.php.net/manual/ru/book.imagick.php)
  for image processing. The last one supports a larger number of image formats, incl.  **HEIC/HEIF**, which are used in Apple iPhone smartphones.
- Downscale large images to the specified size when uploading.
- Using watermark for images.
- Simple integration via function callback from a template.
- Downloads are counted and occur with the original file name.
- Create images for preview (thumbnail) and easily configure it.
- Extrafeilds support.
- Special BBCodes for inserting images and galleries into the text of pages, regardless of the selected parser.
- Supporting image formats by the GD library: .avif, .bmp, .gif, .jpg, .png, .tga, .tpic, .wbmp, .webp, .xbm
- Supporting image [formats by the Imagick library](https://imagemagick.org/script/formats.php).


### System requirements and limitations

- Installed [Cotonti Siena](https://www.cotonti.com) version not lower than 0.9.24 beta. (Files v. 1.0.8 is compatible with Cotonti Siena 0.9.18)
- PHP version **7.4** and higher. (Files v. 1.0.8 is compatible with PHP v 5.4)
 **.bmp** is supported by GD library on PHP >= 7.2,
 **.tga** is supported by GD library on PHP >= 7.4, thumbnails creating in JPEG
 **.avif** is supported by GD library on PHP >= 8.1
- To use remote file storage, [Flysystem](https://flysystem.thephpleague.com/) and the appropriate adapter must be installed
- jQuery File Upload shows preview on image uploading not for all image formats.
- [Cotonti Lib](https://lily-software.com/free-scripts/cotonti-lib) library version **2.2.x** installed on your site.
- The module is **not compatible** with the PFS module and the "User images" plugin. They should not be installed if you are going to
  use the "Files" module.
- Templates "in a box" are designed to use a theme implemented on the [Bootstrap](https://lily-software.com/go.php?getbootstrap.com)
  v3 framework (Installed separately)

_Note_. Due to the different database structure and the way files are stored, the "Files" module will not automatically pick up
files present on the site from the "PFS" module and the "User images" and "Attach2" plugins.

 
#### Data transfer from the "PFS" module and the "User images" and "Attach2" plugins.

If you want to use the "Files" module for an existing site, you should use a converter to transfer data between modules. 
You can [contact me here](https://lily-software.com/contact). 
At the moment, there is a converter for transferring data from these three extensions, because they 
are one of the most frequently used. But you can make a converter for any other module or plugin.

### Installation

- Download the archive and unpack it into the folder with the modules of your site.
- In the "Administration panel > Extensions" menu, install it by clicking "install" button.
- In the module settings, select the settings that suit you.
- In the control panel templates "**admin.users.tpl**" and "**admin.users.tpl**" and also in some others, create tags in
  accordance with the recommendations on the module page in the control panel.
  (https://<your_domain>/admin/extensions?a=details&mod=files)
- If necessary, on the user group editing page, set limits on the size of the uploaded file, the total user file space
  and the maximum number of attachments per object. Each user group can have its own permissions.
- Note that the sizes are specified in bytes, '0' means "unlimited", and '-1' means prohibition.
- Create a **directory for files** on your server ("**datas/files**" is used by default) and set write permissions for PHP
  (for example, CHMOD 775 or CHMOD 777).
- Use **Resources::setAlias()** in your theme to declare aliases for Bootstrap files if you plan to use it and it is installed
  in a folder, different from the one in the Resources class.

