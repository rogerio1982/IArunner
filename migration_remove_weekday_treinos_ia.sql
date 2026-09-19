-- Migração: remove week_day de treinos_ia (o dia da semana passa a ser só
-- uma propriedade da atribuição em user_treinos_ia, via a coluna `date`,
-- não do treino em si).

ALTER TABLE treinos_ia DROP COLUMN week_day;
