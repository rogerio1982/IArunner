<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

// O status real da assinatura é confirmado pelo webhook.php, não por este parâmetro de URL.
$status = $_GET['status'] ?? 'pending';

$messages = [
    'approved' => ['title' => 'Pagamento aprovado!', 'text' => 'Seu plano será ativado em instantes.'],
    'pending' => ['title' => 'Pagamento pendente', 'text' => 'Assim que for aprovado, seu plano será ativado automaticamente.'],
    'failure' => ['title' => 'Pagamento não concluído', 'text' => 'Tente novamente para ativar seu plano.'],
];
$message = $messages[$status] ?? $messages['pending'];

$pageTitle = 'Retorno do pagamento — AI Runner';
include __DIR__ . '/includes/header.php';
?>

<main class="max-w-md mx-auto px-6 py-16 text-center">
    <a href="/dashboard.php" class="text-lime-400 font-extrabold text-xl">AI Runner</a>

    <div class="mt-6 bg-slate-900 rounded-2xl p-8 shadow">
        <h1 class="text-xl font-bold"><?= h($message['title']) ?></h1>
        <p class="mt-2 text-slate-400 text-sm"><?= h($message['text']) ?></p>
        <a href="/dashboard.php" class="mt-6 inline-block px-6 py-3 rounded-xl bg-lime-400 text-slate-950 font-semibold hover:bg-lime-300">
            Ir para o dashboard
        </a>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
