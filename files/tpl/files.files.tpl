<!-- BEGIN: MAIN -->
<!DOCTYPE HTML>
<!--
/*
 * jQuery File Upload Plugin Demo 9.0.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
-->
<html lang="en">
<head>
    <!-- Force latest IE rendering engine or ChromeFrame if installed -->
    <!--[if IE]>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <![endif]-->
    <meta charset="utf-8">
    <title>{PHP.L.files_attachments}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap styles -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/bootstrap/css/bootstrap.min.css?{PHP.cot_modules.files.version}">
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/bootstrap/css/bootstrap-theme.min.css?{PHP.cot_modules.files.version}">
    <!-- IF 0 == 1 -->
    <!-- blueimp Gallery styles -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/Gallery/css/blueimp-gallery.min.css?{PHP.cot_modules.files.version}">
    <!-- ENDIF -->
    <!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload.css?{PHP.cot_modules.files.version}">
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload-ui.css?{PHP.cot_modules.files.version}">
    <!-- Generic page styles -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/tpl/widget.css?{PHP.cot_modules.files.version}">
    <!-- CSS adjustments for browsers with JavaScript disabled -->
    <noscript><link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload-noscript.css?{PHP.cot_modules.files.version}"></noscript>
    <noscript><link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload-ui-noscript.css?{PHP.cot_modules.files.version}"></noscript>

    <!-- Shim to make HTML5 elements usable in older Internet Explorer versions -->
    <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
</head>
<body>
<div class="container">
    <!-- The file upload form used as target for the file upload widget -->
    <form class="fileupload" id="fileupload_{FILES_SOURCE}_{FILES_ITEM}_{FILES_FIELD}" action="{FILES_ACTION}"
          method="POST" enctype="multipart/form-data" data-url="{FILES_ACTION}">
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="col-lg-7">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class="btn btn-success fileinput-button">
                    <i class="glyphicon glyphicon-plus"></i>
                    <span>{PHP.L.files_add}...</span>
                    <input type="file" name="files[]" <!-- IF {UPLOAD_LIMIT} > 0 -->multiple<!-- ENDIF -->>
                </span>
                <button type="submit" class="btn btn-primary start">
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>{PHP.L.files_start_upload}</span>
                </button>
                <button type="reset" class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>{PHP.L.files_cancel}</span>
                </button>
                <button type="button" class="btn btn-danger delete">
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>{PHP.L.Delete}</span>
                </button>
                <input type="checkbox" class="toggle">
                <!-- The global file processing state -->
                <span class="fileupload-process"></span>
            </div>
            <!-- The global progress state -->
            <div class="col-lg-5 fileupload-progress fade">
                <!-- The global progress bar -->
                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-success" style="width:0%;"></div>
                </div>
                <!-- The extended global progress state -->
                <div class="progress-extended">&nbsp;</div>
            </div>
        </div>

        <!-- The table listing the files available for upload/download -->
        <table id="filesTable_" role="presentation" class="table table-striped filesTable"><tbody class="files"></tbody></table>
    </form>
</div>

{ATTACH_TEMPLATES}

<script src="js/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="{PHP.cfg.modules_dir}/files/lib/upload/js/vendor/jquery.ui.widget.js?{PHP.cot_modules.files.version}"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="{PHP.cfg.modules_dir}/files/lib/JavaScript-Templates/tmpl.min.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="{PHP.cfg.modules_dir}/files/lib/JavaScript-Load-Image/js/load-image.min.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="{PHP.cfg.modules_dir}/files/lib/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation -->
<script src="{PHP.cfg.modules_dir}/files/lib/bootstrap/js/bootstrap.min.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- IF 0 == 1 -->
<!-- blueimp Gallery script -->
<script src="http://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js"></script>
<!-- ENDIF -->
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.iframe-transport.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The basic File Upload plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The File Upload processing plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-process.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-image.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The File Upload audio preview plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-audio.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- IF 0 == 1 -->
<!-- The File Upload video preview plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-video.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- ENDIF -->
<!-- The File Upload validation plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-validate.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The File Upload user interface plugin -->
<script src="{PHP.cfg.plugins_dir}/attach2/lib/upload/js/jquery.fileupload-ui.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- Cotonti config -->
<script type="text/javascript">
    if (filesConfig === undefined) {
        var filesConfig = {
            exts: $.map('{UPLOAD_EXTS}'.split(','), $.trim),
            //accept: '{UPLOAD_ACCEPT}',
            maxsize: {UPLOAD_MAXSIZE},
            autoUpload: {PHP.cfg.files.autoupload},
            sequential: {PHP.cfg.files.sequential},
            'x':    '{UPLOAD_X}'
        };
    }
    filesConfig.{UPLOAD_ID} = {
        source: '{UPLOAD_SOURCE}',
        item:   {UPLOAD_ITEM},
        field:  '{UPLOAD_FIELD}',
        limit:  {UPLOAD_LIMIT},
        chunk:  {UPLOAD_CHUNK},
        param:  '{UPLOAD_PARAM}'
    };
</script>
<!-- Table Drag&Drop plugin for reordering -->
<script type="text/javascript" src="js/jquery.tablednd.min.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The main application script -->
<script src="{PHP.cfg.plugins_dir}/attach2/js/attach2.js?{PHP.cot_plugins_enabled.attach2.version}"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
</body>
</html>
<!-- END: MAIN -->