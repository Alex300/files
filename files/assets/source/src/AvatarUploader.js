/**
 * Files avatar uploader
 * @package Files
 * @author Alexey Kalnov <kalnovalexey@yandex.ru> https://github.com/Alex300
 * @copyright (c) 2014-2025 Lily Software https://lily-software.com
 */
export class AvatarUploader {
    #config = {};

    /**
     * @type {HTMLElement|null}
     */
    #element = null;

    /**
     * @type {HTMLElement|null}
     */
    #avatarContainer = null;

    /**
     * @type {HTMLElement|null}
     */
    #avatarImageElement = null;

    /**
     * @type {HTMLElement|null}
     */
    #progressElement = null;

    /**
     * @type {HTMLElement|null}
     */
    #uploaderElement = null;

    #avatarTemplate = '<img src="__src__" alt="__alt__" class="avatar img-responsive" />';

    constructor(config) {
        this.#config = config;
    }

    /**
     * @param {HTMLElement} element
     */
    init(element) {
        if (element.dataset.filesInited === 'true') {
            return;
        }

        this.#element = element;
        this.#avatarContainer = this.#element.querySelector('.files-avatar-container');
        if (this.#avatarContainer !== null) {
            this.#avatarImageElement = this.#avatarContainer.querySelector('img.avatar');
        }

        this.#progressElement = this.#element.querySelector('.progress');
        this.#uploaderElement = this.#element.querySelector('.file-upload-avatar-input');

        const options = Object.assign({}, this.#config);

        options.url = this.#uploaderElement.dataset.url;
        options.acceptFileTypes = /(\.|\/)(avif|bmp|gif|jpe?g|heic|heif|png|svg|tga|webp)$/i;
        options.disableValidation = false;
        options.uploadTemplateId = null;
        options.downloadTemplateId = null;
        options.autoUpload = true;

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
        }

        if (element.dataset.filesAvatarTemplate !== undefined) {
            if (options.formData === undefined) {
                options.formData = {};
            }
            this.#avatarTemplate = atob(element.dataset.filesAvatarTemplate);
        }

        $(this.#uploaderElement).fileupload(options)
            .on('fileuploadprocessstart',  (e) => {
                this.#onUploadStart();
            })
            .on('fileuploadprocessalways', (e, data) => {
                // Validation result
                let currentFile = data.files[data.index];
                if (data.files.error && currentFile.error) {
                    // there was an error
                    this.#showError(currentFile.error);
                }
            })
            .on('fileuploadprogressall', (e, data) => {
                const progress = parseInt(data.loaded / data.total * 100, 10);

                $('.files-avatar-upload-error').remove();
                $('#files-avatar-upload #progress .progress-bar').css('width', progress + '%');

            })
            .on('fileuploaddone', (e, data) => {
                this.#onUploadEnd();

                if (data.result === undefined || data.result.files === undefined) {
                    this.#showError('File upload response error');
                    return;
                }

                data.result.files.forEach((file) => {
                    const error =  file.error || false;
                    if (error) {
                        this.#showError(file.error);
                        return;
                    } else {
                        $('.files-avatar-upload-error').remove();
                    }

                    let avatarElement = this.#element.querySelector('.files-avatar');
                    if (avatarElement !== null) {
                        let avatarRendered = this.#avatarTemplate.replace('__src__', file.thumbnailUrl);
                        avatarRendered = avatarRendered.replace('__alt__', file.name);
                        avatarElement.innerHTML = avatarRendered;
                    }
                });

            })
            .on('fileuploadfail', (e, data) => {
                this.#showError('File upload error');
                this.#onUploadEnd();
            })
            .prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');


        // Disable built-in UI handlers
        $(element).off('.fileupload-ui');

        element.dataset.filesInited = 'true';
    }

    #showError(message) {
        this.#progressElement.classList.add('hidden');

        let div = document.createElement('div');
        div.className = 'files-avatar-upload-error';
        div.innerHTML = '<span class="label label-danger">Error</span> ' + message;

        this.#progressElement.after(div);
    }

    #onUploadStart() {
        if (this.#avatarContainer === null) {
            return;
        }

        if (this.#avatarImageElement !== null) {
            this.#avatarImageElement.style.opacity = .4;
        }

        const preloader = document.createElement('span');
        preloader.classList.add('file-upload-preloader');

        this.#avatarContainer.append(preloader)

        this.#uploaderElement.disabled = true;
        this.#progressElement.classList.remove('hidden');
    }

    #onUploadEnd() {
        if (this.#avatarContainer === null) {
            return;
        }

        const preloader = this.#avatarContainer.querySelector('.file-upload-preloader');
        if (preloader !== null) {
            preloader.remove();
        }

        if (this.#avatarImageElement !== null) {
            this.#avatarImageElement.style.opacity = 1;
        }

        this.#uploaderElement.disabled = false;

        this.#progressElement.classList.add('hidden');
    }
}