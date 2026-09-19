const DIAS_SEMANA_LABEL = {
    1: 'Segunda-feira', 2: 'Terça-feira', 3: 'Quarta-feira', 4: 'Quinta-feira',
    5: 'Sexta-feira', 6: 'Sábado', 7: 'Domingo',
};
const STATUS_LABEL = {
    pending: 'Pendente', done: 'Realizado', partial: 'Parcial', not_done: 'Não realizado',
};
const LEVEL_LABEL = { iniciante: 'Iniciante', intermediario: 'Intermediário', avancado: 'Avançado' };

let allClasses = [];
let allRecentAthletes = [];
let allAthletes = [];
let allTreinosIa = [];

function normalize(str) {
    return (str || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

async function api(action, options = {}) {
    return apiFetch(`/api/admin/coaching.php?action=${action}`, options);
}

function openModal(html) {
    document.getElementById('modal-box').innerHTML = html;
    document.getElementById('modal-backdrop').hidden = false;
}
function closeModal() {
    document.getElementById('modal-backdrop').hidden = true;
    document.getElementById('modal-box').innerHTML = '';
}
document.getElementById('modal-backdrop').addEventListener('click', (e) => {
    if (e.target.id === 'modal-backdrop') closeModal();
});

// ---------- Login ----------

document.getElementById('logout-btn').addEventListener('click', async () => {
    await api('logout', { method: 'POST' });
    window.location.href = '/views/login.html';
});

async function showApp() {
    document.getElementById('app').hidden = false;
    try {
        await loadOverview();
        await loadTreinosIa();
    } catch (err) {
        document.getElementById('athletes-list').innerHTML =
            `<p class="text-red-400 text-sm">Erro ao carregar dados: ${err.message || err}</p>`;
        console.error(err);
    }
}

// ---------- Tabs ----------

document.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach((b) => {
            b.classList.remove('border-lime-400', 'text-lime-400');
            b.classList.add('border-transparent', 'text-slate-400');
        });
        btn.classList.add('border-lime-400', 'text-lime-400');
        btn.classList.remove('border-transparent', 'text-slate-400');

        document.querySelectorAll('.tab-panel').forEach((p) => { p.hidden = true; });
        document.getElementById(`tab-${btn.dataset.tab}`).hidden = false;
    });
});

// ---------- Atletas / Turmas overview ----------

async function loadOverview() {
    const { ok, data } = await api('overview');
    if (!ok) return;

    allClasses = data.classes;
    allRecentAthletes = data.recentAthletes || [];
    allAthletes = data.allAthletes || [];
    applyAthletesSearch();
    applyClassesSearch();
}

function athleteMatchesSearch(user, term) {
    if (!term) return true;
    return normalize(user.name).includes(term)
        || normalize(user.email).includes(term)
        || normalize(user.whatsapp).includes(term);
}

function applyAthletesSearch() {
    const term = normalize(document.getElementById('athletes-search').value.trim());

    const filteredRecent = allRecentAthletes.filter((u) => athleteMatchesSearch(u, term));
    document.getElementById('recent-athletes-section').hidden = term !== '';
    renderRecentAthletes(filteredRecent);

    const filteredAthletes = allAthletes.filter((u) => athleteMatchesSearch(u, term));
    renderAthletesList(filteredAthletes);
}

document.getElementById('athletes-search').addEventListener('input', applyAthletesSearch);

function applyClassesSearch() {
    const term = normalize(document.getElementById('classes-search').value.trim());
    const filtered = allClasses.filter((c) =>
        normalize(c.name).includes(term) || normalize(c.description).includes(term)
    );
    renderClassCards(filtered);
}

document.getElementById('classes-search').addEventListener('input', applyClassesSearch);

