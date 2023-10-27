/**
 * Module config
 *
 * @package Files
 * @author Kalnov Alexey <kalnovalexey@yandex.ru>
 * @copyright (c) Lily Software https://lily-software.com
 */

'use strict';

// @todo Можно еще сделать для "Конвертировать изображения в JPEG при закачке"
function filesConfigFormInit () {
    const resizeElements = {
        width: document.querySelector('input[name="image_maxwidth"]'),
        height: document.querySelector('input[name="image_maxheight"]'),
    };

    let i = 1;
    document.querySelectorAll('input[name="imageResizeInBrowser"]').forEach(element => {
        resizeElements['resizeInBrowser' + i] = element;
        i++;
    });

    function filesSetInputGroutStatus(group, enabled = null) {
        if (enabled === null) {
            let imageResizeEnabled = imageResizeSwitch.length > 0
                ? parseInt(document.querySelector('input[name="image_resize"]:checked').value, 10)
                : 0;
            enabled = (imageResizeEnabled !== 0)
        }

        for (const property in resizeElements) {
            resizeElements[property].disabled = !enabled;
        }
    }

    const configForm = document.querySelector('form[id="saveconfig"]');
    if (configForm !== null) {
        configForm.addEventListener('submit', (event) => {
            filesSetInputGroutStatus(resizeElements, true);
        });
    }

    const imageResizeSwitch = document.querySelectorAll('input[type=radio][name="image_resize"]');
    imageResizeSwitch.forEach(element => element.addEventListener('change', () => {
        filesSetInputGroutStatus(resizeElements);
    }));

    filesSetInputGroutStatus(resizeElements);
}

filesConfigFormInit();

ajaxSuccessHandlers.push(() => filesConfigFormInit());
