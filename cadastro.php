<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $whatsapp === '') {
        $errors[] = 'Preencha todos os campos.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }

    if (empty($errors)) {
        $pdo = getPDO();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Este e-mail já está cadastrado.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 7 dias de teste grátis a partir do cadastro.
        $trialEndsAt = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('INSERT INTO users (name, email, whatsapp, password, trial_ends_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $whatsapp, $hash, $trialEndsAt]);

        $_SESSION['user_id'] = (int) $pdo->lastInsertId();

        header('Location: /onboarding.php');
        exit;
    }
}

$pageTitle = 'Criar conta — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-md mx-auto px-6 py-16">
    <a href="/index.php" class="text-lime-400 font-extrabold text-xl">AI Runner</a>
    <h1 class="mt-6 text-2xl font-bold">Criar conta</h1>

    <?php if ($errors): ?>
        <div class="mt-4 bg-red-950 border border-red-700 text-red-300 rounded-lg p-3 text-sm">
            <?php foreach ($errors as $error): ?>
                <p><?= h($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm text-slate-300 mb-1">Nome</label>
            <input type="text" name="name" value="<?= h($_POST['name'] ?? '') ?>" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <div>
            <label class="block text-sm text-slate-300 mb-1">E-mail</label>
            <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <div>
            <label class="block text-sm text-slate-300 mb-1">WhatsApp</label>
            <input type="text" name="whatsapp" value="<?= h($_POST['whatsapp'] ?? '') ?>" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <div>
            <label class="block text-sm text-slate-300 mb-1">Senha</label>
            <input type="password" name="password" required
                class="w-full rounded-lg bg-slate-900 border border-slate-700 px-4 py-2 focus:outline-none focus:border-lime-400">
        </div>
        <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
            Criar conta
        </button>
    </form>

    <p class="mt-4 text-sm text-slate-400">Já tem conta? <a href="/login.php" class="text-lime-400">Entrar</a></p>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
