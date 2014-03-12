<!-- BEGIN: MAIN -->
<div class="breadcrumb">{BREADCRUMBS}</div>

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

<!-- IF {FOLDER_ADDFORM_URL} -->
<div class="text-right">
    <button class="btn btn-primary" data-toggle="modal" data-target="#dlgNewFolder">
        <span class="glyphicon glyphicon-folder-open"></span> &nbsp;{PHP.L.files_newfolder}
    </button>
</div>
<!-- ENDIF -->

    <!-- BEGIN: ROW -->
    <section class="row list-row">
        <div class="col-xs-12">
            <h2><a href="{FOLDER_ROW_URL}">{FOLDER_ROW_TITLE}</a></h2>
            <!-- IF {FOLDER_ROW_DESC} --><div class="marginbottom10">{FOLDER_ROW_DESC}</div><!-- ENDIF -->
            <div class="row">
                <!-- BEGIN: FILES_ROW -->
                <div class="col-sm-6 col-md-4 marginbottom10">
                    <div class="pull-left" style="width: 50px">
                        <!-- IF {FILES_ROW_ICON} -->
                        <a href="{FILES_ROW_URL}" title="{FILES_ROW_TITLE}"><img src="{FILES_ROW_ICON}" alt="{FILES_ROW_EXT}" /></a>
                        <!-- ENDIF -->
                    </div>
                    <div class="pull-left" data-source="file-row-tooltip-{FILES_ROW_ID}"
                         data-toggle="tooltip" title="" data-html="true">
                        <a href="{FILES_ROW_URL}">{FILES_ROW_NAME}</a><br />
                        <!-- IF {FILES_ROW_TITLE} -->{FILES_ROW_TITLE}<br /><!-- ENDIF -->
                        <span class="desc">{FILES_ROW_UPDATED_RAW|cot_date('datetime_medium', $this)}<br /></span>
                    </div>
                </div>
                <div id="file-row-tooltip-{FILES_ROW_ID}" class="hidden">
                    {FILES_ROW_NAME}<br />{FILES_ROW_UPDATED_RAW|cot_date('datetime_medium', $this)}<br />
                    {FILES_ROW_SIZE} ({PHP.L.files_downloads}: {FILES_ROW_COUNT})
                </div>
                <!-- END: FILES_ROW -->
            </div>
            <div class="row">
                <div class="col-xs-6 desc">
                    <em>Создан:</em> {FOLDER_ROW_CREATED_RAW|cot_date('datetime_fulltext', $this)}<br />
                    <em>Последнее обновление:</em> {FOLDER_ROW_UPDATED_RAW|cot_date('datetime_fulltext', $this)}
                </div>
                <div class="col-xs-6 text-right">
                    <a class="italic" href="{FOLDER_ROW_URL}">{PHP.L.files_morefiles}...</a>
                </div>
            </div>
        </div>
    </section>
    <!-- END: ROW -->

    <!-- BEGIN: EMPTY -->
    <!-- IF {USER_ID} == {PHP.usr.id} -->
    <div class="margintop20 strong grey text-center">
        Вы пока не добавили ни одного раздела
    </div>
    <!-- ELSE -->
    <div class="margintop20 strong grey text-center">
        <!-- IF {USER_FIRSTNAME} OR {USER_LASTNAME} OR {USER_MIDDLENAME} -->
        {USER_FIRSTNAME} {USER_MIDDLENAME} {USER_LASTNAME}
        <!-- ELSE -->
        {USER_NICKNAME}
        <!-- ENDIF -->
        пока не добавил<!-- IF {USER_GENDER_RAW} == 'F' -->а<!-- ENDIF --> ни одного раздела
    </div>
    <!-- ENDIF -->
    <!-- END: EMPTY -->

<!-- END:FOLDERS -->



<!-- END: MAIN -->