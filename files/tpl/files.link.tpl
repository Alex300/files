<!-- BEGIN: MAIN -->

<!-- IF {PHP.files_widget_present} == '' -->
<link rel="stylesheet" href="{PHP.cfg.modules_dir}/files/tpl/link.css">
<div id="filesModal" class="jqmWindow">
    <div id="filesModalTitle">
        <button id="filesModalClose" class="jqmClose">
            {PHP.L.Close} X
        </button>
        <span id="filesModalTitleText">{PHP.L.files_attachments}</span>
    </div>
    <iframe id="filesModalContent" src="">
    </iframe>
</div>
<script>
$(function() {
    var loadInIframeModal = function(hash){
        var trigger = $(hash.t);
        var modal = $(hash.w);
        var url = trigger.attr('href');
        var title= trigger.attr('title');
        var modalContent = $("iframe", modal);

        modalContent.html('').attr('src', url);
        //let's use the anchor "title" attribute as modal window title
        $('#attModalTitleText').text(title);
        modal.jqmShow();
        $('#filesModal').css('margin-left', '-'+($('#filesModal').width()/2)+'px');
        $('#filesModal').css('margin-top', '-'+($('#filesModal').height()/2)+'px');
        $('#filesModal').css('display','block');

        $('#filesModalClose').click(function() {
            window.location.reload();
        })
    }
    // initialise jqModal
    $('#filesModal').jqm({
        modal: false,
        trigger: 'a.filesLink',
        target: '#filesModalContent',
        onShow:  loadInIframeModal
    });
});
</script>
<!-- ENDIF -->

<a href="{FILES_URL}" class="filesLink"  title="{PHP.L.files_attachments}">{PHP.L.files_attach}</a>
<!-- END: MAIN -->
