<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$imported = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if (hash_equals(env('ADMIN_PASSWORD'), $_POST['admin_password'])) {
        $_SESSION['is_admin'] = true;
    } else {
        $error = 'Senha incorreta.';
    }
}

$isAdmin = !empty($_SESSION['is_admin']);

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

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    $file = $_FILES['csv'];

    if ($file['error'] === UPLOAD_ERR_OK) {
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
        $imported = $count;
    } else {
        $error = 'Erro no upload do arquivo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar treinos — AI Runner</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<main class="max-w-md mx-auto px-6 py-16">
    <h1 class="text-2xl font-bold">Importar treinos (CSV)</h1>

    <?php if ($error): ?>
        <div class="mt-4 bg-red-950 border border-red-700 text-red-300 rounded-lg p-3 text-sm"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($imported !== null): ?>
        <div class="mt-4 bg-lime-950 border border-lime-700 text-lime-300 rounded-lg p-3 text-sm">
            <?= (int) $imported ?> treinos importados com sucesso.
        </div>
    <?php endif; ?>

    <?php if (!$isAdmin): ?>
        <form method="post" class="mt-6 space-y-4">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Senha de administrador</label>
                <input type="password" name="admin_password" required
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2">
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                Entrar
            </button>
        </form>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data" class="mt-6 space-y-4">
            <div>
                <label class="block text-sm text-slate-300 mb-1">Arquivo CSV</label>
                <p class="text-xs text-slate-500 mb-2">Colunas: Nível,Dia da Semana,Tipo de Treino,Aquecimento,Treino Principal,Volta à Calma,Tempo Total / Distância,Observações / Foco</p>
                <input type="file" name="csv" accept=".csv" required
                    class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2">
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                Importar
            </button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
