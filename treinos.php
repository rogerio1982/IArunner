<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userWorkoutId = (int) ($_POST['user_workout_id'] ?? 0);

    if ($userWorkoutId > 0) {
        // Garante que o registro pertence ao usuário logado (evita IDOR).
        $stmt = getPDO()->prepare('UPDATE user_workouts SET status = "done" WHERE id = ? AND user_id = ?');
        $stmt->execute([$userWorkoutId, $_SESSION['user_id']]);
    }
}

header('Location: /dashboard.php');
exit;
