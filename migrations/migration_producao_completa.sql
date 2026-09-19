-- ============================================================
-- Migração combinada para produção (Hostinger)
-- Aplica todas as migrações novas desta sessão, na ordem correta.
-- Cole este arquivo inteiro no phpMyAdmin (aba SQL) e execute.
-- Totalmente idempotente: pode ser executado quantas vezes for
-- preciso, sem dar erro de "duplicate column"/"already exists".
-- ============================================================

-- 1) Turmas nomeadas + feedback/PSE do atleta.
-- Cria já com o nome final "turmas" (evita o problema de criar
-- "classes" e depois renomear, que quebraria numa segunda execução
-- deste script após o rename já ter acontecido).
CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL DEFAULT '',
    level ENUM('iniciante', 'intermediario', 'avancado') NOT NULL DEFAULT 'intermediario'
) ENGINE=InnoDB;

-- Se ainda existir uma tabela "classes" antiga (script rodado antes
-- desta versão, parou no meio), migra os dados dela para "turmas" e
-- remove — sem isso, turmas ficaria vazia num reaproveitamento parcial.
SET @classes_exists = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'classes'
);
SET @sql = IF(@classes_exists > 0,
    'INSERT INTO turmas (id, name, description, level) SELECT id, name, description, level FROM classes ON DUPLICATE KEY UPDATE description = VALUES(description), level = VALUES(level)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO turmas (name, description, level) VALUES
    ('ATLETAS 21K', 'Atletas que pretendem concluir 21km no mês de Outubro', 'avancado'),
    ('ATLETAS 50K', 'Atletas que pretendem concluir 50k na ULTRA CL', 'avancado'),
    ('DESAFIO RP NOS 5K', 'Atletas que estarão participando do desafio', 'intermediario'),
    ('EVOLUÇÃO 01', 'Atletas voltando 6:30 a 6:45', 'iniciante'),
    ('EVOLUÇÃO 02', 'Pace 6:50 a 7', 'iniciante'),
    ('FITNESS 01', 'Atletas pace 5:10 a 5:30', 'intermediario'),
    ('FITNESS 02', 'Médio rendimento pace 5:40 a 6', 'intermediario'),
    ('FITNESS 03', 'Médio rendimento pace 6:10 a 6:30', 'intermediario'),
    ('GARMIN', 'Atletas que utilizam relógio Garmin', 'intermediario'),
    ('MARATONISTAS', 'Inscritos em maratona', 'avancado'),
    ('MEIA MARATONISTAS', 'Inscritos em meia maratona', 'avancado'),
    ('PERFORMANCE 01', 'Atletas pace até 3:45', 'avancado'),
    ('PERFORMANCE 02', 'Atletas pace até 4:30', 'avancado'),
    ('PERFORMANCE 03', 'Atletas pace 4:40 a 5', 'avancado'),
    ('PERSONAL', 'Alunos que fazem personal', 'intermediario')
ON DUPLICATE KEY UPDATE description = VALUES(description), level = VALUES(level);

-- 2) Colunas novas em users (idempotente: só adiciona se não existir).
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'class_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN class_id INT NULL AFTER subscription_status',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'target_distance'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN target_distance VARCHAR(50) NOT NULL DEFAULT \'\' AFTER class_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avg_pace'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN avg_pace VARCHAR(20) NOT NULL DEFAULT \'\' AFTER target_distance',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'photo_url'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN photo_url VARCHAR(255) NOT NULL DEFAULT \'\' AFTER avg_pace',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FK users.class_id -> turmas.id (idempotente: só adiciona se não existir).
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_class'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE users ADD CONSTRAINT fk_users_class FOREIGN KEY (class_id) REFERENCES turmas(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Colunas novas em user_workouts (só roda se a tabela ainda existir —
--    o bloco 8, no fim deste script, remove user_workouts/workouts).
SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_workouts'
);
SET @col_exists = IF(@table_exists > 0, (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_workouts' AND COLUMN_NAME = 'pse'
), 1);
SET @sql = IF(@table_exists > 0 AND @col_exists = 0,
    'ALTER TABLE user_workouts
        MODIFY COLUMN status ENUM(\'pending\', \'done\', \'partial\', \'not_done\') NOT NULL DEFAULT \'pending\',
        ADD COLUMN pse TINYINT NULL AFTER status,
        ADD COLUMN athlete_notes VARCHAR(500) NOT NULL DEFAULT \'\' AFTER pse,
        ADD COLUMN completed_at DATETIME NULL AFTER athlete_notes',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Treinadores (login próprio)
CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5) Treinos gerados por IA (catálogo por nível)
CREATE TABLE IF NOT EXISTS treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6) user_treinos_ia (atribuição de treino ao atleta)
CREATE TABLE IF NOT EXISTS user_treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    treino_ia_id INT NOT NULL,
    nome_personalizado VARCHAR(150) NULL,
    conteudo_personalizado TEXT NULL,
    date DATE NOT NULL,
    status ENUM('pending', 'done', 'partial', 'not_done') NOT NULL DEFAULT 'pending',
    pse TINYINT NULL COMMENT 'Percepção de esforço, 1 a 10',
    athlete_notes VARCHAR(500) NOT NULL DEFAULT '',
    completed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (treino_ia_id) REFERENCES treinos_ia(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7) Remove a tabela "classes" antiga, se ainda existir (já migrada
--    para "turmas" no bloco 1 acima).
DROP TABLE IF EXISTS classes;

-- 8) Remove tabelas legadas não mais usadas pelo fluxo do app
DROP TABLE IF EXISTS user_workouts;
DROP TABLE IF EXISTS workouts;
