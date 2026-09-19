<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) {
    json_response(['error' => 'unauthorized'], 401);
}

garantir_plano_semanal_atual($user);

$pdo = getPDO();
$hasAccess = usuario_tem_acesso($user);
$isTrial = $user['subscription_status'] !== 'active' && em_periodo_de_teste($user);

$class = null;
if (!empty($user['class_id'])) {
    $stmt = $pdo->prepare('SELECT id, name, description FROM turmas WHERE id = ?');
    $stmt->execute([$user['class_id']]);
    $class = $stmt->fetch() ?: null;
}

$todayWorkout = null;
$week = [];

if ($hasAccess) {
    $today = (new DateTime())->format('Y-m-d');

    $stmt = $pdo->prepare('
        SELECT
            uti.id AS user_workout_id, uti.date, uti.status, t.id, t.tipo,
            COALESCE(uti.nome_personalizado, t.nome) AS nome,
            COALESCE(uti.conteudo_personalizado, t.conteudo) AS conteudo
        FROM user_treinos_ia uti
        JOIN treinos_ia t ON t.id = uti.treino_ia_id
        WHERE uti.user_id = ? AND uti.date = ?
    ');
    $stmt->execute([$user['id'], $today]);
    $todayWorkout = $stmt->fetch() ?: null;

    $monday = (new DateTime())->modify('monday this week')->format('Y-m-d');
    $sunday = (new DateTime())->modify('sunday this week')->format('Y-m-d');

    $stmt = $pdo->prepare('
        SELECT
            uti.id AS user_workout_id, uti.date, uti.status, t.id, t.tipo,
            COALESCE(uti.nome_personalizado, t.nome) AS nome,
            COALESCE(uti.conteudo_personalizado, t.conteudo) AS conteudo
        FROM user_treinos_ia uti
        JOIN treinos_ia t ON t.id = uti.treino_ia_id
        WHERE uti.user_id = ? AND uti.date BETWEEN ? AND ?
        ORDER BY uti.date ASC
    ');
    $stmt->execute([$user['id'], $monday, $sunday]);
    $week = $stmt->fetchAll();

    foreach ($week as &$item) {
        $item['week_day'] = (int) (new DateTime($item['date']))->format('N');
        $item['week_day_label'] = DIAS_SEMANA[$item['week_day']];
    }
    unset($item);
}

json_response([
    'firstName' => explode(' ', $user['name'])[0],
    'photoUrl' => $user['photo_url'] ?: null,
    'targetDistance' => $user['target_distance'] ?: null,
    'avgPace' => $user['avg_pace'] ?: null,
    'class' => $class,
    'subscriptionStatus' => $user['subscription_status'],
    'hasAccess' => $hasAccess,
    'isTrial' => $isTrial,
    'diasRestantes' => $isTrial ? dias_restantes_trial($user) : null,
    'todayWorkout' => $todayWorkout,
    'week' => $week,
]);
