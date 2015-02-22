/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document, console */

var dndOffset = 0;
$(function () {
    'use strict';

    var x = filesConfig['x'];

    // Initialize the jQuery File Upload widget:
    $('.fileupload').each(function () {
        var fileInput = $(this);

        var uplId = $(this).attr('id');
        uplId = uplId.replace('fileupload_', '');

        $(this).fileupload({
            dropZone: $(this),
            formData: {
                param: filesConfig[uplId].param,
                x: x
            },
            autoUpload: filesConfig.autoUpload,
            previewMaxWidth: filesConfig.previewMaxWidth,
            previewMaxHeight: filesConfig.previewMaxHeight,
            maxChunkSize: filesConfig[uplId]['chunk'],
            sequentialUploads: filesConfig.sequential
        });

        // Enable iframe cross-domain access via redirect option:
        $(this).fileupload(
            'option',
            'redirect',
            window.location.href.replace(
                /\/[^\/]*$/,
                'modules/files/lib/upload/cors/result.html?%s'
            )
        );
        $(this).bind('fileuploaddone', function (e, data) {
            // После загрузки новых файлов их тоже надо сортировать
            // todo обойтись без дублирования кода
            $.each(data.result.files, function (index, file) {
                fileInput.find(".filesTable").tableDnD({
                    onDragStart: function(table, row){
                        var offset = $(row).offset();
                        dndOffset = offset.top;
                    },
                    onDrop: function(table, row) {

                        var offset = $(row).offset();
                        if(Math.abs(dndOffset - offset.top) < 20 ) return;

                        dndOffset = 0;

                        var orders = [];
                        var i = 0;
                        var rows = table.tBodies[0].rows;
                        $(rows).each(function() {
                            var id = $(this).attr('data-id');
                            orders[i] = id;
                            i++;
                        });

                        var x = filesConfig['x'];
                        var updateUrl = 'index.php?e=files&m=files&a=reorder';

                        var procDiv = $('<div>', { 'id': "task-processing" });
                        $(table).before( procDiv );

                        $.post(updateUrl, {
                            orders: orders,
                            source: filesConfig[uplId].source,
                            item: filesConfig[uplId].item,
                            field: filesConfig[uplId].field,
                            x: x
                        }, function(data) {

                        }, 'json').fail(function() {
                            alert('Request Error');
                        }).always(function() {
                            $(procDiv).remove();
                        });
                    }
                });
            });
        });

        // Load existing files:
        fileInput.addClass('fileupload-processing');
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: fileInput.fileupload('option', 'url'),
            dataType: 'json',
            context: $(fileInput)[0]
        }).always(function () {
                $(this).removeClass('fileupload-processing');
            }).done(function (result) {
                $(this).fileupload('option', 'done')
                    .call(this, $.Event('done'), {result: result});

                // Drag&Drop for reordering
                setTimeout(function() {
                    fileInput.find(".filesTable").tableDnD({
                        onDragStart: function(table, row){
                            var offset = $(row).offset();
                            dndOffset = offset.top;
                        },
                        onDrop: function(table, row) {

                            var offset = $(row).offset();
                            if(Math.abs(dndOffset - offset.top) < 20 ) return;

                            dndOffset = 0;

                            var orders = [];
                            var i = 0;
                            var rows = table.tBodies[0].rows;
                            $(rows).each(function() {
                                var id = $(this).attr('data-id');
                                orders[i] = id;
                                i++;
                            });

                            var x = filesConfig['x'];
                            var updateUrl = 'index.php?e=files&m=files&a=reorder';

                            var procDiv = $('<div>', { 'id': "task-processing" });
                            $(table).before( procDiv );

                            $.post(updateUrl, {
                                    orders: orders,
                                    source: filesConfig[uplId].source,
                                    item: filesConfig[uplId].item,
                                    field: filesConfig[uplId].field,
                                    x: x
                            }, function(data) {

                            }, 'json').fail(function() {
                                alert('Request Error');
                            }).always(function() {
                                $(procDiv).remove();
                            });
                        }
                    });
                }, 300);

            });
    });


    if (window.FormData) {
        // Replacement of existing images
        // Supported on moder browsers only
        $('.fileupload').on('change', 'input.files-replace-file', function() {
            var id   = $(this).attr('data-id');
            var filename = $(this).val();
            var pass = true;
            if (filesConfig.exts.length > 0) {
                // Examine file extension
                var m = /\.(\w+)$/.exec(filename);
                if (m) {
                    var ext = m[1];
                    pass = filesConfig.exts.indexOf(ext.toLowerCase()) != -1;
                } else {
                    pass = false;
                }
            }
            if (pass) {
                $('button.files-replace-button[data-id="'+id+'"]').show();
            } else {
                $('button.files-replace-button[data-id="'+id+'"]').hide();
            }
        });

        $('.fileupload').on('click', 'button.files-replace-button', function() {
            var id   = $(this).attr('data-id');
            var input = document.getElementById("files-file"+id);
            var formdata = new FormData();
            if (input.files.length != 1) {
                return false;
            }
            var file = input.files[0];
            // TODO check file.type against filesConfig.accept
            formdata.append('file', file);
            formdata.append('id', id);
            formdata.append('x', filesConfig['x']);

            var updateUrl = 'index.php?e=files&m=files&a=replace';

            var procDiv = $('<div>', { 'id': "task-processing" });
            $(this).before( procDiv );

            $.ajax({
                url: updateUrl,
                type: "POST",
                data: formdata,
                processData: false,
                contentType: false,
                success: function (data) {
                    data.error = data.error || '';
                    if(data.error != ''){
                        $('#files-file'+id).after('<div id="files-error-'+ id +'"><span class="label label-danger">Error</span> ' +
                            data.error + '</div>');
                        $('#files-error-'+ id).fadeOut(3000);
                    }else{
                        // Reload the frame
                        // todo обойтись без перезагрузки страницы
                        window.location.reload();
                    }
                }

            }, 'json') .fail(function() {
                $('#files-file'+id).after('<div id="files-error-'+ id +'"><span class="label label-danger">Error</span></div>');
                $('#files-error-'+ id).fadeOut('slow');
            }).always(function() {
                $(procDiv).remove();
            });
            return false;
        });
    }

    /**
     * Ajax редактирование полей загруженных элементов
     */
    $('.fileupload').on('change', '.file-edit', function() {
        var that  = this,
            me    = $(this),
            row   = $(this).closest('.template-download'),
            id    = row.attr('data-id'),
            key   = $(this).attr('name'),
            value = $(this).val(),
            x     = filesConfig['x'],
            updateUrl = 'index.php?e=files&m=files&a=updateValue';

        key = key || false;
        if(!key) return false;

        var procDiv = $('<div>', { 'id': "task-processing" });
        $(this).before( procDiv );
        $(this).attr('disabled', true);

        $.post(updateUrl, {key: key, value: value, id: id, x: x}, function( data ) {
            data.error = data.error || '';
            if(data.error != ''){
                $('#files-file'+id).after('<div id="files-error-'+ id +'"><span class="label label-danger">Error</span> ' +
                data.error + '</div>');
                $('#files-error-'+ id).fadeOut(3000);
            }
        }, 'json').fail(function() {
            $('#files-file'+id).after('<div id="files-error-'+ id +'"><span class="label label-danger">Error</span></div>');
            $('#files-error-'+ id).fadeOut('slow');
        }).always(function() {
            $(that).attr('disabled', false);
            $('.fileupload-loading').hide();
            $(procDiv).remove();
        });

    });


    /**
     * PFS. Вставка изображения в текст
     */
    $('.fileupload').on('click', '.pasteImage', function(e) {
        e.preventDefault();

        var parentTr =  $(this).parents('.template-download');
        var id = parentTr.attr('data-id');
        parentTr = $('tr#file_'+id);

        var url = parentTr.attr('data-url'),
            title = parentTr.find('input[name="file_title"]').val(),
            name = parentTr.attr('data-name');
        title = title || '';

        addpix(url, title, name);
        return false;
    });

    /**
     * PFS. Вставка в текст иконки с ссылкой на изображение
     */
    $('.fileupload').on('click', '.pasteThumb', function(e) {
        e.preventDefault();

        var parentTr =  $(this).parents('.template-download');
        var id = parentTr.attr('data-id');
        parentTr = $('tr#file_'+id);

        var url = parentTr.attr('data-url'),
            title = parentTr.find('input[name="file_title"]').val(),
            name = parentTr.attr('data-name'),
            thumb = parentTr.attr('data-thumbnail');
        title = title || '';
        addthumb(url, title, name, thumb);
        return false;
    });

    /**
     * PFS. Вставка в текст ссылки на файл
     */
    $('.fileupload').on('click', '.pasteFile', function(e) {
        e.preventDefault();

        var parentTr =  $(this).parents('.template-download');
        var id = parentTr.attr('data-id');
        parentTr = $('tr#file_'+id);

        var url = parentTr.attr('data-url'),
            title = parentTr.find('input[name="file_title"]').val(),
            name = parentTr.attr('data-name'),
            thumb = parentTr.attr('data-thumbnail');
        title = title || '';
        addfile(url, title, name, thumb);

        return false;
    });


    $(document).bind('dragover', function (e) {
        var dropZone = $('#dropzone'),
            timeout = window.dropZoneTimeout;
        if (!timeout) {
            dropZone.addClass('in');
        } else {
            clearTimeout(timeout);
        }
        var found = false,
            node = e.target;
        do {
            if (node === dropZone[0]) {
                found = true;
                break;
            }
            node = node.parentNode;
        } while (node != null);
        if (found) {
            dropZone.addClass('hover');
        } else {
            dropZone.removeClass('hover');
        }
        window.dropZoneTimeout = setTimeout(function () {
            window.dropZoneTimeout = null;
            dropZone.removeClass('in hover');
        }, 1500);
    });

    $(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });
});