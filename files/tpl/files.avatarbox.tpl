<!-- BEGIN: MAIN -->
<div
        class="file-upload-avatar row"
        data-files-form-param="{UPLOAD_PARAM}"
        data-files-form-x="{UPLOAD_X}"
        data-files-avatar-template="{AVATAR_TEMPLATE}"
>
    <div class="files-avatar-container">
        <div class="files-avatar">{AVATAR}</div>
    </div>
    <div>
        <span class="btn btn-success fileinput-button">
            <i class="glyphicon glyphicon-plus"></i>
            <span>{PHP.L.files_select_avatar}...</span>
            <input class="file-upload-avatar-input" type="file" name="files[]" data-url="{UPLOAD_ACTION}">
        </span>
        <div class="progress progress-striped active hidden margintop10">
            <div class="progress-bar progress-bar-success"></div>
        </div>
    </div>
</div>
<!-- END: MAIN -->