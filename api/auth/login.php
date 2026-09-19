<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

$input = json_body();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = (int) $user['id'];
    json_response(['ok' => true, 'role' => 'athlete']);
}

$stmt = $pdo->prepare('SELECT id, password FROM coaches WHERE email = ?');
$stmt->execute([$email]);
$coach = $stmt->fetch();

if ($coach && password_verify($password, $coach['password'])) {
    $_SESSION['coach_id'] = (int) $coach['id'];
    json_response(['ok' => true, 'role' => 'coach']);
}

json_response(['error' => 'E-mail ou senha inválidos.'], 401);