function formatRelativeDate(isoDateTime) {
    const date = new Date(isoDateTime.replace(' ', 'T'));
    const diffMs = Date.now() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);

    if (diffMin < 1) return 'agora mesmo';
    if (diffMin < 60) return `há ${diffMin} min`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `há ${diffH}h`;
    const diffD = Math.floor(diffH / 24);
    if (diffD === 1) return 'ontem';
    if (diffD < 7) return `há ${diffD} dias`;
    return date.toLocaleDateString('pt-BR');
}

function renderRecentAthletes(recentAthletes) {
    const container = document.getElementById('recent-athletes-list');
    container.innerHTML = '';

    if (!recentAthletes || !recentAthletes.length) {
        container.innerHTML = '<p class="text-slate-400 text-sm py-2">Nenhum atleta cadastrado ainda.</p>';
        return;
    }

    recentAthletes.forEach((user) => {
        const row = document.createElement('div');
        row.className = 'py-3 flex items-center justify-between gap-3';
        row.innerHTML = `
            <div>
                <p class="font-semibold text-sm">${escapeHtml(user.name)}</p>
                <p class="text-xs text-slate-400">${escapeHtml(user.email)} · cadastrado ${formatRelativeDate(user.created_at)}</p>
            </div>
            <div class="flex items-center gap-2">
                ${accessBadge(user)}
                <button data-user-id="${user.id}" class="view-athlete-btn text-xs px-3 py-1 rounded-lg border border-slate-700 hover:bg-slate-800">Ver</button>
            </div>
        `;
        container.appendChild(row);
    });

    container.querySelectorAll('.view-athlete-btn').forEach((btn) => {
        btn.addEventListener('click', () => openAthleteModal(Number(btn.dataset.userId)));
    });
}

function accessBadge(user) {
    const cls = user.hasAccess ? 'bg-lime-400/20 text-lime-400' : 'bg-red-400/20 text-red-400';
    const label = user.subscription_status === 'active'
        ? 'Assinante'
        : (user.hasAccess ? 'Trial' : 'Sem acesso');
    return `<span class="text-xs font-semibold px-2 py-1 rounded-full ${cls}">${label}</span>`;
}

function renderAthletesList(athletes) {
    const container = document.getElementById('athletes-list');
    const countEl = document.getElementById('athletes-count');
    container.innerHTML = '';
    countEl.textContent = athletes.length ? `(${athletes.length})` : '';

    if (!athletes.length) {
        container.innerHTML = '<p class="text-slate-400 text-sm py-2">Nenhum atleta encontrado.</p>';
        return;
    }

    athletes.forEach((user) => {
        const row = document.createElement('div');
        row.className = 'py-3 flex items-center justify-between gap-3';
        row.innerHTML = `
            <div>
                <p class="font-semibold text-sm">${escapeHtml(user.name)}</p>
                <p class="text-xs text-slate-400">${escapeHtml(user.email)} · ${escapeHtml(user.whatsapp) || '-'} · ${user.class_name ? escapeHtml(user.class_name) : 'Sem turma'}</p>
            </div>
            <div class="flex items-center gap-2">
                ${accessBadge(user)}
                <button data-user-id="${user.id}" class="view-athlete-btn text-xs px-3 py-1 rounded-lg border border-slate-700 hover:bg-slate-800">Ver</button>
            </div>
        `;
        container.appendChild(row);
    });

    container.querySelectorAll('.view-athlete-btn').forEach((btn) => {
        btn.addEventListener('click', () => openAthleteModal(Number(btn.dataset.userId)));
    });
}

