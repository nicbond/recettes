import $ from 'jquery';
function initThumbnailPreview() {
    const $input = $('input[type="file"]');

    if ($input.length === 0) {
        return;
    }

    $input.off('change.thumbnail').on('change.thumbnail', function () {
        const file = this.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            const $container = $('#thumbnail-preview-container');

            $container
                .removeClass('bg-secondary')
                .html(
                    `<img id="thumbnail-preview"
                          src="${e.target.result}"
                          alt="Aperçu de la recette"
                          style="width: 100%; height: 100%; object-fit: cover;">`
                );
        };

        reader.readAsDataURL(file);
    });
}

$(document).ready(function () {
    initThumbnailPreview();
});

document.addEventListener('turbo:frame-load', function () {
    initThumbnailPreview();
});