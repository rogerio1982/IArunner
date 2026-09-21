<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration de teste para validar que o cron da Hostinger aplica migrations
 * pendentes sozinho, sem intervenção manual. Coluna nullable, sem efeito
 * colateral no app — será revertida assim que o teste for confirmado.
 */
final class TesteCronPhinx extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('cron_test_marker', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'Coluna temporaria para validar o cron do Phinx - remover depois',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('cron_test_marker')
            ->update();
    }
}