async function openAthleteModal(userId) {
    const { ok, data } = await api(`athlete&user_id=${userId}`);
    if (!ok) return;

    const { user, workouts } = data;
    const classOptions = allClasses.map((c) =>
        `<option value="${c.id}" ${c.id === user.class_id ? 'selected' : ''}>${c.name}</option>`
    ).join('');

    const workoutRows = workouts.map((w) => {
        const isCustom = !!(w.nome_personalizado || w.conteudo_personalizado);
        return `
        <div class="py-2 border-b border-slate-800 text-sm">
            <div class="flex justify-between items-start gap-2">
                <span class="font-semibold">${w.date} — ${escapeHtml(w.nome)}</span>
                <div class="flex items-center gap-2 shrink-0">
                    ${isCustom ? '<span class="text-xs px-2 py-0.5 rounded-full bg-lime-400/20 text-lime-400">Personalizado</span>' : ''}
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800">${STATUS_LABEL[w.status] || w.status}</span>
                    <button data-id="${w.user_workout_id}" class="edit-user-treino-btn text-xs px-2 py-1 rounded-lg border border-slate-700 hover:bg-slate-800">Editar</button>
                </div>
            </div>
            ${w.pse ? `<p class="text-xs text-slate-400 mt-1">PSE: ${w.pse}/10</p>` : ''}
            ${w.athlete_notes ? `<p class="text-xs text-slate-400 mt-1">"${escapeHtml(w.athlete_notes)}"</p>` : ''}
        </div>
    `;
    }).join('') || '<p class="text-sm text-slate-400">Nenhum treino registrado.</p>';

    openModal(`
        <h2 class="text-lg font-bold">${user.name}</h2>
        <p class="text-sm text-slate-400">${user.email} · ${user.whatsapp || '-'}</p>
        <p class="text-sm text-slate-400 mt-1">Meta: ${user.target_distance || '-'} · Pace médio: ${user.avg_pace || '-'}</p>

        <div class="mt-4">
            <label class="block text-sm text-slate-300 mb-1">Turma</label>
            <select id="move-class-select" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
                <option value="">Sem turma</option>
                ${classOptions}
            </select>
        </div>

        <h3 class="mt-6 font-semibold text-sm text-lime-400">Histórico de treinos</h3>
        <div class="mt-2 max-h-64 overflow-y-auto">${workoutRows}</div>

        <button id="close-modal-btn" class="mt-6 w-full py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-sm">Fechar</button>
    `);

    document.getElementById('close-modal-btn').addEventListener('click', closeModal);
    document.getElementById('move-class-select').addEventListener('change', async (e) => {
        await api('move_athlete', {
            method: 'POST',
            body: JSON.stringify({ user_id: user.id, class_id: e.target.value || null }),
        });
        closeModal();
        loadOverview();
    });

    document.querySelectorAll('.edit-user-treino-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const w = workouts.find((item) => item.user_workout_id === Number(btn.dataset.id));
            openUserTreinoModal(w, userId);
        });
    });
}

function openUserTreinoModal(workout, userId) {
    openModal(`
        <h2 class="text-lg font-bold">Personalizar treino — ${workout.date}</h2>
        <p class="text-xs text-slate-500 mt-1">Essa edição vale só para este atleta e este dia. O treino original do catálogo não é alterado.</p>
        <form id="user-treino-form" class="mt-4 space-y-3">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Nome</label>
                <input name="nome" required value="${escapeHtml(workout.nome)}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Conteúdo</label>
                <p class="text-xs text-slate-500 mb-1">A formatação (quebras de linha) é preservada exatamente como digitada.</p>
                <textarea name="conteudo" rows="14" required
                    class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-mono whitespace-pre-wrap">${escapeHtml(workout.conteudo)}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-lg bg-lime-400 text-slate-950 font-semibold text-sm hover:bg-lime-300">Salvar</button>
                <button type="button" id="restore-user-treino-btn" class="flex-1 py-2 rounded-lg border border-slate-700 text-sm hover:bg-slate-800">Restaurar original</button>
                <button type="button" id="cancel-user-treino-btn" class="flex-1 py-2 rounded-lg border border-slate-700 text-sm hover:bg-slate-800">Cancelar</button>
            </div>
        </form>
    `);

    document.getElementById('cancel-user-treino-btn').addEventListener('click', () => openAthleteModal(userId));

    document.getElementById('restore-user-treino-btn').addEventListener('click', async () => {
        if (!confirm('Restaurar o treino original do catálogo para este dia?')) return;
        await api('update_user_treino', {
            method: 'POST',
            body: JSON.stringify({ user_workout_id: workout.user_workout_id, nome: '', conteudo: '' }),
        });
        openAthleteModal(userId);
    });

    document.getElementById('user-treino-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        await api('update_user_treino', {
            method: 'POST',
            body: JSON.stringify({
                user_workout_id: workout.user_workout_id,
                nome: form.nome.value,
                conteudo: form.conteudo.value,
            }),
        });
        openAthleteModal(userId);
    });
}

