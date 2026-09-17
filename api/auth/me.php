<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = current_user();

if (!$user) {
    json_response(['error' => 'unauthorized'], 401);
}

json_response(['user' => $user]);
