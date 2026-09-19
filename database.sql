CREATE DATABASE IF NOT EXISTS ai_runner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_runner;

-- Treinadores (login próprio, separado dos atletas).
CREATE TABLE coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Treinos gerados por IA: texto livre com formatação preservada (quebras de linha).
-- Fonte do plano de treino do atleta, cadastrado pelo treinador.
CREATE TABLE treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Turmas de atletas (ex.: PERFORMANCE 01, FITNESS 02, ATLETAS 21K...).
CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL DEFAULT '',
    level ENUM('iniciante', 'intermediario', 'avancado') NOT NULL DEFAULT 'intermediario'
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    whatsapp VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    subscription_status ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive',
    class_id INT NULL,
    target_distance VARCHAR(50) NOT NULL DEFAULT '',
    avg_pace VARCHAR(20) NOT NULL DEFAULT '',
    photo_url VARCHAR(255) NOT NULL DEFAULT '',
    trial_ends_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES turmas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Atribuição de treinos_ia ao atleta (fonte do plano de treino em uso pelo app).
-- nome_personalizado/conteudo_personalizado permitem que o treinador edite o
-- treino de um dia específico só para aquele atleta, sem alterar o catálogo
-- compartilhado treinos_ia (quando NULL, usa o treino do catálogo).
CREATE TABLE user_treinos_ia (
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
    ('PERSONAL', 'Alunos que fazem personal', 'intermediario');

-- Treinos por nível são cadastrados pelo treinador via dashboard de coaching
-- (aba "Treino com IA" → tabela treinos_ia), não há mais importação por CSV.
