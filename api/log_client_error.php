<?php
require_once __DIR__ . '/_bootstrap.php';

$input = json_body();

log_error('client', (string) ($input['message'] ?? 'Erro desconhecido no navegador'), [
    'url' => $input['url'] ?? null,
    'line' => $input['line'] ?? null,
    'stack' => $input['stack'] ?? null,
    'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
]);

json_response(['ok' => true]);
