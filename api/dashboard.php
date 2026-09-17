<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
if (!$user) {
    json_response(['error' => 'unauthorized'], 401);
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
    $todayWorkout = $stmt->fetch() ?: null;

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

    foreach ($week as &$item) {
        $item['week_day_label'] = DIAS_SEMANA[(int) $item['week_day']];
    }
    unset($item);
}

json_response([
    'firstName' => explode(' ', $user['name'])[0],
    'hasAccess' => $hasAccess,
    'isTrial' => $isTrial,
    'diasRestantes' => $isTrial ? dias_restantes_trial($user) : null,
    'todayWorkout' => $todayWorkout,
    'week' => $week,
]);
