import $ from 'jquery';

$(document).ready(function () {
    const $input = $('input[type="file"]');

    if ($input.length === 0) return;

    const $preview = $('#thumbnail-preview');

    $input.on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $preview.attr('src', e.target.result);
                $preview.show();
            };
            reader.readAsDataURL(file);
        }
    });
});