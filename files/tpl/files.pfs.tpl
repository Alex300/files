<!-- BEGIN: MAIN -->
<!-- BEGIN: STANDALONE_HEADER -->
<!DOCTYPE html>
<html lang="{PHP.usr.lang}">
<head>
    <title>{PHP.out.subtitle} - {PHP.cfg.maintitle}</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <base href="{PHP.cfg.mainurl}/" />
    {PFS_HEAD}
    <script type="text/javascript">
        //<![CDATA[
        {PFS_HEADER_JAVASCRIPT}
        var formName  = '{PFS_C1}';
        var inputName = '{PFS_C2}';
        //]]>
    </script>

</head>
<body>
<div class="container pfs standalone">
<!-- END: STANDALONE_HEADER -->

{BREADCRUMBS}

<h1>{PAGE_TITLE}</h1>

{FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

<!-- IF {FOLDER_DESC} --><div>{FOLDER_DESC}</div><!-- ENDIF -->

<!-- BEGIN:FOLDERS -->
<div class="desc">
    <strong>{FOLDERS_COUNT}</strong>
    <!-- IF {FOLDERS_COUNT_RAW} > 0 -->
    / {FOLDERS_FILES_COUNT} <em>({PHP.L.files_onpage}: {FOLDERS_ONPAGE_COUNT} / {FOLDERS_ONPAGE_FILES_COUNT})</em>
    <!-- ENDIF -->
</div>

<div class="text-right">
    <button class="btn btn-primary" data-toggle="modal" data-target="#dlgNewFolder">
        <span class="glyphicon glyphicon-folder-open"></span> &nbsp;{PHP.L.files_newfolder}
    </button>
</div>

<!-- IF {FOLDERS_COUNT_RAW} > 0 -->
<table class="table">
    <thead>
    <tr>
        <th>&nbsp;</th>
        <th>{PHP.L.Folder} / {PHP.L.Gallery}</th>
        <th>{PHP.L.Public}</th>
        <th>{PHP.L.Files}</th>
        <th>{PHP.L.Size}</th>
        <th>{PHP.L.Updated}</th>
        <th>{PHP.L.Action}</th>
    </tr>
    </thead>
    <!-- BEGIN: ROW -->
    <tr>
        <td class="centerall"><a href="{FOLDER_ROW_PFS_URL}">{FOLDER_ROW_ICON}</a></td>
        <td>
            <p class="strong margin0"><a href="{FOLDER_ROW_PFS_URL}">{FOLDER_ROW_TITLE}</a></p>
            <p class="desc margin0 lnh padding0">{FOLDER_ROW_DESC}</p>
            <p class="desc italic margin0 lnh padding0 text-right">
                <em>{PHP.L.files_pastecode}:</em> <strong>[pfs_gallery?f={FOLDER_ROW_ID}]</strong>
            </p>
        </td>
        <td class="centerall">{FOLDER_ROW_PUBLIC}</td>
        <td class="centerall">{FOLDER_ROW_ITEMS_COUNT}</td>
        <td class="centerall">{FOLDER_ROW_ITEMS_SIZE}</td>
        <td class="centerall">{FOLDER_ROW_UPDATE_DATE}</td>
        <td class="centerall" style="white-space: nowrap">
            <a href="{FOLDER_ROW_EDIT_URL}" class="btn btn-default btn-sm" title="{PHP.L.Edit}" data-toggle="tooltip">
                <span class="glyphicon glyphicon-edit"></span>
            </a>
            <!-- IF {PFS_IS_STANDALONE} -->
            <a href="javascript:addgallery('{FOLDER_ROW_ID}')" class="btn btn-default btn-sm"
               title="{PHP.L.files_pastegallery}"  data-toggle="tooltip">
                <span class="glyphicon glyphicon-picture"></span>
            </a>
            <!-- ENDIF -->
            <a href="{FOLDER_ROW_DELETE_URL}" class="btn btn-danger btn-sm confirmLink" title="{PHP.L.Delete}"  data-toggle="tooltip">
                <span class="glyphicon glyphicon-trash"></span></a>
        </td>
    </tr>
    <!-- END: ROW -->
</table>
<!-- ENDIF -->

<!-- IF {FOLDERS_PAGINATION} -->
<div class="pagination text-right">
    {FOLDERS_PAGEPREV}{FOLDERS_PAGINATION}{FOLDERS_PAGENEXT}
</div>
<!-- ENDIF -->
<!-- END: FOLDERS -->

<!-- IF {IS_SITE_FILE_SPACE} == 0 -->
<div class="well well-sm margintop10">
    <p>{PHP.L.files_totalsize}: {PFS_TOTALSIZE} {PHP.L.Of} {PFS_MAXTOTAL} ({PFS_PERCENTAGE}%)</p>

    <div class="progress  progress-striped">
        <div class="progress-bar {PFS_PROGRESSBAR_CLASS}" role="progressbar" aria-valuenow="{PFS_PERCENTAGE}" aria-valuemin="0" aria-valuemax="100"
             style="width: {PFS_PERCENTAGE}%;">{PFS_PERCENTAGE}%</div>
    </div>
    <p>{PHP.L.files_maxsize}: {PFS_MAXFILESIZE}</p>
</div>
<!-- ENDIF -->

<!-- IF {FOLDER_EDIT_URL} -->
<div class="text-right">
    <a href="{FOLDER_EDIT_URL}" class="btn btn-primary"><span class="glyphicon glyphicon-edit"></span> {PHP.L.Edit}</a>
</div>
<!-- ENDIF -->

<div class="margintop20 desc">
    <strong>{PFS_FILES_COUNT}</strong>
    <!-- IF {PFS_IS_ROOT} -->{PHP.L.files_intheroot}<!-- ELSE -->{PHP.L.files_inthisfolder}<!-- ENDIF -->
</div>
<div class="row"><div class="col-xs-12">{FILES_WIDGET}</div></div>

<div class="well well-sm margintop20">
    <h4 style="">{PHP.L.files_extallowed}:</h4>
    <!-- BEGIN: ALLOWED_ROW -->
    <div class="small margin5 pull-left text-center" style="margin: 5px">
        <img src="{ALLOWED_ROW_ICON_URL}" /><br />{ALLOWED_ROW_EXT}<br />{ALLOWED_ROW_DESC}
    </div>
    <!-- END: ALLOWED_ROW -->
    <div class="clearfix"></div>
</div>

<!-- BEGIN: FOLDER_NEWFORM -->
<div class="modal fade" id="dlgNewFolder" tabindex="-1" role="dialog" aria-labelledby="newFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="newFolderModalLabel">{PHP.L.files_newfolder}</h4>
            </div>

            <form name="login" id="dlgNewFolderForm" action="{FOLDER_ADDFORM_URL}" method="post"  role="form">
                {FOLDER_ADDFORM_HIDDEN}
                <div class="modal-body">

                    <div class="form-group">
                        <label>{PHP.L.Title}:</label>
                        {FOLDER_ADDFORM_TITLE}
                    </div>
                    <div class="form-group">
                        <label>{PHP.L.Description}:</label>
                        {FOLDER_ADDFORM_DESC}
                    </div>

                    <div class="checkbox">{FOLDER_ADDFORM_ALBUM}</div>
                    <div class="checkbox">{FOLDER_ADDFORM_PUBLIC}</div>

                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span> {PHP.L.Submit}</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <span class="glyphicon glyphicon-remove"></span> {PHP.L.Cancel}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END: FOLDER_NEWFORM -->

<!-- BEGIN: STANDALONE_FOOTER -->
<div class="col-xs-12">
    <div class="well well-sm">
        {PHP.R.files_icon_pastethumb} {PHP.L.files_pastethumb} &nbsp;
        {PHP.R.files_icon_pasteimage} {PHP.L.files_pasteimage} &nbsp;
        {PHP.R.files_icon_pastefile} {PHP.L.files_pastefile}
    </div>
</div>
</div>
{FOOTER_RC}
</body>
</html>
<!-- END: STANDALONE_FOOTER -->
<!-- END: MAIN -->