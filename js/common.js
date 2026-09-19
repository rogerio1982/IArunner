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

function reportClientError(message, extra = {}) {
    try {
        fetch('/api/log_client_error.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, url: window.location.href, ...extra }),
            keepalive: true,
        }).catch(() => {});
    } catch (e) {
        // Nunca deixa o log de erro quebrar a página.
    }
}

window.addEventListener('error', (event) => {
    reportClientError(event.message, {
        line: event.lineno,
        stack: event.error ? event.error.stack : null,
    });
});

window.addEventListener('unhandledrejection', (event) => {
    reportClientError('Promise rejeitada: ' + (event.reason && event.reason.message || event.reason), {
        stack: event.reason && event.reason.stack,
    });
});
