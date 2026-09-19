-- Migração: permite personalizar o treino de um dia específico no histórico
-- de um atleta, sem alterar o treino_ia original do catálogo compartilhado.

ALTER TABLE user_treinos_ia
    ADD COLUMN nome_personalizado VARCHAR(150) NULL AFTER treino_ia_id,
    ADD COLUMN conteudo_personalizado TEXT NULL AFTER nome_personalizado;
