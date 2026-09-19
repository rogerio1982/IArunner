<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin_auth.php';

$pdo = getPDO();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'logout') {
    unset($_SESSION['coach_id']);
    json_response(['ok' => true]);
}

require_admin();

// Visão geral: turmas com contagem de atletas + lista de atletas por turma.
if ($action === 'overview') {
    $classes = $pdo->query('SELECT id, name, description, level FROM turmas ORDER BY name ASC')->fetchAll();

    $stmt = $pdo->query('
        SELECT u.id, u.name, u.email, u.whatsapp, u.class_id, u.subscription_status, u.trial_ends_at,
               u.target_distance, u.avg_pace, u.created_at, c.name AS class_name
        FROM users u
        LEFT JOIN turmas c ON c.id = u.class_id
        ORDER BY u.name ASC
    ');
    $users = $stmt->fetchAll();

    $usersByClass = [];
    $semTurma = [];
    foreach ($users as &$u) {
        $u['hasAccess'] = usuario_tem_acesso($u);
        if ($u['class_id']) {
            $usersByClass[$u['class_id']][] = $u;
        } else {
            $semTurma[] = $u;
        }
    }
    unset($u);

    foreach ($classes as &$c) {
        $c['athletes'] = $usersByClass[$c['id']] ?? [];
    }
    unset($c);

    $recentAthletes = $users;
    usort($recentAthletes, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
    $recentAthletes = array_slice($recentAthletes, 0, 10);

    json_response(['classes' => $classes, 'semTurma' => $semTurma, 'recentAthletes' => $recentAthletes, 'allAthletes' => $users]);
}

// Detalhe de um atleta: dados + treinos da semana atual com feedback.
if ($action === 'athlete') {
    $userId = (int) ($_GET['user_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT u.*, c.name AS class_name FROM users u LEFT JOIN turmas c ON c.id = u.class_id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['error' => 'Atleta não encontrado.'], 404);
    }
    unset($user['password']);

    $stmt = $pdo->prepare('
        SELECT
            uti.id AS user_workout_id, uti.date, uti.status, uti.pse, uti.athlete_notes, uti.completed_at,
            uti.nome_personalizado, uti.conteudo_personalizado,
            t.id, t.tipo,
            COALESCE(uti.nome_personalizado, t.nome) AS nome,
            COALESCE(uti.conteudo_personalizado, t.conteudo) AS conteudo
        FROM user_treinos_ia uti
        JOIN treinos_ia t ON t.id = uti.treino_ia_id
        WHERE uti.user_id = ?
        ORDER BY uti.date DESC
        LIMIT 30
    ');
    $stmt->execute([$userId]);
    $workouts = $stmt->fetchAll();

    json_response(['user' => $user, 'workouts' => $workouts]);
}

// Personaliza o treino de um dia específico do atleta, sem alterar o
// catálogo treinos_ia compartilhado. Enviar nome/conteudo vazios remove a
// personalização e volta a mostrar o treino original do catálogo.
if ($action === 'update_user_treino') {
    $input = json_body();
    $userWorkoutId = (int) ($input['user_workout_id'] ?? 0);
    $nome = trim((string) ($input['nome'] ?? ''));
    $conteudo = trim((string) ($input['conteudo'] ?? ''));

    $nomePersonalizado = $nome !== '' ? $nome : null;
    $conteudoPersonalizado = $conteudo !== '' ? $conteudo : null;

    $pdo->prepare('UPDATE user_treinos_ia SET nome_personalizado = ?, conteudo_personalizado = ? WHERE id = ?')
        ->execute([$nomePersonalizado, $conteudoPersonalizado, $userWorkoutId]);

    json_response(['ok' => true]);
}

// Move um atleta para outra turma (ou remove, com class_id vazio).
if ($action === 'move_athlete') {
    $input = json_body();
    $userId = (int) ($input['user_id'] ?? 0);
    $classId = $input['class_id'] ?? null;
    $classId = $classId ? (int) $classId : null;

    if ($classId !== null) {
        $stmt = $pdo->prepare('SELECT level FROM turmas WHERE id = ?');
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        if (!$class) {
            json_response(['error' => 'Turma inválida.'], 422);
        }
    }

    $pdo->prepare('UPDATE users SET class_id = ? WHERE id = ?')->execute([$classId, $userId]);
    json_response(['ok' => true]);
}

// CRUD de turmas.
if ($action === 'save_class') {
    $input = json_body();
    $id = (int) ($input['id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $level = $input['level'] ?? '';

    if ($name === '' || !in_array($level, ['iniciante', 'intermediario', 'avancado'], true)) {
        json_response(['error' => 'Preencha nome e nível válidos.'], 422);
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE turmas SET name = ?, description = ?, level = ? WHERE id = ?')
            ->execute([$name, $description, $level, $id]);
    } else {
        $pdo->prepare('INSERT INTO turmas (name, description, level) VALUES (?, ?, ?)')
            ->execute([$name, $description, $level]);
        $id = (int) $pdo->lastInsertId();
    }

    json_response(['ok' => true, 'id' => $id]);
}

if ($action === 'delete_class') {
    $input = json_body();
    $id = (int) ($input['id'] ?? 0);
    $pdo->prepare('DELETE FROM turmas WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

// CRUD de treinos IA (texto livre, formatação preservada).
if ($action === 'treinos_ia') {
    $tipo = $_GET['tipo'] ?? '';
    if ($tipo !== '' && !in_array($tipo, ['iniciante', 'intermediario', 'avancado'], true)) {
        json_response(['error' => 'Tipo inválido.'], 422);
    }

    if ($tipo !== '') {
        $stmt = $pdo->prepare('SELECT * FROM treinos_ia WHERE tipo = ? ORDER BY created_at DESC');
        $stmt->execute([$tipo]);
    } else {
        $stmt = $pdo->query('SELECT * FROM treinos_ia ORDER BY created_at DESC');
    }

    json_response(['treinos' => $stmt->fetchAll()]);
}

if ($action === 'treino_ia') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM treinos_ia WHERE id = ?');
    $stmt->execute([$id]);
    $treino = $stmt->fetch();

    if (!$treino) {
        json_response(['error' => 'Treino não encontrado.'], 404);
    }

    json_response(['treino' => $treino]);
}

if ($action === 'save_treino_ia') {
    $input = json_body();
    $id = (int) ($input['id'] ?? 0);
    $nome = trim((string) ($input['nome'] ?? ''));
    $tipo = $input['tipo'] ?? '';
    $conteudo = (string) ($input['conteudo'] ?? '');

    if ($nome === '' || !in_array($tipo, ['iniciante', 'intermediario', 'avancado'], true) || trim($conteudo) === '') {
        json_response(['error' => 'Preencha nome, tipo e conteúdo.'], 422);
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE treinos_ia SET nome = ?, tipo = ?, conteudo = ? WHERE id = ?')
            ->execute([$nome, $tipo, $conteudo, $id]);
    } else {
        $pdo->prepare('INSERT INTO treinos_ia (nome, tipo, conteudo) VALUES (?, ?, ?)')
            ->execute([$nome, $tipo, $conteudo]);
        $id = (int) $pdo->lastInsertId();
    }

    json_response(['ok' => true, 'id' => $id]);
}

if ($action === 'delete_treino_ia') {
    $input = json_body();
    $id = (int) ($input['id'] ?? 0);
    $pdo->prepare('DELETE FROM treinos_ia WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

json_response(['error' => 'Ação inválida.'], 400);
