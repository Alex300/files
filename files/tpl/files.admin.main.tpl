<!-- BEGIN: MAIN -->
<!-- IF {PAGE_TITLE} -->
<h2 class="tags"><img src="{PHP.cfg.modules_dir}/files/files.png" style="vertical-align: middle;" /> {PAGE_TITLE}</h2>
<!-- ENDIF -->

<div class="clear" style="height: 1px;"></div>

<!-- BEGIN: IMAGICK_INFO -->
<div class="col4-2 first paddingright10">
    <div class="block">
        <h2>
            <!-- IF {IS_ACTIVE} -->
            <strong style="margin-right: 5px; color: green">V</strong>
            <!-- ENDIF -->
            {PHP.L.files_imagick}:
        </h2>
        <div class="wrapper">
            <ul class="follow">
                <!-- BEGIN: ROW -->
                <li>{IMAGICK_DATA_NAME}: <span class="strong">{IMAGICK_DATA_VALUE}</span></li>
                <!-- END: ROW -->
            </ul>
        </div>
    </div>
</div>
<!-- END: IMAGICK_INFO -->

<!-- BEGIN: GD_INFO -->
<div class="col4-2 paddingleft10">
    <div class="block">
        <h2>
            <!-- IF {IS_ACTIVE} -->
            <strong style="margin-right: 5px; color: green">V</strong>
            <!-- ENDIF -->
            {PHP.L.files_gd}:
        </h2>
        <div class="wrapper">
            <ul class="follow">
                <!-- BEGIN: ROW -->
                <li>{GD_DATA_NAME}: <span class="strong">{GD_DATA_VALUE}</span></li>
                <!-- END: ROW -->
            </ul>
        </div>
    </div>
</div>
<!-- END: GD_INFO -->

<div class="clear" style="height: 1px;"></div>
<div class="textcenter margintop10">
    <em>{PHP.L.Version}:</em> <strong>{PHP.cot_modules.files.version}</strong>.
</div>
<!-- END: MAIN -->