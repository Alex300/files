<!-- BEGIN: MAIN -->
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{PHP.L.files_attachments}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {PHP|cot_filesLoadBootstrap}
    {PHP.out.head_head}
    <!-- Generic page styles -->
    <link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/tpl/widget.css?{PHP.cot_modules.files.version}">
</head>
<body class="files-widget">
<div class="container">
    <!-- The file upload form used as target for the file upload widget -->
    <form
            class="file-upload"
            id="fileupload_{UPLOAD_SOURCE}_{UPLOAD_ITEM}_{UPLOAD_FIELD}"
            method="POST"
            enctype="multipart/form-data"
            data-url="{UPLOAD_ACTION}"
            data-files-form-param="{UPLOAD_PARAM}"
            data-files-form-x="{UPLOAD_X}"
            data-files-source="{UPLOAD_SOURCE}"
            data-files-source-id="{UPLOAD_ITEM}"
            data-files-field="{UPLOAD_FIELD}"
    >
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
{PHP.out.footer_rc}
</body>
</html>
<!-- END: MAIN -->