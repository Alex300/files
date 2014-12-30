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

<!-- BEGIN: FORM -->
<form action="{FOLDER_FORM_URL}" enctype="multipart/form-data" method="post" name="groupform"
      class="form-horizontal" role="form">
    {FOLDER_FORM_HIDDEN}

    <div class="form-group {PHP|cot_files_formGroupClass('ff_title')}">
        <label class="col-sm-2 control-label">Название: *</label>
        <div class="col-sm-10">{FOLDER_FORM_TITLE}</div>
    </div>

    <div class="form-group {PHP|cot_files_formGroupClass('ff_desc')}">
        <label class="col-sm-2 control-label">{PHP.L.Description}:</label>
        <div class="col-sm-10">{FOLDER_FORM_DESC}</div>
    </div>

    <div class="form-group">
        <div class="col-sm-10 col-sm-offset-2">
            <div class="checkbox">{FOLDER_FORM_PUBLIC}</div>
        </div>
    </div>

    <!-- IF {FOLDER_FORM_ALBUM} -->
    <div class="form-group">
        <div class="col-sm-10 col-sm-offset-2">
            <div class="checkbox">{FOLDER_FORM_ALBUM}</div>
        </div>
    </div>
    <!-- ENDIF -->
    
    <div class="form-group">
        <div class="col-sm-10 col-sm-offset-2">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-ok"></span>
                {PHP.L.Submit}</button>
        </div>
    </div>

</form>
<!-- END: FORM -->

<!-- IF {IS_SITE_FILE_SPACE} == 0 -->
<div class="well well-sm">
    <p>{PHP.L.files_totalsize}: {PFS_TOTALSIZE} {PHP.L.Of} {PFS_MAXTOTAL} ({PFS_PERCENTAGE}%)</p>

    <div class="progress  progress-striped">
        <div class="progress-bar {PFS_PROGRESSBAR_CLASS}" role="progressbar" aria-valuenow="{PFS_PERCENTAGE}" aria-valuemin="0" aria-valuemax="100"
             style="width: {PFS_PERCENTAGE}%;">{PFS_PERCENTAGE}%</div>
    </div>
    <p>{PHP.L.files_maxsize}: {PFS_MAXFILESIZE}</p>
</div>
<!-- ENDIF -->

<!-- IF {FILES_WIDGET} != '' -->
<div class="margintop20 desc">
    <strong>{PFS_FILES_COUNT}</strong> {PHP.L.files_inthisfolder}
</div>
<div class="row"><div class="col-xs-12">{FILES_WIDGET}</div></div>
<!-- ENDIF -->

<div class="well well-sm margintop20">
    <h4 style="">{PHP.L.files_extallowed}:</h4>
    <!-- BEGIN: ALLOWED_ROW -->
    <div class="small margin5 pull-left text-center" style="margin: 5px">
        <img src="{ALLOWED_ROW_ICON_URL}" /><br />{ALLOWED_ROW_EXT}<br />{ALLOWED_ROW_DESC}
    </div>
    <!-- END: ALLOWED_ROW -->
    <div class="clearfix"></div>
</div>

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