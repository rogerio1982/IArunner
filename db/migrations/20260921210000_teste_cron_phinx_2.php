<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Segunda migration de teste, para confirmar um novo ciclo completo do cron
 * do Phinx (push -> deploy -> cron aplica sozinho). Mesma lógica da
 * primeira (20260921200000): coluna nullable, sem uso pelo app.
 */
final class TesteCronPhinx2 extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('cron_test_marker_2', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'Coluna temporaria para validar o cron do Phinx (2o teste) - remover depois',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('cron_test_marker_2')
            ->update();
    }
}
