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
 * Fonte: treinos_ia (texto livre, gerado por IA) — catálogo por nível, sem
 * dia da semana fixo no treino. Pega os treinos mais recentes do nível, um
 * por dia, repetindo o catálogo se houver menos de 7 cadastrados.
 */
function atribuir_plano_semanal_ao_usuario(int $userId, string $level): void
{
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT * FROM treinos_ia WHERE tipo = ? ORDER BY created_at DESC');
    $stmt->execute([$level]);
    $treinos = $stmt->fetchAll();

    if (!$treinos) {
        return;
    }

    $monday = new DateTime();
    $monday->modify('monday this week');

    $insert = $pdo->prepare('INSERT INTO user_treinos_ia (user_id, treino_ia_id, date, status) VALUES (?, ?, ?, "pending")');

    for ($weekDay = 1; $weekDay <= 7; $weekDay++) {
        $treino = $treinos[($weekDay - 1) % count($treinos)];
        $date = (clone $monday)->modify('+' . ($weekDay - 1) . ' days')->format('Y-m-d');
        $insert->execute([$userId, $treino['id'], $date]);
    }
}

/**
 * Garante que a semana atual (segunda a domingo) do atleta já tem treinos
 * gerados. Se não tiver, gera na hora a partir do nível da turma dele —
 * é assim que a "próxima semana" é montada automaticamente, sem cron: a
 * checagem roda a cada acesso ao dashboard (ver api/dashboard.php).
 * Atleta sem turma (sem nível definido) não gera nada.
 */
function garantir_plano_semanal_atual(array $user): void
{
    if (empty($user['class_id'])) {
        return;
    }

    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT level FROM turmas WHERE id = ?');
    $stmt->execute([$user['class_id']]);
    $turma = $stmt->fetch();

    if (!$turma) {
        return;
    }

    $monday = (new DateTime())->modify('monday this week')->format('Y-m-d');

    $stmt = $pdo->prepare('SELECT 1 FROM user_treinos_ia WHERE user_id = ? AND date = ? LIMIT 1');
    $stmt->execute([$user['id'], $monday]);

    if ($stmt->fetch()) {
        return;
    }

    atribuir_plano_semanal_ao_usuario((int) $user['id'], $turma['level']);
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

const STATUS_TREINO_LABEL = [
    'pending' => 'Pendente',
    'done' => 'Realizado',
    'partial' => 'Realizado parcialmente',
    'not_done' => 'Não realizado',
];
