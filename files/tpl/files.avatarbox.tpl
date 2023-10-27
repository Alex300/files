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

                let progressElement = document.querySelector('#files-avatar-upload #progress');

                function showError(message) {
                    progressElement.classList.add('hidden');

                    let div = document.createElement('div');
                    div.className = 'files-avatar-upload-error';
                    div.innerHTML = '<span class="label label-danger">Error</span> ' + message;

                    progressElement.after(div);
                }

                let options = {
                    dataType: 'json',
                    maxChunkSize: {UPLOAD_CHUNK},
                    formData: {
                        param: '{UPLOAD_PARAM}',
                        x: '{UPLOAD_X}'
                    },
                    acceptFileTypes: /(\.|\/)(avif|bmp|gif|jpe?g|heic|heif|png|svg|tga|webp)$/i,
                    disableValidation: false
                };

                <!-- IF {PHP.cfg.files.image_resize} == 1 AND {PHP.cfg.files.imageResizeInBrowser} == 1 AND {PHP.cfg.files.image_maxwidth} > 0 AND {PHP.cfg.files.image_maxheight} > 0 -->
                options.loadImageFileTypes = /^image\/(avif|bmp|gif|jpeg|heic|heif|png|svg\+xml|x-tga|webp)$/;
                options.loadImageMaxFileSize = 60000000; // 60MB
                options.disableImageResize = false;
                options.imageMaxWidth = {PHP.cfg.files.image_maxwidth};
                options.imageMaxHeight = {PHP.cfg.files.image_maxheight};
                <!-- ENDIF -->

                $('#fileupload').fileupload(options)
                .on('fileuploadprocessalways', function (e, data) {
                    // Validation result
                    let currentFile = data.files[data.index];
                    if (data.files.error && currentFile.error) {
                        // there was an error
                        showError(currentFile.error);
                    }

                }).on('fileuploadprogressall', function (e, data) {
                    const progress = parseInt(data.loaded / data.total * 100, 10);

                    $('.files-avatar-upload-error').remove();

                    $('#files-avatar-upload #progress').removeClass('hidden');
                    $('#files-avatar-upload #progress .progress-bar').css( 'width', progress + '%' );

                }).on('fileuploaddone', function (e, data) {
                    progressElement.classList.add('hidden');

                    $.each(data.result.files, function (index, file) {
                        const error =  file.error || false;
                        if (error) {
                            showError(file.error);
                            return;
                        } else {
                            $('.files-avatar-upload-error').remove();
                        }

                        const avatarTemplate = '{PHP|str_replace("'", "\'", {PHP.R.files_user_avatar})}';
                        let avatarElement = document.getElementById('files-avatar');
                        if (avatarElement !== null) {
                            avatarElement.innerHTML = avatarTemplate.replace('{$src}', file.thumbnailUrl)
                                .replace('{$alt}', file.name);
                        }
                    });

                }).on('fileuploadfail', function (e, data) {
                    showError('File upload error');

                }).prop('disabled', !$.support.fileInput)
                        .parent().addClass($.support.fileInput ? undefined : 'disabled');
            });
        </script>
    </div>
</div>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
<!-- END: MAIN -->