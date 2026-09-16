<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mercadopago.php';

require_login();

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $initPoint = mp_criar_preferencia((int) $user['id'], $user['email']);

    if ($initPoint) {
        header('Location: ' . $initPoint);
        exit;
    }

    $error = 'Pagamento indisponível no momento. Verifique as credenciais do Mercado Pago no .env.';
}

$pageTitle = 'Assinar plano — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-md mx-auto px-6 py-16">
    <a href="/dashboard.php" class="text-lime-400 font-extrabold text-xl">AI Runner</a>

    <?php if ($error): ?>
        <div class="mt-4 bg-red-950 border border-red-700 text-red-300 rounded-lg p-3 text-sm">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 bg-slate-900 rounded-2xl p-8 shadow text-center">
        <h1 class="text-sm uppercase tracking-widest text-lime-400 font-semibold">Plano AI Runner</h1>
        <p class="mt-2 text-4xl font-extrabold">R$ 29,90 <span class="text-base font-normal text-slate-400">/ mês</span></p>

        <form method="post" class="mt-6">
            <button type="submit" class="w-full py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
                Assinar
            </button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
