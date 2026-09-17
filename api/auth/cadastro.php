<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

$input = json_body();
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$whatsapp = trim($input['whatsapp'] ?? '');
$password = $input['password'] ?? '';

$errors = [];

if ($name === '' || $whatsapp === '') {
    $errors[] = 'Preencha todos os campos.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido.';
}
if (strlen($password) < 6) {
    $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
}

if (empty($errors)) {
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Este e-mail já está cadastrado.';
    }
}

if (!empty($errors)) {
    json_response(['errors' => $errors], 422);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// 7 dias de teste grátis a partir do cadastro.
$trialEndsAt = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

$stmt = $pdo->prepare('INSERT INTO users (name, email, whatsapp, password, trial_ends_at) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, $whatsapp, $hash, $trialEndsAt]);

$_SESSION['user_id'] = (int) $pdo->lastInsertId();

json_response(['ok' => true]);
