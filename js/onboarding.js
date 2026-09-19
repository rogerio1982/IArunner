requireLogin();

document.getElementById('onboarding-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    const form = event.target;
    const errorsBox = document.getElementById('errors');
    errorsBox.hidden = true;

    const level = form.level.value;

    const { ok, data } = await apiFetch('/api/onboarding.php', {
        method: 'POST',
        body: JSON.stringify({ level }),
    });

    if (!ok) {
        const errors = data.errors || ['Não foi possível continuar.'];
        errorsBox.innerHTML = '';
        errors.forEach((msg) => {
            const p = document.createElement('p');
            p.textContent = msg;
            errorsBox.appendChild(p);
        });
        errorsBox.hidden = false;
        return;
    }

    window.location.href = '/views/dashboard.html';
});
