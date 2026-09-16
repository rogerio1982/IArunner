<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$pdo = getPDO();
$hasAccess = usuario_tem_acesso($user);
$isTrial = $user['subscription_status'] !== 'active' && em_periodo_de_teste($user);

$todayWorkout = null;
$week = [];

if ($hasAccess) {
    $today = (new DateTime())->format('Y-m-d');

    $stmt = $pdo->prepare('
        SELECT uw.id AS user_workout_id, uw.date, uw.status, w.*
        FROM user_workouts uw
        JOIN workouts w ON w.id = uw.workout_id
        WHERE uw.user_id = ? AND uw.date = ?
    ');
    $stmt->execute([$user['id'], $today]);
    $todayWorkout = $stmt->fetch();

    $monday = (new DateTime())->modify('monday this week')->format('Y-m-d');
    $sunday = (new DateTime())->modify('sunday this week')->format('Y-m-d');

    $stmt = $pdo->prepare('
        SELECT uw.id AS user_workout_id, uw.date, uw.status, w.*
        FROM user_workouts uw
        JOIN workouts w ON w.id = uw.workout_id
        WHERE uw.user_id = ? AND uw.date BETWEEN ? AND ?
        ORDER BY uw.date ASC
    ');
    $stmt->execute([$user['id'], $monday, $sunday]);
    $week = $stmt->fetchAll();
}

$firstName = explode(' ', $user['name'])[0];

$pageTitle = 'Dashboard — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<nav class="flex items-center justify-between px-6 py-5 max-w-3xl mx-auto">
    <span class="text-xl font-extrabold tracking-tight">AI <span class="text-lime-400">Runner</span></span>
    <a href="/logout.php" class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-700 hover:bg-slate-800">Sair</a>
</nav>

<main class="max-w-3xl mx-auto px-6 pb-16">
    <h1 class="text-2xl font-bold">Olá, <?= h($firstName) ?> 👋</h1>

    <?php if ($isTrial): ?>
        <div class="mt-4 bg-lime-400/10 border border-lime-400/30 text-lime-300 rounded-xl px-4 py-3 text-sm text-center">
            Você está no teste grátis: <?= dias_restantes_trial($user) ?> dia(s) restante(s).
            <a href="/pagamento.php" class="underline font-semibold">Assinar agora</a>
        </div>
    <?php endif; ?>

    <?php if (!$hasAccess): ?>
        <div class="mt-6 bg-slate-900 rounded-2xl p-6 shadow text-center">
            <p class="text-slate-300">Seu período de teste grátis terminou.</p>
            <p class="text-slate-400 text-sm mt-1">Assine o plano para continuar recebendo seus treinos.</p>
            <a href="/pagamento.php" class="mt-4 inline-block px-6 py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                Assinar agora
            </a>
        </div>
    <?php else: ?>

        <?php if ($todayWorkout): ?>
            <section class="mt-6 bg-slate-900 rounded-2xl p-6 shadow">
                <h2 class="text-sm uppercase tracking-widest text-lime-400 font-semibold">Treino de hoje</h2>

                <?php if ($todayWorkout['is_rest_day']): ?>
                    <h3 class="mt-2 text-xl font-bold">🛌 <?= h($todayWorkout['title']) ?></h3>
                    <p class="mt-3 text-slate-300 text-sm"><?= h($todayWorkout['main_workout']) ?></p>
                <?php else: ?>
                    <h3 class="mt-2 text-xl font-bold">🏃 <?= h($todayWorkout['title']) ?></h3>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="bg-slate-950 rounded-xl p-4">
                            <p class="text-xs text-slate-400">Distância / Tempo</p>
                            <p class="text-lg font-bold"><?= h($todayWorkout['distance'] ?: '-') ?></p>
                        </div>
                        <div class="bg-slate-950 rounded-xl p-4">
                            <p class="text-xs text-slate-400">Foco</p>
                            <p class="text-sm font-semibold"><?= h($todayWorkout['notes'] ?: '-') ?></p>
                        </div>
                    </div>

                    <div class="mt-4 text-slate-300 text-sm space-y-2">
                        <?php if ($todayWorkout['warmup']): ?>
                            <p><span class="text-slate-400">Aquecimento:</span> <?= h($todayWorkout['warmup']) ?></p>
                        <?php endif; ?>
                        <p><span class="text-slate-400">Treino:</span> <?= h($todayWorkout['main_workout']) ?></p>
                        <?php if ($todayWorkout['cooldown']): ?>
                            <p><span class="text-slate-400">Desaquecimento:</span> <?= h($todayWorkout['cooldown']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($todayWorkout['status'] === 'pending'): ?>
                    <form method="post" action="/treinos.php" class="mt-6">
                        <input type="hidden" name="user_workout_id" value="<?= (int) $todayWorkout['user_workout_id'] ?>">
                        <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                            <?= $todayWorkout['is_rest_day'] ? 'Marcar dia como concluído' : 'Marcar como realizado' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <p class="mt-6 text-center text-lime-400 text-sm font-semibold">Treino de hoje já realizado ✅</p>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="mt-6 bg-slate-900 rounded-2xl p-6 shadow text-center">
                <p class="text-slate-300">Você ainda não tem um plano de treino.</p>
                <a href="/onboarding.php" class="mt-4 inline-block px-6 py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                    Escolher meu nível
                </a>
            </section>
        <?php endif; ?>

        <section class="mt-10">
            <h2 class="text-lg font-bold">Minha semana</h2>
            <div class="mt-4 space-y-3">
                <?php if (!$week): ?>
                    <p class="text-slate-400 text-sm">Nenhum treino registrado ainda.</p>
                <?php endif; ?>
                <?php foreach ($week as $item): ?>
                    <div class="bg-slate-900 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <p class="font-semibold"><?= h(DIAS_SEMANA[(int) $item['week_day']]) ?> — <?= h($item['title']) ?></p>
                            <p class="text-xs text-slate-400"><?= h($item['distance'] ?: 'Descanso') ?> · <?= h($item['date']) ?></p>
                        </div>
                        <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $item['status'] === 'done' ? 'bg-lime-400/20 text-lime-400' : 'bg-slate-700 text-slate-300' ?>">
                            <?= $item['status'] === 'done' ? 'Realizado' : 'Pendente' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
