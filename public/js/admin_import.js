const loginForm = document.getElementById('admin-login-form');
const importForm = document.getElementById('import-form');
const errorBox = document.getElementById('error');
const successBox = document.getElementById('success');

function showError(msg) {
    errorBox.textContent = msg;
    errorBox.hidden = false;
}

function showAdminForm() {
    loginForm.hidden = true;
    importForm.hidden = false;
}

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.hidden = true;

    const formData = new FormData(loginForm);
    const response = await fetch('/api/admin/import.php', {
        method: 'POST',
        credentials: 'include',
        body: formData,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        showError(data.error || 'Senha incorreta.');
        return;
    }

    showAdminForm();
});

importForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.hidden = true;
    successBox.hidden = true;

    const formData = new FormData(importForm);
    const response = await fetch('/api/admin/import.php', {
        method: 'POST',
        credentials: 'include',
        body: formData,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        showError(data.error || 'Erro ao importar.');
        return;
    }

    successBox.textContent = `${data.imported} treinos importados com sucesso.`;
    successBox.hidden = false;
    importForm.reset();
});
