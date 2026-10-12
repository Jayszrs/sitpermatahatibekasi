document.addEventListener('click', async function (event) {
    const button = event.target.closest('[data-copy-url]');
    if (!button) return;
    const original = button.textContent;
    try {
        await navigator.clipboard.writeText(button.dataset.copyUrl);
        button.textContent = 'Tautan tersalin';
    } catch (error) {
        const input = document.createElement('input');
        input.value = button.dataset.copyUrl;
        document.body.appendChild(input);
        input.select();
        const copied = document.execCommand('copy');
        input.remove();
        button.textContent = copied ? 'Tautan tersalin' : 'Gagal menyalin';
    }
    window.setTimeout(() => { button.textContent = original; }, 2400);
});
