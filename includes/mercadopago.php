<?php

require_once __DIR__ . '/../config/database.php';

function mp_criar_preferencia(int $userId, string $userEmail): ?string
{
    $accessToken = env('MP_ACCESS_TOKEN');
    if ($accessToken === '') {
        return null;
    }

    $appUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

    $payload = [
        'items' => [[
            'title' => 'Plano AI Runner - Mensal',
            'quantity' => 1,
            'unit_price' => 29.90,
            'currency_id' => 'BRL',
        ]],
        'payer' => ['email' => $userEmail],
        'external_reference' => (string) $userId,
        'back_urls' => [
            'success' => $appUrl . '/views/pagamento_retorno.html?status=approved',
            'pending' => $appUrl . '/views/pagamento_retorno.html?status=pending',
            'failure' => $appUrl . '/views/pagamento_retorno.html?status=failure',
        ],
        'auto_return' => 'approved',
        'notification_url' => $appUrl . '/webhook.php',
    ];

    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return $data['init_point'] ?? null;
}

function mp_consultar_pagamento(string $paymentId): ?array
{
    $accessToken = env('MP_ACCESS_TOKEN');
    if ($accessToken === '') {
        return null;
    }

    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return is_array($data) ? $data : null;
}
