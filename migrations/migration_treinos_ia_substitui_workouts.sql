-- Migração: treinos_ia passa a ser a fonte do plano de treino do atleta,
-- substituindo workouts/user_workouts no fluxo em uso (as tabelas antigas
-- continuam existindo, apenas deixam de ser referenciadas pelo código).

ALTER TABLE treinos_ia
    ADD COLUMN week_day TINYINT NULL COMMENT '1=Segunda ... 7=Domingo' AFTER tipo;

CREATE TABLE IF NOT EXISTS user_treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    treino_ia_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('pending', 'done', 'partial', 'not_done') NOT NULL DEFAULT 'pending',
    pse TINYINT NULL COMMENT 'Percepção de esforço, 1 a 10',
    athlete_notes VARCHAR(500) NOT NULL DEFAULT '',
    completed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (treino_ia_id) REFERENCES treinos_ia(id) ON DELETE CASCADE
) ENGINE=InnoDB;
