<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'unauthorized'], 401);
}

$input = json_body();
$userWorkoutId = (int) ($input['user_workout_id'] ?? 0);
$status = $input['status'] ?? 'done';
$pse = $input['pse'] ?? null;
$athleteNotes = trim((string) ($input['athlete_notes'] ?? ''));

$validStatuses = ['done', 'partial', 'not_done'];
if (!in_array($status, $validStatuses, true)) {
    json_response(['error' => 'Status inválido.'], 422);
}

if ($pse !== null) {
    $pse = (int) $pse;
    if ($pse < 1 || $pse > 10) {
        json_response(['error' => 'PSE deve estar entre 1 e 10.'], 422);
    }
}

if ($userWorkoutId > 0) {
    // Garante que o registro pertence ao usuário logado (evita IDOR).
    $stmt = getPDO()->prepare('
        UPDATE user_treinos_ia
        SET status = ?, pse = ?, athlete_notes = ?, completed_at = NOW()
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$status, $pse, $athleteNotes, $userWorkoutId, $_SESSION['user_id']]);
}

json_response(['ok' => true]);
