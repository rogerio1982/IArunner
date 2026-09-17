async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });
    const data = await response.json().catch(() => ({}));
    return { ok: response.ok, status: response.status, data };
}

async function requireLogin() {
    const { ok, data } = await apiFetch('/api/auth/me.php');
    if (!ok) {
        window.location.href = '/views/login.html';
        return null;
    }
    return data.user;
}
