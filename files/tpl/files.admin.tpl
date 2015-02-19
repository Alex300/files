<!-- BEGIN: MAIN -->

<div class="block button-toolbar">
    <a title="{PHP.L.Configuration}" href="{PHP|cot_url('admin', 'm=config&n=edit&o=module&p=files')}" class="button">{PHP.L.Configuration}</a>
    <a href="{PHP|cot_url('admin', 'm=extrafields&n=cot_files')}" class="button">{PHP.L.files_extrafields_files}</a>
    <a href="{PHP|cot_url('admin', 'm=extrafields&n=cot_files_folders')}" class="button">{PHP.L.files_extrafields_folders}</a>
    <a href="{PHP|cot_url('admin', 'm=files&a=allpfs')}" class="button">{PHP.L.files_allpfs}</a>
    <a href="{PHP|cot_url('files', 'm=pfs&uid=0')}" class="button">{PHP.L.SFS}</a>
    <a href="{PHP|cot_url('admin', 'm=files&a=cleanup')}" onclick="return confirm('{PHP.L.files_cleanup_conf}')"
        class="button">{PHP.L.files_cleanup}</a>
    <a href="{PHP|cot_url('admin', 'm=files&a=delAllThumbs')}" onclick="return confirm('{PHP.L.files_deleteallthumbs_conf}')"
       class="button">{PHP.L.files_deleteallthumbs}</a>
</div>

{FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

{CONTENT}
<!-- END: MAIN -->
