<?php

require_once __DIR__ . '/../config/database.php';

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

const DIAS_SEMANA = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
    7 => 'Domingo',
];

/**
 * Monta o plano semanal (7 dias) do nível informado e atribui ao usuário,
 * usando a semana atual (segunda a domingo) como referência de datas.
 * TODO: futuramente substituir por geração dinâmica via OpenAI.
 */
function atribuir_plano_semanal_ao_usuario(int $userId, string $level): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM workouts WHERE level = ? ORDER BY week_day ASC');
    $stmt->execute([$level]);
    $workouts = $stmt->fetchAll();

    if (!$workouts) {
        return;
    }

    $monday = new DateTime();
    $monday->modify('monday this week');

    $insert = $pdo->prepare('INSERT INTO user_workouts (user_id, workout_id, date, status) VALUES (?, ?, ?, "pending")');

    foreach ($workouts as $workout) {
        $date = (clone $monday)->modify('+' . ($workout['week_day'] - 1) . ' days')->format('Y-m-d');
        $insert->execute([$userId, $workout['id'], $date]);
    }
}

/**
 * Usuário tem acesso liberado se a assinatura está ativa OU se ainda está no período de teste de 7 dias.
 */
function usuario_tem_acesso(array $user): bool
{
    if ($user['subscription_status'] === 'active') {
        return true;
    }

    return em_periodo_de_teste($user);
}

function em_periodo_de_teste(array $user): bool
{
    return strtotime($user['trial_ends_at']) > time();
}

function dias_restantes_trial(array $user): int
{
    $diff = strtotime($user['trial_ends_at']) - time();

    return max(0, (int) ceil($diff / 86400));
}
