<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

$input = json_body();
$level = $input['level'] ?? '';

$validLevels = ['iniciante', 'intermediario', 'avancado'];

if (!in_array($level, $validLevels, true)) {
    json_response(['errors' => ['Selecione uma opção válida.']], 422);
}

atribuir_plano_semanal_ao_usuario((int) $_SESSION['user_id'], $level);

json_response(['ok' => true]);
