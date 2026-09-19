document.getElementById('cadastro-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.target;
    const errorsBox = document.getElementById('errors');
    errorsBox.hidden = true;

    const { ok, data } = await apiFetch('/api/auth/cadastro.php', {
        method: 'POST',
        body: JSON.stringify({
            name: form.name.value,
            email: form.email.value,
            whatsapp: form.whatsapp.value,
            password: form.password.value,
        }),
    });

    if (!ok) {
        const errors = data.errors || ['Não foi possível criar a conta.'];
        errorsBox.innerHTML = '';
        errors.forEach((msg) => {
            const p = document.createElement('p');
            p.textContent = msg;
            errorsBox.appendChild(p);
        });
        errorsBox.hidden = false;
        return;
    }

    window.location.href = '/onboarding.html';
});
