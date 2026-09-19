<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

$stmt = getPDO()->query('SELECT id, name, description, level FROM turmas ORDER BY name ASC');
json_response(['classes' => $stmt->fetchAll()]);
