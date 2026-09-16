-- Migration: adiciona período de teste gratuito de 7 dias.
-- Rodar apenas se o banco já existia antes desta alteração.
USE ai_runner;

ALTER TABLE users
    ADD COLUMN trial_ends_at DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER subscription_status;

UPDATE users SET trial_ends_at = DATE_ADD(created_at, INTERVAL 7 DAY);

ALTER TABLE users ALTER COLUMN trial_ends_at DROP DEFAULT;
