-- Migração: turmas nomeadas + feedback/PSE do atleta nos treinos.
-- Aplicar após database.sql e as migrações anteriores.

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
