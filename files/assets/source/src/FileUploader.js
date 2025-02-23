/**
 * Files file uploader
 * @package Files
 * @author Alexey Kalnov <kalnovalexey@yandex.ru> https://github.com/Alex300
 * @copyright (c) 2014-2025 Lily Software https://lily-software.com
 *
 * @todo refactor
 */
export class FileUploader {
    #REORDER_URL = 'index.php?e=files&m=files&a=reorder&_ajax=1';
    #REPLACE_URL = 'index.php?e=files&m=files&a=replace&_ajax=1';
    #UPDATE_FIELD_VALUE_URL = 'index.php?e=files&m=files&a=updateValue&_ajax=1';

    static globalsInited = false;

    #config = {};

    /**
     * @type {HTMLElement|null}
     */
    #element = null;

    #source = null;
    #sourceId = null;
    #field = null;

    #x = null;

    #dndOffset = 0;

    constructor(config) {
        this.#config = config;
        if (this.#config.x !== undefined) {
            this.#x = this.#config.x;
        }
    }

    /**
     * @param {HTMLElement} element
     */
    init(element) {
        if (element.dataset.filesInited === 'true') {
            return;
        }

        this.#element = element;

        if (element.dataset.filesSource !== undefined) {
            this.#source = element.dataset.filesSource;
        }
        if (element.dataset.filesSourceId !== undefined) {
            this.#sourceId = element.dataset.filesSourceId;
        }
        if (element.dataset.filesField !== undefined) {
            this.#field = element.dataset.filesField;
        }

        const options = Object.assign({}, this.#config);

        options.url = element.dataset.url;
        options.dropZone = $(element);

        if (element.dataset.filesFormParam !== undefined) {
            if (options.formData === undefined) {
                options.formData = {};
            }
            options.formData.param = element.dataset.filesFormParam;
        }

        if (element.dataset.filesFormX !== undefined) {
            if (options.formData === undefined) {
                options.formData = {};
            }
            options.formData.x = element.dataset.filesFormX;
            this.#x = element.dataset.filesFormX;
        }

        $(element).fileupload(options);

        // Enable iframe cross-domain access via redirect option:
        $(element).fileupload(
            'option',
            'redirect',
            window.location.href.replace(
                /\/[^\/]*$/,
                'modules/files/lib/upload/cors/result.html?%s' // @todo
            )
        );

        this.#loadExistingFiles();

        $(element).bind('fileuploaddone', (e, data) => {
            // После загрузки новых файлов их тоже надо сортировать
            // todo обойтись без дублирования кода
            $.each(data.result.files, (index, file) => {
                $(element).find(".filesTable").tableDnD({
                    onDragStart: (table, row) => {
                        var offset = $(row).offset();
                        this.#dndOffset = offset.top;
                    },
                    onDrop: (table, row) => {
                        var offset = $(row).offset();
                        if (Math.abs(this.#dndOffset - offset.top) < 20 ) {
                            return;
                        }

                        this.#dndOffset = 0;

                        var orders = [];
                        var i = 0;
                        var rows = table.tBodies[0].rows;
                        $(rows).each(function() {
                            var id = $(this).attr('data-id');
                            orders[i] = id;
                            i++;
                        });

                        const preloader = document.createElement('span');
                        preloader.classList.add('file-upload-preloader');
                        row.append(preloader);

                        $.post(this.#REORDER_URL, {
                            orders: orders,
                            source: this.#source,
                            item: this.#sourceId,
                            field: this.#field,
                            x: this.#x
                        }, function(data) {

                        }, 'json').fail(function() {
                            alert('Request Error');
                        }).always(function() {
                            preloader.remove();
                        });
                    }
                });
            });
        });

        element.addEventListener('change', (event) => {
            if (event.target.tagName === 'INPUT' && event.target.classList.contains('files-replace-file')) {
                this.#onChangeFileReplaceInput(event.target);
            }

            if (event.target.classList.contains('file-edit')) {
                this.#onChangeFieldValue(event.target);
            }
        });

        element.addEventListener('click', (event) => {
            const row = event.target.closest('.template-download');

            let target = event.target.closest('button.files-replace-button');
            if (target !== null && this.#element.contains(target)) {
                event.preventDefault();
                this.#onClickFileReplaceButton(target);
                return;
            }

            target = event.target.closest('.delete');
            if (row !== null && target !== null && this.#element.contains(target)) {
                this.#onClickDelete(target);
                return;
            }

            target = event.target.closest('.pasteImage');
            if (target !== null && this.#element.contains(target)) {
                event.preventDefault();
                this.#onClickPfsPasteImage(target);
                return;
            }

            target = event.target.closest('.pasteThumb');
            if (target !== null && this.#element.contains(target)) {
                event.preventDefault();
                this.#onClickPfsPasteThumbnail(target);
                return;
            }

            target = event.target.closest('.pasteFile');
            if (target !== null && this.#element.contains(target)) {
                event.preventDefault();
                this.#onClickPfsPasteFile(target);
                return;
            }
        });

        element.dataset.filesInited = 'true';

        if (!FileUploader.globalsInited) {
            this.#initGlobals();
        }
    }

    #initGlobals() {
        if (FileUploader.globalsInited) {
            return;
        }

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

        FileUploader.globalsInited = true;
    }

