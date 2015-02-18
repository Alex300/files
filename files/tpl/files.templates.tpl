<!-- BEGIN: MAIN -->
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td>
            <span class="preview"></span>
        </td>
        <td>
            <p class="name">{%=file.name%}</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
            <strong class="error text-danger"></strong>
        </td>
        <td class="hidden-xs">
            <p class="size">{PHP.L.files_processing}...</p>
        </td>
        <td>
            <div class="visible-xs"><span class="size">{PHP.L.files_processing}...</span></div>
            {% if (!i && !o.options.autoUpload) { %}
                <button class="btn btn-primary start" disabled>
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>{PHP.L.files_start}</span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>{PHP.L.Cancel}</span>
                </button>
            {% } %}
        </td>
    </tr>
    {% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade" id="file_{%=file.id%}" data-id="{%=file.id%}" data-url="{%=file.url%}"
            data-thumbnail="{%=file.thumbnail%}" data-name="{%=file.name%}">
        <td>
            <span class="preview">
                {% if (file.thumbnailUrl) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                {% } %}
            </span>
        </td>
        <td>
            <div style="position: relative">
            <p class="name">
                {% if (file.url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                {% } else { %}
                    <span>{%=file.name%}</span>
                {% } %}
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger">Error</span> {%=file.error%}</div>
            {% } else { %}

                {% for (var j=0, element; element=file.editForm[j]; j++) { %}
                    <label>{%=element.title%}</label> {%#element.element%}
                {% } %}

                {% if (window.FormData) { %}
                <input type="file" name="replacement" class="files-replace-file" data-id="{%=file.id%}" id="files-file{%=file.id%}">
                {% } %}
            {% } %}
            </div>
        </td>
        <td class="hidden-xs">
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
        <td>
            <div class="visible-xs"><span class="size">{%=o.formatFileSize(file.size)%}</span></div>
            {% if (file.deleteUrl) { %}

                <!-- IF {IS_STANDALONE} == 1 -->
                <div class="btn-group">
                {PHP|cot_rc('files_pfs_link_addfile')}
                {% if (file.isImage==1) { %}
                {PHP|cot_rc('files_pfs_link_addthumb')} {PHP|cot_rc('files_pfs_link_addpix')}
                {% } %}
                </div>
                <!-- ENDIF -->

                <button class="btn btn-danger delete btn-sm" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %} title="{PHP.L.Delete}" data-toggle="tooltip">
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>{PHP.L.Delete}</span>
                </button>
                <input type="checkbox" name="delete" value="1" class="toggle">

            {% } else { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>{PHP.L.Cancel}</span>
                </button>
            {% } %}
            <div style="margin-top: 10px">
                <button type="button" data-id="{%=file.id%}" class="btn btn-primary files-replace-button"
                    title="{PHP.L.files_replace}" data-toggle="tooltip" style="display:none">
                <i class="glyphicon glyphicon-retweet"></i> <span>{PHP.L.files_replace}</span></button>
            </div>

        </td>
    </tr>
{% } %}
</script>
<!-- END: MAIN -->