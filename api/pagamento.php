<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mercadopago.php';

$user = current_user();
if (!$user) {
    json_response(['error' => 'unauthorized'], 401);
}

$initPoint = mp_criar_preferencia((int) $user['id'], $user['email']);

if (!$initPoint) {
    json_response(['error' => 'Pagamento indisponível no momento. Verifique as credenciais do Mercado Pago no .env.'], 502);
}

json_response(['init_point' => $initPoint]);
