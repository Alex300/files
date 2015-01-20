<!-- BEGIN: MAIN -->
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="css/jquery.fileupload-ui-noscript.css"></noscript>

<!-- Shim to make HTML5 elements usable in older Internet Explorer versions -->
<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

<div class="row">
    <!-- The file upload form used as target for the file upload widget -->
    <div class="col-xs-12 fileupload" id="fileupload_{UPLOAD_SOURCE}_{UPLOAD_ITEM}_{UPLOAD_FIELD}" data-url="{UPLOAD_ACTION}">
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="col-xs-12">
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

            <div class="col-xs-12">
                <div id="dropzone" class="dropzone fade well">

                    <div class="hidden-xs"><span class="glyphicon glyphicon-import"></span> {PHP.L.files_draghere}</div>

                    <!-- The global progress state -->
                    <div class="fileupload-progress fade">
                        <!-- The global progress bar -->
                        <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-success" style="width:0%;"></div>
                        </div>
                        <!-- The extended global progress state -->
                        <div class="progress-extended hidden-xs">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- The table listing the files available for upload/download -->
        <table id="filesTable_" role="presentation" class="table table-striped filesTable"><tbody class="files"></tbody></table>
    </div>
</div>

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
<!-- END: MAIN -->