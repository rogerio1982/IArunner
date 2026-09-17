async function init() {
    const user = await requireLogin();
    if (!user) return;

    const status = new URLSearchParams(window.location.search).get('status') || 'pending';
    const { data } = await apiFetch(`/api/pagamento_retorno.php?status=${encodeURIComponent(status)}`);

    document.getElementById('msg-title').textContent = data.title;
    document.getElementById('msg-text').textContent = data.text;
}

init();
