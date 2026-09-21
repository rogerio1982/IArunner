<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Baseline: representa o schema já existente em produção no momento em que
 * o Phinx foi introduzido no projeto (ver PROMPT_REPRODUZIR_APP.md /
 * SISTEMA.md para o histórico). Não deve ser alterada — mudanças de schema
 * a partir de agora entram como novas migrations.
 *
 * Esta migration é marcada como já aplicada em produção (phinx breakpoint /
 * `phinx migrate` com o schema já existente checado antes de rodar), nunca
 * executada de fato contra o banco de produção atual. Só roda o up() de
 * verdade num banco novo/vazio (ex.: ambiente local).
 */
final class BaselineSchemaAtual extends AbstractMigration
{
    public function up(): void
    {
        $this->table('coaches')
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        $this->table('treinos_ia')
            ->addColumn('nome', 'string', ['limit' => 150])
            ->addColumn('tipo', 'enum', ['values' => ['iniciante', 'intermediario', 'avancado']])
            ->addColumn('conteudo', 'text')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->create();

        $this->table('turmas')
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255, 'default' => ''])
            ->addColumn('level', 'enum', [
                'values' => ['iniciante', 'intermediario', 'avancado'],
                'default' => 'intermediario',
            ])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        $this->table('users')
            ->addColumn('name', 'string', ['limit' => 150])
            ->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('whatsapp', 'string', ['limit' => 30])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('subscription_status', 'enum', [
                'values' => ['active', 'inactive'],
                'default' => 'inactive',
            ])
            ->addColumn('class_id', 'integer', ['null' => true, 'signed' => true])
            ->addColumn('target_distance', 'string', ['limit' => 50, 'default' => ''])
            ->addColumn('avg_pace', 'string', ['limit' => 20, 'default' => ''])
            ->addColumn('photo_url', 'string', ['limit' => 255, 'default' => ''])
            ->addColumn('trial_ends_at', 'datetime')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addForeignKey('class_id', 'turmas', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_users_class',
            ])
            ->create();

        $this->table('user_treinos_ia')
            ->addColumn('user_id', 'integer', ['signed' => true])
            ->addColumn('treino_ia_id', 'integer', ['signed' => true])
            ->addColumn('nome_personalizado', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('conteudo_personalizado', 'text', ['null' => true])
            ->addColumn('date', 'date')
            ->addColumn('status', 'enum', [
                'values' => ['pending', 'done', 'partial', 'not_done'],
                'default' => 'pending',
            ])
            ->addColumn('pse', 'integer', ['limit' => 3, 'null' => true, 'comment' => 'Percepção de esforço, 1 a 10'])
            ->addColumn('athlete_notes', 'string', ['limit' => 500, 'default' => ''])
            ->addColumn('completed_at', 'datetime', ['null' => true])
            ->addIndex(['user_id'])
            ->addIndex(['treino_ia_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('treino_ia_id', 'treinos_ia', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('user_treinos_ia')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('turmas')->drop()->save();
        $this->table('treinos_ia')->drop()->save();
        $this->table('coaches')->drop()->save();
    }
}
