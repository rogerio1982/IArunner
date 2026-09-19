-- Migração: treinos gerados por IA (texto livre, formatação preservada).

CREATE TABLE IF NOT EXISTS treinos_ia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
