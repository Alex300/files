<!-- BEGIN: MAIN -->
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="css/jquery.fileupload-ui-noscript.css"></noscript>

<!-- Shim to make HTML5 elements usable in older Internet Explorer versions -->
<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

<div class="row" id="files-avatar-upload">
    <div class="col-xs-4" id="files-avatar">{AVATAR}</div>
    <div class="col-xs-8">
        <span class="btn btn-success fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span>{PHP.L.files_select_avatar}...</span>
            <input id="fileupload" type="file" name="files[]" data-url="{UPLOAD_ACTION}">
        </span>
        <div class="progress progress-striped active hidden margintop10" id="progress">
            <div class="progress-bar progress-bar-success"></div>
        </div>
        <script>
            $(function () {
                'use strict';

                var uplId = 'pfs_0_';

                $('#fileupload').fileupload({
                    dataType: 'json',
                    maxChunkSize: filesConfig[uplId]['chunk'],
                    formData: {
                        param: filesConfig[uplId].param,
                        x: filesConfig['x']
                    },
                    acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
                }).on('fileuploadprocessalways', function (e, data) {

                    // Обработка перед загрузкой. Пока не нужна

                }).on('fileuploadprogressall', function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);

                    $('#files-avatar-upload-error').remove();

                    $('#files-avatar-upload #progress').removeClass('hidden');
                    $('#files-avatar-upload #progress .progress-bar').css( 'width', progress + '%' );

                }).on('fileuploaddone', function (e, data) {

                    $('#files-avatar-upload #progress').addClass('hidden');

                    $.each(data.result.files, function (index, file) {
                        var error =  file.error || false;
                        if(error){
                            $('<div id="files-avatar-upload-error"><span class="label label-danger">Error</span> '+ file.error +'</div>').
                                insertAfter($('#files-avatar-upload #progress'));
                            return;
                        }else{
                            $('#files-avatar-upload-error').remove();
                        }
                        $('#files-avatar').html('');
                        $('<img/>', {src: file.thumbnailUrl, alt: file.name, 'class': 'avatar' }).appendTo('#files-avatar');
                    });

                }).on('fileuploadfail', function (e, data) {

                    $('#files-avatar-upload #progress').addClass('hidden');

                    $('<div id="files-avatar-upload-error"><span class="label label-danger">Error</span> File upload error</div>').
                            insertAfter($('#files-avatar-upload #progress'));
                }).prop('disabled', !$.support.fileInput)
                        .parent().addClass($.support.fileInput ? undefined : 'disabled');
            });
        </script>
    </div>
</div>

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
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
<!-- END: MAIN -->