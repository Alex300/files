<!-- BEGIN: MAIN -->

<!-- IF {PHP.files_widget_present} == '' -->
<link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/tpl/link.css">
<div class="modal fade" id="dlgFiles" tabindex="-1" role="dialog" aria-labelledby="dlgFilesLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="dlgFilesLabel">{PHP.L.Files}</h4>
            </div>

            <div class="modal-body"><iframe id="filesModalContent" src=""></iframe></div>

            <div class="modal-footer text-center">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> {PHP.L.Close}</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on("click", ".filesLink", function(event){
        event.preventDefault();
        var url = $(this).attr('href');
        var title= $(this).attr('title');

        $('#filesModalContent').html('').attr('src', url);
        $('#dlgFilesLabel').html(title);
        $('#dlgFiles').modal();
        return false;
    });

    $('#dlgFiles').on('hidden.bs.modal', function (e) {
        window.location.reload();
    })

</script>
<!-- ENDIF -->

<a href="{FILES_URL}" class="filesLink"  title="{PHP.L.files_attachments}">{PHP.L.files_attach}</a>
<!-- END: MAIN -->
