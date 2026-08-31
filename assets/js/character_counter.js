// jQuery is already included in the project for other features, but for this simple counter
// I opted for vanilla JS to avoid unnecessarily increasing the page size!
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.querySelector('#recipe_content');

    if (!textarea) return;

    const counter = document.createElement('div');
    counter.classList.add('form-text', 'text-muted', 'text-end');
    textarea.parentNode.appendChild(counter);

    function updateCounter() {
        const max = textarea.getAttribute('maxlength');
        if (max) {
            counter.textContent = textarea.value.length + ' / ' + max + ' caractères';
            counter.classList.toggle('text-danger', textarea.value.length >= max);
        } else {
            counter.textContent = textarea.value.length + ' caractère(s) saisi(s)';
        }
    }

    textarea.addEventListener('input', updateCounter);
    updateCounter();
});