<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

$input = json_body();
$level = $input['level'] ?? '';

if (!in_array($level, ['iniciante', 'intermediario', 'avancado'], true)) {
    json_response(['errors' => ['Selecione um nível válido.']], 422);
}

$pdo = getPDO();

// Atribui automaticamente a primeira turma (ordem alfabética) daquele nível.
$stmt = $pdo->prepare('SELECT * FROM turmas WHERE level = ? ORDER BY name ASC LIMIT 1');
$stmt->execute([$level]);
$turma = $stmt->fetch();

$userId = (int) $_SESSION['user_id'];

if ($turma) {
    $pdo->prepare('UPDATE users SET class_id = ? WHERE id = ?')->execute([$turma['id'], $userId]);
}

atribuir_plano_semanal_ao_usuario($userId, $level);

json_response(['ok' => true]);
