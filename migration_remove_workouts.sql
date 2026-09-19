-- Migração: remove as tabelas legadas workouts/user_workouts, não mais
-- usadas pelo fluxo do app (substituídas por treinos_ia/user_treinos_ia).
-- A tela de importação de treinos via CSV também foi removida.

DROP TABLE IF EXISTS user_workouts;
DROP TABLE IF EXISTS workouts;
