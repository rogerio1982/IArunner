requireLogin();

document.getElementById('pagamento-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const errorBox = document.getElementById('error');
    errorBox.hidden = true;

    const { ok, data } = await apiFetch('/api/pagamento.php', { method: 'POST' });

    if (!ok) {
        errorBox.textContent = data.error || 'Pagamento indisponível no momento.';
        errorBox.hidden = false;
        return;
    }

    window.location.href = data.init_point;
});
