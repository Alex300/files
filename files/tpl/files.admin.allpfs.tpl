<!-- BEGIN: MAIN -->
<!-- IF {PAGE_TITLE} -->
<h2 class="tags"><img src="{PHP.cfg.modules_dir}/files/files.png" style="vertical-align: middle;" /> {PAGE_TITLE}</h2>
<!-- ENDIF -->

<!-- BEGIN: SFS -->
<table class="cells marginbottom10">
    <tr>
        <td class="strong width60">{PHP.L.SFS}</td>
        <td class="centerall width20">{SFS_COUNT}</td>
        <td class="centerall width20"><a title="{PHP.L.Open}" href="{PHP|cot_url('files', 'm=pfs&uid=0')}">{PHP.R.icon_folder}</a></td>
    </tr>
</table>
<!-- END: SFS -->

<table class="cells">
    <tr>
        <td class="coltop width60">{PHP.L.User}</td>
        <td class="coltop width20">{PHP.L.Files}</td>
        <td class="coltop width20">{PHP.L.Open}</td>
    </tr>
    <!-- BEGIN: ALLPFS_ROW -->
    <tr>
        <td>
            <a href="{ALLPFS_ROW_USER_DETAILSLINK}">{ALLPFS_ROW_USER_DISPLAY_NAME}</a>
            <!-- IF {ALLPFS_ROW_USER_DISPLAY_NAME} != {ALLPFS_ROW_USER_NICKNAME} -->
            <em>({ALLPFS_ROW_USER_NICKNAME})</em>
            <!-- ENDIF -->
        </td>
        <td class="centerall">{ALLPFS_ROW_COUNT}</td>
        <td class="centerall"><a title="{PHP.L.Open}" href="{ALLPFS_ROW_URL}">{PHP.R.icon_folder}</a></td>
    </tr>
    <!-- END: ALLPFS_ROW -->
</table>

<p class="paging">{ALLPFS_PAGINATION_PREV}{ALLPFS_PAGNAV}{ALLPFS_PAGINATION_NEXT}<span>
        {PHP.L.Total}: {ALLPFS_TOTALITEMS}, {PHP.L.Onpage}: {ALLPFS_ON_PAGE}</span></p>
<!-- END: MAIN -->