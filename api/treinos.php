<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

$input = json_body();
$userWorkoutId = (int) ($input['user_workout_id'] ?? 0);

if ($userWorkoutId > 0) {
    // Garante que o registro pertence ao usuário logado (evita IDOR).
    $stmt = getPDO()->prepare('UPDATE user_workouts SET status = "done" WHERE id = ? AND user_id = ?');
    $stmt->execute([$userWorkoutId, $_SESSION['user_id']]);
}

json_response(['ok' => true]);
