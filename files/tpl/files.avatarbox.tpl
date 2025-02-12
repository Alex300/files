<!-- BEGIN: MAIN -->
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="css/jquery.fileupload-ui-noscript.css"></noscript>

<!-- Shim to make HTML5 elements usable in older Internet Explorer versions -->
<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

<div class="row" id="files-avatar-upload">
    <div id="files-avatar-container">
        <div class="files-avatar">{AVATAR}</div>
        <span id="task-processing"></span>
    </div>
    <div>
        <span class="btn btn-success fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span>{PHP.L.files_select_avatar}...</span>
            <input id="fileupload" type="file" name="files[]" data-url="{UPLOAD_ACTION}">
        </span>
        <div class="progress progress-striped active hidden margintop10" id="progress">
            <div class="progress-bar progress-bar-success"></div>
        </div>
    </div>
</div>
<style>
    /* @todo Удалить после генерации общей CSS */
    #files-avatar-upload {
        display: flex;
        flex-direction: column;
        align-items: flex-start
    }

    #files-avatar-container {
        position: relative;
    }

    #task-processing {
        position: absolute;
        display: none;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: url(modules/files/lib/upload/img/loading.gif) center no-repeat;
        background-size: contain;
    }
</style>
<script>
    $(function () {
        'use strict';

        const progressElement = document.querySelector('#files-avatar-upload #progress');
        const preloaderElement = document.querySelector('#task-processing');
        const uploaderElement = document.querySelector('#fileupload');

        function showError(message) {
            progressElement.classList.add('hidden');

            let div = document.createElement('div');
            div.className = 'files-avatar-upload-error';
            div.innerHTML = '<span class="label label-danger">Error</span> ' + message;

            progressElement.after(div);
        }

        function onUploadStart() {
            const avatarImageElement = document.querySelector('#files-avatar-container img.avatar');
            if (avatarImageElement !== null) {
                avatarImageElement.style.opacity = .4;
            }
            preloaderElement.style.display = 'inline-block';
            uploaderElement.disabled = true;
            $('#files-avatar-upload #progress').removeClass('hidden');
        }

        function onUploadEnd() {
            const avatarImageElement = document.querySelector('#files-avatar-container img.avatar');
            if (avatarImageElement !== null) {
                avatarImageElement.style.opacity = 1;
            }
            preloaderElement.style.display = 'none';
            uploaderElement.disabled = false;
            progressElement.classList.add('hidden');
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

        $(uploaderElement).fileupload(options)
            .on('fileuploadprocessstart', function (e) {
                onUploadStart();
            })
            .on('fileuploadprocessalways', function (e, data) {
                // Validation result
                let currentFile = data.files[data.index];
                if (data.files.error && currentFile.error) {
                    // there was an error
                    showError(currentFile.error);
                }
            })
            .on('fileuploadprogressall', function (e, data) {
                const progress = parseInt(data.loaded / data.total * 100, 10);

                $('.files-avatar-upload-error').remove();
                $('#files-avatar-upload #progress .progress-bar').css( 'width', progress + '%' );

            })
            .on('fileuploaddone', function (e, data) {
                onUploadEnd();

                $.each(data.result.files, function (index, file) {
                    const error =  file.error || false;
                    if (error) {
                        showError(file.error);
                        return;
                    } else {
                        $('.files-avatar-upload-error').remove();
                    }

                    let avatarElement = document.getElementById('files-avatar');
                    if (avatarElement !== null) {
                        const avatarTemplate = '{AVATAR_TEMPLATE}';
                        let avatarRendered = avatarTemplate.replace('__src__', file.thumbnailUrl);
                        avatarRendered = avatarRendered.replace('__alt__', file.name);
                        avatarElement.innerHTML = avatarRendered;
                    }
                });

            })
            .on('fileuploadfail', function (e, data) {
                showError('File upload error');
                onUploadEnd();
            })
            .prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
    });
</script>
<!-- END: MAIN -->