// ---------- Turmas CRUD ----------

function renderClassCards(classes) {
    const container = document.getElementById('turmas-list');
    container.innerHTML = '';

    classes.forEach((c) => {
        const card = document.createElement('div');
        card.className = 'bg-slate-900 rounded-2xl p-5';
        card.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold">${c.name}</h3>
                    <p class="text-xs text-slate-400 mt-1">${c.description || '-'}</p>
                    <p class="text-xs text-lime-400 mt-1">${LEVEL_LABEL[c.level]} · ${c.athletes.length} atleta(s)</p>
                </div>
                <div class="flex gap-2">
                    <button data-id="${c.id}" class="edit-class-btn text-xs px-2 py-1 rounded-lg border border-slate-700 hover:bg-slate-800">Editar</button>
                    <button data-id="${c.id}" class="delete-class-btn text-xs px-2 py-1 rounded-lg border border-red-800 text-red-300 hover:bg-red-950">Excluir</button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });

    container.querySelectorAll('.edit-class-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const c = classes.find((x) => x.id === Number(btn.dataset.id));
            openClassModal(c);
        });
    });
    container.querySelectorAll('.delete-class-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Excluir esta turma? Atletas vinculados ficarão sem turma.')) return;
            await api('delete_class', { method: 'POST', body: JSON.stringify({ id: Number(btn.dataset.id) }) });
            loadOverview();
        });
    });
}

document.getElementById('new-class-btn').addEventListener('click', () => openClassModal(null));

function openClassModal(cls) {
    openModal(`
        <h2 class="text-lg font-bold">${cls ? 'Editar turma' : 'Nova turma'}</h2>
        <form id="class-form" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Nome</label>
                <input name="name" required value="${cls ? cls.name : ''}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Descrição</label>
                <input name="description" value="${cls ? cls.description : ''}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Nível</label>
                <select name="level" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
                    <option value="iniciante" ${cls && cls.level === 'iniciante' ? 'selected' : ''}>Iniciante</option>
                    <option value="intermediario" ${cls && cls.level === 'intermediario' ? 'selected' : ''}>Intermediário</option>
                    <option value="avancado" ${cls && cls.level === 'avancado' ? 'selected' : ''}>Avançado</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-lg bg-lime-400 text-slate-950 font-semibold text-sm hover:bg-lime-300">Salvar</button>
                <button type="button" id="cancel-class-btn" class="flex-1 py-2 rounded-lg border border-slate-700 text-sm hover:bg-slate-800">Cancelar</button>
            </div>
        </form>
    `);

    document.getElementById('cancel-class-btn').addEventListener('click', closeModal);
    document.getElementById('class-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        await api('save_class', {
            method: 'POST',
            body: JSON.stringify({
                id: cls ? cls.id : null,
                name: form.name.value,
                description: form.description.value,
                level: form.level.value,
            }),
        });
        closeModal();
        loadOverview();
    });
}

// ---------- Treinos IA CRUD ----------

document.getElementById('treino-ia-filter').addEventListener('change', loadTreinosIa);
document.getElementById('treinos-ia-search').addEventListener('input', applyTreinosIaSearch);
document.getElementById('new-treino-ia-btn').addEventListener('click', () => openTreinoIaModal(null));

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

async function loadTreinosIa() {
    const tipo = document.getElementById('treino-ia-filter').value;
    const { ok, data } = await api(`treinos_ia&tipo=${tipo}`);
    if (!ok) return;

    allTreinosIa = data.treinos;
    applyTreinosIaSearch();
}

