const DIAS_SEMANA_LABEL = {
    1: 'Segunda-feira', 2: 'Terça-feira', 3: 'Quarta-feira', 4: 'Quinta-feira',
    5: 'Sexta-feira', 6: 'Sábado', 7: 'Domingo',
};

function renderTodayWorkout(workout) {
    const body = document.getElementById('today-workout-body');
    body.innerHTML = '';

    const titleEl = document.createElement('h3');
    titleEl.className = 'mt-2 text-xl font-bold';
    titleEl.textContent = (workout.is_rest_day ? '🛌 ' : '🏃 ') + workout.title;
    body.appendChild(titleEl);

    if (workout.is_rest_day) {
        const p = document.createElement('p');
        p.className = 'mt-3 text-slate-300 text-sm';
        p.textContent = workout.main_workout;
        body.appendChild(p);
    } else {
        const grid = document.createElement('div');
        grid.className = 'mt-4 grid grid-cols-2 gap-4';
        grid.innerHTML = `
            <div class="bg-slate-950 rounded-xl p-4">
                <p class="text-xs text-slate-400">Distância / Tempo</p>
                <p class="text-lg font-bold" id="workout-distance"></p>
            </div>
            <div class="bg-slate-950 rounded-xl p-4">
                <p class="text-xs text-slate-400">Foco</p>
                <p class="text-sm font-semibold" id="workout-notes"></p>
            </div>
        `;
        body.appendChild(grid);
        grid.querySelector('#workout-distance').textContent = workout.distance || '-';
        grid.querySelector('#workout-notes').textContent = workout.notes || '-';

        const details = document.createElement('div');
        details.className = 'mt-4 text-slate-300 text-sm space-y-2';
        if (workout.warmup) {
            const p = document.createElement('p');
            p.innerHTML = '<span class="text-slate-400">Aquecimento:</span> ';
            p.append(workout.warmup);
            details.appendChild(p);
        }
        const mainP = document.createElement('p');
        mainP.innerHTML = '<span class="text-slate-400">Treino:</span> ';
        mainP.append(workout.main_workout);
        details.appendChild(mainP);
        if (workout.cooldown) {
            const p = document.createElement('p');
            p.innerHTML = '<span class="text-slate-400">Desaquecimento:</span> ';
            p.append(workout.cooldown);
            details.appendChild(p);
        }
        body.appendChild(details);
    }

    if (workout.status === 'pending') {
        const form = document.createElement('form');
        form.className = 'mt-6';
        form.innerHTML = `
            <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                ${workout.is_rest_day ? 'Marcar dia como concluído' : 'Marcar como realizado'}
            </button>
        `;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await apiFetch('/api/treinos.php', {
                method: 'POST',
                body: JSON.stringify({ user_workout_id: workout.user_workout_id }),
            });
            window.location.reload();
        });
        body.appendChild(form);
    } else {
        const p = document.createElement('p');
        p.className = 'mt-6 text-center text-lime-400 text-sm font-semibold';
        p.textContent = 'Treino de hoje já realizado ✅';
        body.appendChild(p);
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
        title.textContent = `${DIAS_SEMANA_LABEL[item.week_day]} — ${item.title}`;
        const sub = document.createElement('p');
        sub.className = 'text-xs text-slate-400';
        sub.textContent = `${item.distance || 'Descanso'} · ${item.date}`;
        left.append(title, sub);

        const badge = document.createElement('span');
        const isDone = item.status === 'done';
        badge.className = 'text-xs font-semibold px-3 py-1 rounded-full ' +
            (isDone ? 'bg-lime-400/20 text-lime-400' : 'bg-slate-700 text-slate-300');
        badge.textContent = isDone ? 'Realizado' : 'Pendente';

        row.append(left, badge);
        list.appendChild(row);
    });
}

async function init() {
    const user = await requireLogin();
    if (!user) return;

    const { data } = await apiFetch('/api/dashboard.php');

    document.getElementById('first-name').textContent = data.firstName;

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

document.getElementById('logout-btn').addEventListener('click', async () => {
    await apiFetch('/api/auth/logout.php', { method: 'POST' });
    window.location.href = '/views/index.html';
});

init();
