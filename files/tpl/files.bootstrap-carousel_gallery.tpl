<!-- BEGIN: MAIN -->
<div id="carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD}" class="carousel slide" data-ride="carousel">

    <!-- Indicators -->
    <ol class="carousel-indicators"></ol>

    <!-- Wrapper for slides -->
    <div class="carousel-inner">
        <!-- BEGIN: FILES_ROW -->
        <div class="item<!-- IF {FILES_ROW_NUM} == 1 --> active<!-- ENDIF -->">
            <img src="{FILES_ROW_ID|cot_files_thumb($this,1153,523,'crop')}" alt="{FILES_ROW_FILENAME}" class="img-responsive" />
            <!-- IF {FILES_ROW_TITLE} --><div class="carousel-caption">{FILES_ROW_TITLE}</div> <!-- ENDIF -->
        </div>
        <!-- END: FILES_ROW -->
    </div>


    <!-- Controls -->
    <a class="left carousel-control" href="#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD}" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left"></span>
    </a>
    <a class="right carousel-control" href="#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD}" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right"></span>
    </a>

    <!-- IF {PHP.usr.isadmin} -->
    <div style="position: absolute; top: 10px; right: 10px">
        <a data-toggle="tooltip" title="" href="{PHP|cot_url('page', 'm=edit&id=2')}" class="btn btn-default btn-sm" data-original-title=" Редактировать карусель">
            <span class="glyphicon glyphicon-cog"></span> </a>
    </div>
    <!-- ENDIF -->
</div>
<script type="text/javascript">
    var caruselNum = 0;
    $('#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD} .carousel-inner').children('.item').each(function (index) {
        caruselNum++;
        var li = $('<li>', {
            'data-target': "#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD}",
            'data-slide-to': index
        });
        if (index == 0) {
            $(this).addClass('active');
            li.addClass('active');
        }
        $('#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD} .carousel-indicators').append(li);
    })

    if(caruselNum < 2){
        $('#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD} .carousel-control').css('display', 'none');
        $('#carousel-{FILES_SOURCE}-{FILES_ITEM}-{FILES_FIELD} .carousel-indicators').css('display', 'none');
    }
</script>
<!-- END: MAIN -->