function applyTreinosIaSearch() {
    const term = normalize(document.getElementById('treinos-ia-search').value.trim());
    const filtered = allTreinosIa.filter((t) => normalize(t.nome).includes(term));
    renderTreinosIa(filtered);
}

function renderTreinosIa(treinos) {
    const container = document.getElementById('treinos-ia-list');
    container.innerHTML = '';

    if (!treinos.length) {
        container.innerHTML = '<p class="text-slate-400 text-sm">Nenhum treino IA encontrado.</p>';
        return;
    }

    treinos.forEach((t) => {
        const card = document.createElement('div');
        card.className = 'bg-slate-900 rounded-xl p-4';
        card.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-sm">${escapeHtml(t.nome)}</p>
                    <p class="text-xs text-lime-400">${LEVEL_LABEL[t.tipo] || t.tipo}</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <button data-id="${t.id}" class="edit-treino-ia-btn text-xs px-3 py-1 rounded-lg border border-slate-700 hover:bg-slate-800">Editar</button>
                    <button data-id="${t.id}" class="delete-treino-ia-btn text-xs px-3 py-1 rounded-lg border border-red-800 text-red-300 hover:bg-red-950">Excluir</button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });

    container.querySelectorAll('.edit-treino-ia-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const { ok: okDetail, data: detail } = await api(`treino_ia&id=${btn.dataset.id}`);
            if (okDetail) openTreinoIaModal(detail.treino);
        });
    });
    container.querySelectorAll('.delete-treino-ia-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Excluir este treino IA?')) return;
            await api('delete_treino_ia', { method: 'POST', body: JSON.stringify({ id: Number(btn.dataset.id) }) });
            loadTreinosIa();
        });
    });
}

function openTreinoIaModal(treino) {
    treino = treino || {};

    openModal(`
        <h2 class="text-lg font-bold">${treino.id ? 'Editar treino IA' : 'Novo treino IA'}</h2>
        <form id="treino-ia-form" class="mt-4 space-y-3">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Nome</label>
                <input name="nome" required value="${treino.nome ? escapeHtml(treino.nome) : ''}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Tipo</label>
                <select name="tipo" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm">
                    <option value="iniciante" ${treino.tipo === 'iniciante' ? 'selected' : ''}>Iniciante</option>
                    <option value="intermediario" ${treino.tipo === 'intermediario' ? 'selected' : ''}>Intermediário</option>
                    <option value="avancado" ${treino.tipo === 'avancado' ? 'selected' : ''}>Avançado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Conteúdo</label>
                <p class="text-xs text-slate-500 mb-1">A formatação (quebras de linha) é preservada exatamente como digitada.</p>
                <textarea name="conteudo" rows="14" required
                    class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-mono whitespace-pre-wrap">${treino.conteudo ? escapeHtml(treino.conteudo) : ''}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-lg bg-lime-400 text-slate-950 font-semibold text-sm hover:bg-lime-300">Salvar</button>
                <button type="button" id="cancel-treino-ia-btn" class="flex-1 py-2 rounded-lg border border-slate-700 text-sm hover:bg-slate-800">Cancelar</button>
            </div>
        </form>
    `);

    document.getElementById('cancel-treino-ia-btn').addEventListener('click', closeModal);
    document.getElementById('treino-ia-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        await api('save_treino_ia', {
            method: 'POST',
            body: JSON.stringify({
                id: treino.id || null,
                nome: form.nome.value,
                tipo: form.tipo.value,
                conteudo: form.conteudo.value,
            }),
        });
        closeModal();
        loadTreinosIa();
    });
}

// ---------- Init ----------

(async function init() {
    try {
        const { ok } = await api('overview');
        if (!ok) {
            window.location.href = '/views/login.html';
            return;
        }
        showApp();
    } catch (err) {
        console.error('Falha ao verificar sessão de admin:', err);
        window.location.href = '/views/login.html';
    }
})();
