-- ============================================================
-- Migração combinada para produção (Hostinger)
-- Aplica todas as migrações novas desta sessão, na ordem correta.
-- Cole este arquivo inteiro no phpMyAdmin (aba SQL) e execute uma vez.
-- Idempotente onde possível (IF NOT EXISTS / IF EXISTS).
-- ============================================================

-- 1) Turmas nomeadas + feedback/PSE do atleta
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL DEFAULT '',
    level ENUM('iniciante', 'intermediario', 'avancado') NOT NULL DEFAULT 'intermediario'
) ENGINE=InnoDB;

INSERT INTO classes (name, description, level) VALUES
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

-- As colunas/tabelas abaixo só devem ser adicionadas se ainda não existirem.
-- Se o ALTER der erro "Duplicate column name" ou "already exists", é sinal
-- de que essa parte específica já foi aplicada antes — pode ignorar esse
-- erro pontual e seguir pro próximo bloco.

ALTER TABLE users
    ADD COLUMN class_id INT NULL AFTER subscription_status,
    ADD COLUMN target_distance VARCHAR(50) NOT NULL DEFAULT '' AFTER class_id,
    ADD COLUMN avg_pace VARCHAR(20) NOT NULL DEFAULT '' AFTER target_distance,
    ADD COLUMN photo_url VARCHAR(255) NOT NULL DEFAULT '' AFTER avg_pace,
    ADD CONSTRAINT fk_users_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL;

ALTER TABLE user_workouts
    MODIFY COLUMN status ENUM('pending', 'done', 'partial', 'not_done') NOT NULL DEFAULT 'pending',
    ADD COLUMN pse TINYINT NULL AFTER status,
    ADD COLUMN athlete_notes VARCHAR(500) NOT NULL DEFAULT '' AFTER pse,
    ADD COLUMN completed_at DATETIME NULL AFTER athlete_notes;

-- 2) Treinadores (login próprio)
CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3) Treinos gerados por IA (catálogo por nível)
CREATE TABLE IF NOT EXISTS treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4) user_treinos_ia (atribuição de treino ao atleta) — sem week_day em
--    treinos_ia, já que ele foi removido na migração 5 abaixo (aplicamos
--    a tabela já no formato final, sem precisar criar e depois remover).
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

-- 7) Renomeia classes para turmas (fazer por último, depois que tudo que
--    referenciava "classes" já foi criado/testado)
RENAME TABLE classes TO turmas;

-- 8) Remove tabelas legadas não mais usadas pelo fluxo do app
DROP TABLE IF EXISTS user_workouts;
DROP TABLE IF EXISTS workouts;
