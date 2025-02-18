import {AvatarUploader} from "./AvatarUploader";
import {FileUploader} from "./FileUploader";

/**
 * Base Files class
 * @package Files
 * @author Alexey Kalnov <kalnovalexey@yandex.ru> https://github.com/Alex300
 * @copyright (c) 2014-2025 Lily Software https://lily-software.com
 */
class Files
{
    #config = {};

    #uploaderSelector = '.file-upload';
    #avatarUploaderSelector = '.file-upload-avatar';

    init(config = null) {
        if (config !== null) {
            Object.assign(this.#config, config);
        }

        if (this.#config.loadImageFileTypes !== undefined && (typeof this.#config.loadImageFileTypes === 'string')) {
            this.#config.loadImageFileTypes = new RegExp(this.#config.loadImageFileTypes);
        }

        document.addEventListener("DOMContentLoaded", () => {
            this.initUploaders();
        });
    }

    initUploaders() {
        this.initFilesUploaders();
        this.initAvatarUploaders();
    }

    initFilesUploaders() {
        const elements = document.querySelectorAll(this.#uploaderSelector);
        elements.forEach((element) => {
            if (element.dataset.filesInited === 'true') {
                return;
            }
            let uploader = new FileUploader(this.#config);
            uploader.init(element);
        });
    }

    initAvatarUploaders() {
        const elements = document.querySelectorAll(this.#avatarUploaderSelector);
        elements.forEach((element) => {
            if (element.dataset.filesInited === 'true') {
                return;
            }
            let avatarUploader = new AvatarUploader(this.#config);
            avatarUploader.init(element);
        });
    }
}

/**
 * @todo window.cot is available since Cotonti version 0.9.26
 */
window.cotFiles = new Files
