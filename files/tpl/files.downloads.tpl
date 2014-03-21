<!-- BEGIN: MAIN -->
<style type="text/css">
.files-downloads { border:0; }
.files-downloads .files-icon { width:32px;padding:4px 8px 8px 4px; }
.files-downloads .files-fileinfo { text-align: left;padding:4px 8px 4px 8px;}
</style>
<table class="files-downloads">
	<!-- BEGIN: FILES_ROW -->
	<tr>
		<td class="files-icon">
			<a href="{FILES_ROW_URL}" title="{FILES_ROW_TITLE}">
				<img src="{FILES_ROW_ICON}" alt="{FILES_ROW_EXT}" />
			</a>
		</td>
		<td class="files-fileinfo">
			<a href="{FILES_ROW_URL}" title="{FILES_ROW_TITLE}" class="strong">{FILES_ROW_NAME}</a>
			<p class="small">{FILES_ROW_SIZE} ({PHP.L.files_downloads}: {FILES_ROW_COUNT})</p>
		</td>
	</tr>
	<!-- END: FILES_ROW -->
</table>
<!-- END: MAIN -->