    #loadExistingFiles()
    {
        this.#element.classList.add('fileupload-processing');
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: $(this.#element).fileupload('option', 'url'),
            dataType: 'json',
            context: this.#element

        }).always(function () {
            $(this).removeClass('fileupload-processing');
        }).done((result) => {
            $(this.#element).fileupload('option', 'done').call($(this.#element), $.Event('done'), {result: result});

            // Drag&Drop for reordering
            setTimeout(() => {
                $(this.#element).find(".filesTable").tableDnD({
                    onDragStart: (table, row) => {
                        const offset = $(row).offset();
                        this.#dndOffset = offset.top;
                    },
                    onDrop: (table, row) => {
                        const offset = $(row).offset();
                        if (Math.abs(this.#dndOffset - offset.top) < 20 ) {
                            return;
                        }

                        this.#dndOffset = 0;

                        const orders = [];
                        let i = 0;
                        const rows = table.tBodies[0].rows;
                        $(rows).each(function() {
                            orders[i] = $(this).attr('data-id');
                            i++;
                        });

                        const preloader = document.createElement('span');
                        preloader.classList.add('file-upload-preloader');
                        row.append(preloader);

                        $.post(this.#REORDER_URL, {
                            orders: orders,
                            source: this.#source,
                            item: this.#sourceId,
                            field: this.#field,
                            x: this.#x
                        }, function(data) {

                        }, 'json').fail(function() {
                            alert('Request Error');
                        }).always(function() {
                            preloader.remove();
                        });
                    }
                });
            }, 300);

        });
    }

    /**
     * @param {HTMLElement} element
     */
    #onClickDelete(element) {
        $('div.tooltip').remove();
        const preloader = document.createElement('span');
        preloader.classList.add('file-upload-preloader');
        element.parentNode.append(preloader);
    }

    /**
     * @param {HTMLElement} fileInput
     */
    #onChangeFileReplaceInput(fileInput)
    {
        let id = fileInput.dataset.id;
        let filename = fileInput.value;
        let pass = true;
        if (this.#config.allowedExtensions.length > 0) {
            // Examine file extension
            let m = /\.(\w+)$/.exec(filename);
            if (m) {
                const ext = m[1];
                pass = this.#config.allowedExtensions.indexOf(ext.toLowerCase()) !== -1;
            } else {
                pass = false;
            }
        }

        const replaceButton = this.#element.querySelector('button.files-replace-button[data-id="' + id + '"]');
        if (replaceButton !== null) {
            if (pass) {
                $(replaceButton).fadeIn('slow');
            } else {
                $(replaceButton).hide();
            }
        }
    }

    /**
     * @param {HTMLElement} replaceButton
     */
    #onClickFileReplaceButton(replaceButton)
    {
        let id = replaceButton.dataset.id;
        let input = document.getElementById('files-file' + id);
        if (input.files.length !== 1) {
            return false;
        }
        const formData = new FormData();
        const file = input.files[0];
        // TODO check file.type against filesConfig.accept
        formData.append('file', file);
        formData.append('id', id);
        formData.append('x', this.#x);

        const preloader = document.createElement('span');
        preloader.classList.add('file-upload-preloader');
        replaceButton.parentNode.append(preloader);

        $.ajax({
            url: this.#REPLACE_URL,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                data.error = data.error || '';
                if (data.error !== '') {
                    $('#files-file'+id).after('<div id="files-error-'+ id +'"><span class="label label-danger">Error</span> ' +
                        data.error + '</div>');
                    $('#files-error-'+ id).fadeOut(3000);
                } else {
                    // Reload the frame
                    // todo обойтись без перезагрузки страницы
                    window.location.reload();
                }
            }

        }, 'json') .fail(() => {
            this.#showError(id, 'The file could not be uploaded');
        }).always(function() {
            preloader.remove();
        });
    }

    /**
     * @param {HTMLElement} element
     */
    #onChangeFieldValue(element)
    {
        const row = element.closest('.template-download');
        const id = row.dataset.id;
        let key = element.name;
        const value = element.value;

        key = key || false;
        if(!key) {
            return false;
        }

        const preloader = document.createElement('span');
        preloader.classList.add('file-upload-preloader');
        row.append(preloader);
        element.disabled = true;

        $.post(this.#UPDATE_FIELD_VALUE_URL, {key: key, value: value, id: id, x: this.#x}, (data) => {
            data.error = data.error || '';
            if (data.error !== '') {
                this.#showError(id, data.error);
            }
        }, 'json').fail(() => {
            this.#showError(id, 'Update value error');
        }).always(() => {
            element.disabled = false;
            preloader.remove();
        });
    }

    /**
     * PFS. Insert an image into text
     * @param {HTMLElement} element
     */
    #onClickPfsPasteImage(element) {
        const row = element.closest('.template-download');
        const data = this.#getFileDataToInsert(row);

        window.addpix(data.url, data.title, data.name);
    }

    /**
     * PFS. Insert a thumbnail into text with a link to the image
     * @param {HTMLElement} element
     */
    #onClickPfsPasteThumbnail(element) {
        const row = element.closest('.template-download');
        const data = this.#getFileDataToInsert(row);

        window.addthumb(data.url, data.title, data.name, data.thumbnailUrl);
    }

    /**
     * PFS. Insert a link to a file into the text
     * @param {HTMLElement} element
     */
    #onClickPfsPasteFile(element) {
        const row = element.closest('.template-download');
        const data = this.#getFileDataToInsert(row);

        window.addfile(data.url, data.title, data.name, data.thumbnailUrl);
    }

    /**
     * @param {HTMLElement} row
     * @returns {{ url: string, title: string, name: string, thumbnailUrl:string }}
     */
    #getFileDataToInsert(row) {
        const result = {
            url: row.dataset.url,
            name: row.dataset.name,
            thumbnailUrl: row.dataset.thumbnail,
            title: '',
        }

        const titleInput = row.querySelector('input.file-edit[name="title"]');
        if (titleInput !== null) {
            result.title = titleInput.value;
        }

        return result;
    }

    #showError (fileId, errorMessage) {
        $('#files-file'+ fileId).after(
            '<div id="files-error-'+ fileId +'"><span class="label label-danger">Error</span> ' + errorMessage + '</div>'
        );
        setTimeout(() => { $('#files-error-'+ fileId).fadeOut(3000) }, 1000)
    }
}