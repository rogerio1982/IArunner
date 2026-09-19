document.getElementById('login-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.target;
    const errorsBox = document.getElementById('errors');
    errorsBox.hidden = true;

    const { ok, data } = await apiFetch('/api/auth/login.php', {
        method: 'POST',
        body: JSON.stringify({
            email: form.email.value,
            password: form.password.value,
        }),
    });

    if (!ok) {
        errorsBox.textContent = data.error || 'Não foi possível entrar.';
        errorsBox.hidden = false;
        return;
    }

    window.location.href = data.role === 'coach'
        ? '/admin/coaching.html'
        : '/dashboard.html';
});
