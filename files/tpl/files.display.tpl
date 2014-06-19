<!-- BEGIN: MAIN -->
<div class="row">
    <!-- BEGIN: FILES_ROW -->
    <div class="col-xs-6 col-sm-2 text-center" style="height: 110px; overflow: hidden" data-source="file-row-tooltip-{FILES_ROW_ID}"
         data-toggle="tooltip" title="" data-html="true">
        <a href="{FILES_ROW_URL}" title="{FILES_ROW_TITLE}"><img src="{FILES_ROW_ICON}" alt="{FILES_ROW_EXT}" /></a><br />
        <a href="{FILES_ROW_URL}" title="{FILES_ROW_TITLE}" class="strong lhn">{FILES_ROW_NAME}</a><br />
        <!-- IF {FILES_ROW_TITLE} -->{FILES_ROW_TITLE}<br /><!-- ENDIF -->
        <span class="desc">{FILES_ROW_SIZE} ({PHP.L.files_downloads}: {FILES_ROW_COUNT})</span>
    </div>
    <div id="file-row-tooltip-{FILES_ROW_ID}" class="hidden">
        {FILES_ROW_NAME}<br />
        {FILES_ROW_SIZE} ({PHP.L.files_downloads}: {FILES_ROW_COUNT})
    </div>
    <!-- END: FILES_ROW -->
</div>
<!-- END: MAIN -->
