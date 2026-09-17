<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

$input = json_body();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$stmt = getPDO()->prepare('SELECT id, password FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['error' => 'E-mail ou senha inválidos.'], 401);
}

$_SESSION['user_id'] = (int) $user['id'];

json_response(['ok' => true]);
