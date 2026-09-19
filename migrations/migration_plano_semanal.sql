-- Migration: substitui o modelo de treino avulso (goal/level aleatório) por plano semanal por nível.
-- Rodar apenas se o banco já existia antes desta alteração.
USE ai_runner;

DROP TABLE IF EXISTS user_workouts;
DROP TABLE IF EXISTS workouts;

CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    week_day TINYINT NOT NULL COMMENT '1=Segunda ... 7=Domingo',
    title VARCHAR(150) NOT NULL,
    type VARCHAR(100) NOT NULL,
    is_rest_day TINYINT(1) NOT NULL DEFAULT 0,
    warmup VARCHAR(255) NOT NULL DEFAULT '',
    main_workout TEXT NOT NULL,
    cooldown VARCHAR(255) NOT NULL DEFAULT '',
    distance VARCHAR(50) NOT NULL DEFAULT '',
    duration VARCHAR(50) NOT NULL DEFAULT '',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    UNIQUE KEY unique_level_day (level, week_day)
) ENGINE=InnoDB;

CREATE TABLE user_workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
