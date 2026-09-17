<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

// O status real da assinatura é confirmado pelo webhook.php, não por este parâmetro de URL.
$status = $_GET['status'] ?? 'pending';

$messages = [
    'approved' => ['title' => 'Pagamento aprovado!', 'text' => 'Seu plano será ativado em instantes.'],
    'pending' => ['title' => 'Pagamento pendente', 'text' => 'Assim que for aprovado, seu plano será ativado automaticamente.'],
    'failure' => ['title' => 'Pagamento não concluído', 'text' => 'Tente novamente para ativar seu plano.'],
];

json_response(['status' => $status] + ($messages[$status] ?? $messages['pending']));
