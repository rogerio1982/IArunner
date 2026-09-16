<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = $_POST['level'] ?? '';

    $validLevels = ['iniciante', 'intermediario', 'avancado'];

    if (!in_array($level, $validLevels, true)) {
        $errors[] = 'Selecione uma opção válida.';
    }

    if (empty($errors)) {
        atribuir_plano_semanal_ao_usuario((int) $_SESSION['user_id'], $level);

        header('Location: /dashboard.php');
        exit;
    }
}

$pageTitle = 'Seu nível — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-md mx-auto px-6 py-16">
    <a href="/index.php" class="text-lime-400 font-extrabold text-xl">AI Runner</a>
    <h1 class="mt-6 text-2xl font-bold">Qual é o seu nível?</h1>
    <p class="mt-1 text-slate-400 text-sm">Vamos montar seu plano de treino da semana.</p>

    <?php if ($errors): ?>
        <div class="mt-4 bg-red-950 border border-red-700 text-red-300 rounded-lg p-3 text-sm">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm text-slate-300 mb-1">Nível</label>
            <select name="level" required class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2">
                <option value="iniciante">Iniciante</option>
                <option value="intermediario">Intermediário</option>
                <option value="avancado">Avançado</option>
            </select>
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
            Continuar
        </button>
    </form>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
