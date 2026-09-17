<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

$isAdmin = !empty($_SESSION['is_admin']);

if (!$isAdmin && isset($_POST['admin_password'])) {
    if (hash_equals(env('ADMIN_PASSWORD'), $_POST['admin_password'])) {
        $_SESSION['is_admin'] = true;
        $isAdmin = true;
    } else {
        json_response(['error' => 'Senha incorreta.'], 401);
    }
}

if (!$isAdmin) {
    json_response(['error' => 'unauthorized'], 401);
}

if (!isset($_FILES['csv'])) {
    json_response(['ok' => true, 'isAdmin' => true]);
}

const NIVEL_MAP = [
    'iniciante' => 'iniciante',
    'intermediário' => 'intermediario',
    'intermediario' => 'intermediario',
    'avançado' => 'avancado',
    'avancado' => 'avancado',
];

const DIA_SEMANA_MAP = [
    'segunda-feira' => 1,
    'terça-feira' => 2,
    'terca-feira' => 2,
    'quarta-feira' => 3,
    'quinta-feira' => 4,
    'sexta-feira' => 5,
    'sábado' => 6,
    'sabado' => 6,
    'domingo' => 7,
];

$file = $_FILES['csv'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Erro no upload do arquivo.'], 400);
}

$handle = fopen($file['tmp_name'], 'r');
fgetcsv($handle); // cabeçalho
$count = 0;

$pdo = getPDO();
$stmt = $pdo->prepare('
    INSERT INTO workouts (level, week_day, title, type, is_rest_day, warmup, main_workout, cooldown, distance, duration, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        title = VALUES(title), type = VALUES(type), is_rest_day = VALUES(is_rest_day),
        warmup = VALUES(warmup), main_workout = VALUES(main_workout), cooldown = VALUES(cooldown),
        distance = VALUES(distance), duration = VALUES(duration), notes = VALUES(notes)
');

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 8) {
        continue;
    }

    [$nivelRaw, $diaRaw, $tipo, $aquecimento, $treino, $volta, $tempo, $obs] = array_map('trim', array_slice($row, 0, 8));

    $level = NIVEL_MAP[mb_strtolower($nivelRaw)] ?? null;
    $weekDay = DIA_SEMANA_MAP[mb_strtolower($diaRaw)] ?? null;

    if (!$level || !$weekDay) {
        continue;
    }

    $isRestDay = ($aquecimento === '-' && $treino !== '' && str_contains(mb_strtolower($treino), 'descanso'));

    $stmt->execute([
        $level,
        $weekDay,
        $tipo,
        $tipo,
        $isRestDay ? 1 : 0,
        $aquecimento === '-' ? '' : $aquecimento,
        $treino,
        $volta === '-' ? '' : $volta,
        $tempo === '-' ? '' : $tempo,
        '',
        $obs,
    ]);
    $count++;
}

fclose($handle);

json_response(['imported' => $count]);
