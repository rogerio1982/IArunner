const DIAS_SEMANA_LABEL = {
    1: 'Segunda-feira', 2: 'Terça-feira', 3: 'Quarta-feira', 4: 'Quinta-feira',
    5: 'Sexta-feira', 6: 'Sábado', 7: 'Domingo',
};

function renderTodayWorkout(workout) {
    const body = document.getElementById('today-workout-body');
    body.innerHTML = '';

    const titleEl = document.createElement('h3');
    titleEl.className = 'mt-2 text-xl font-bold';
    titleEl.textContent = '🏃 ' + workout.nome;
    body.appendChild(titleEl);

    const content = document.createElement('pre');
    content.className = 'mt-4 text-slate-300 text-sm whitespace-pre-wrap font-sans';
    content.textContent = workout.conteudo;
    body.appendChild(content);

    if (workout.status === 'pending') {
        const form = document.createElement('form');
        form.className = 'mt-6 space-y-4';
        form.innerHTML = `
            <div>
                <label class="block text-sm text-slate-300 mb-1">Como foi o treino?</label>
                <select name="status" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-2">
                    <option value="done">Realizado integralmente</option>
                    <option value="partial">Realizado parcialmente</option>
                    <option value="not_done">Não realizado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Percepção de esforço (PSE): <span id="pse-value">5</span>/10</label>
                <input type="range" name="pse" min="1" max="10" value="5" class="w-full">
            </div>
            <div>
                <label class="block text-sm text-slate-300 mb-1">Observações (opcional)</label>
                <textarea name="athlete_notes" rows="2" placeholder="Ex.: Senti um leve desconforto no joelho no km 5"
                    class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                Enviar feedback do treino
            </button>
        `;
        const pseInput = form.querySelector('input[name="pse"]');
        const pseValue = form.querySelector('#pse-value');
        pseInput.addEventListener('input', () => { pseValue.textContent = pseInput.value; });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await apiFetch('/api/treinos.php', {
                method: 'POST',
                body: JSON.stringify({
                    user_workout_id: workout.user_workout_id,
                    status: form.status.value,
                    pse: Number(pseInput.value),
                    athlete_notes: form.athlete_notes.value,
                }),
            });
            window.location.reload();
        });
        body.appendChild(form);
    } else {
        const statusLabel = {
            done: 'Treino de hoje já realizado ✅',
            partial: 'Treino de hoje realizado parcialmente ⚠️',
            not_done: 'Treino de hoje marcado como não realizado',
        }[workout.status] || 'Treino de hoje já registrado';

        const p = document.createElement('p');
        p.className = 'mt-6 text-center text-lime-400 text-sm font-semibold';
        p.textContent = statusLabel;
        body.appendChild(p);

        if (workout.athlete_notes) {
            const notes = document.createElement('p');
            notes.className = 'mt-2 text-center text-slate-400 text-xs';
            notes.textContent = `"${workout.athlete_notes}"`;
            body.appendChild(notes);
        }
    }
}

function renderWeek(week) {
    const list = document.getElementById('week-list');
    list.innerHTML = '';

    if (!week.length) {
        const p = document.createElement('p');
        p.className = 'text-slate-400 text-sm';
        p.textContent = 'Nenhum treino registrado ainda.';
        list.appendChild(p);
        return;
    }

    week.forEach((item) => {
        const row = document.createElement('div');
        row.className = 'bg-slate-900 rounded-xl p-4 flex items-center justify-between';

        const left = document.createElement('div');
        const title = document.createElement('p');
        title.className = 'font-semibold';
        title.textContent = `${DIAS_SEMANA_LABEL[item.week_day]} — ${item.nome}`;
        const sub = document.createElement('p');
        sub.className = 'text-xs text-slate-400';
        sub.textContent = item.date;
        left.append(title, sub);

        const STATUS_BADGE = {
            done: { label: 'Realizado', className: 'bg-lime-400/20 text-lime-400' },
            partial: { label: 'Parcial', className: 'bg-yellow-400/20 text-yellow-400' },
            not_done: { label: 'Não realizado', className: 'bg-red-400/20 text-red-400' },
            pending: { label: 'Pendente', className: 'bg-slate-700 text-slate-300' },
        };
        const badgeInfo = STATUS_BADGE[item.status] || STATUS_BADGE.pending;

        const badge = document.createElement('span');
        badge.className = 'text-xs font-semibold px-3 py-1 rounded-full ' + badgeInfo.className;
        badge.textContent = badgeInfo.label;

        row.append(left, badge);
        list.appendChild(row);
    });
}

async function init() {
    const user = await requireLogin();
    if (!user) return;

    const { data } = await apiFetch('/api/dashboard.php');

    document.getElementById('first-name').textContent = data.firstName;

    if (data.class) {
        const classLabel = document.getElementById('class-label');
        classLabel.textContent = `Turma: ${data.class.name}`;
        classLabel.hidden = false;
    }

    if (data.subscriptionStatus !== 'active') {
        document.getElementById('pagamento-btn').hidden = false;
    }

    if (data.isTrial) {
        document.getElementById('dias-restantes').textContent = data.diasRestantes;
        document.getElementById('trial-banner').hidden = false;
    }

    if (!data.hasAccess) {
        document.getElementById('no-access').hidden = false;
        return;
    }

    document.getElementById('access-content').hidden = false;

    if (data.todayWorkout) {
        document.getElementById('today-workout').hidden = false;
        renderTodayWorkout(data.todayWorkout);
    } else {
        document.getElementById('no-plan').hidden = false;
    }

    renderWeek(data.week);
}

document.getElementById('pagamento-btn').addEventListener('click', () => {
    window.location.href = '/views/pagamento.html';
});

document.getElementById('logout-btn').addEventListener('click', async () => {
    await apiFetch('/api/auth/logout.php', { method: 'POST' });
    window.location.href = '/views/index.html';
});

init();
