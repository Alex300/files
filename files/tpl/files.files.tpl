<!-- BEGIN: MAIN -->
<!DOCTYPE HTML>
<!--
/*
 * jQuery File Upload Plugin Demo 9.1.0
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
    {PHP.out.head_head}
    <!-- Generic page styles -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/tpl/widget.css?{PHP.cot_modules.files.version}">
    <!-- CSS adjustments for browsers with JavaScript disabled -->
    <noscript><link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload-noscript.css?{PHP.cot_modules.files.version}"></noscript>
    <noscript><link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/lib/upload/css/jquery.fileupload-ui-noscript.css?{PHP.cot_modules.files.version}"></noscript>

    <!-- Shim to make HTML5 elements usable in older Internet Explorer versions -->
    <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
</head>
<body class="files-widget">
<div class="container">
    <!-- The file upload form used as target for the file upload widget -->
    <form class="fileupload" id="fileupload_{UPLOAD_SOURCE}_{UPLOAD_ITEM}_{UPLOAD_FIELD}" data-url="{UPLOAD_ACTION}"
          method="POST" enctype="multipart/form-data" data-url="{UPLOAD_ACTION}">
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="col-sm-12">
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
            <div class="col-sm-12 text-center small">
                <span class="glyphicon glyphicon-import"></span> {PHP.L.files_draghere}
            </div>
            <!-- The global progress state -->
            <div class="col-sm-12 fileupload-progress fade">
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

<script src="js/jquery.min.js"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation -->
<script src="{PHP.cfg.modules_dir}/files/lib/bootstrap/js/bootstrap.min.js?{PHP.cot_modules.files.version}"></script>
{PHP.out.footer_rc}
<!-- Cotonti config -->
<script type="text/javascript">
    if (filesConfig === undefined) {
        var filesConfig = {
            exts: $.map('{UPLOAD_EXTS}'.split(','), $.trim),
            //accept: '{UPLOAD_ACCEPT}',
            maxsize: {UPLOAD_MAXSIZE},
            previewMaxWidth: {UPLOAD_THUMB_WIDTH},
            previewMaxHeight: {UPLOAD_THUMB_HEIGHT},
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
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
</body>
</html>
<!-- END: MAIN -->