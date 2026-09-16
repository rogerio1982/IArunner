<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mercadopago.php';

header('Content-Type: application/json');

$topic = $_GET['topic'] ?? $_GET['type'] ?? '';
$paymentId = $_GET['id'] ?? $_GET['data_id'] ?? null;

if ($paymentId === null) {
    $body = json_decode(file_get_contents('php://input'), true);
    $paymentId = $body['data']['id'] ?? null;
}

if ($topic !== 'payment' && ($_GET['type'] ?? '') !== 'payment') {
    http_response_code(200);
    echo json_encode(['ignored' => true]);
    exit;
}

if (!$paymentId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing payment id']);
    exit;
}

$payment = mp_consultar_pagamento((string) $paymentId);

if ($payment && ($payment['status'] ?? '') === 'approved') {
    $userId = (int) ($payment['external_reference'] ?? 0);

    if ($userId > 0) {
        $stmt = getPDO()->prepare('UPDATE users SET subscription_status = "active" WHERE id = ?');
        $stmt->execute([$userId]);
    }
}

http_response_code(200);
echo json_encode(['ok' => true]);
