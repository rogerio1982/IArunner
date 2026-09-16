<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = getPDO()->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        header('Location: /dashboard.php');
        exit;
    }

    $errors[] = 'E-mail ou senha inválidos.';
}

$pageTitle = 'Entrar — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-md mx-auto px-6 py-16">
    <a href="/index.php" class="text-lime-400 font-extrabold text-xl">AI Runner</a>
    <h1 class="mt-6 text-2xl font-bold">Entrar</h1>

    <?php if ($errors): ?>
        <div class="mt-4 bg-red-950 border border-red-700 text-red-300 rounded-lg p-3 text-sm">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm text-slate-300 mb-1">E-mail</label>
            <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <div>
            <label class="block text-sm text-slate-300 mb-1">Senha</label>
            <input type="password" name="password" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
            Entrar
        </button>
    </form>

    <p class="mt-4 text-sm text-slate-400">Não tem conta? <a href="/cadastro.php" class="text-lime-400">Criar conta</a></p>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
