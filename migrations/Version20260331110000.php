<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create top menu export queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE top_menu_export_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, requested_by_id INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_15D3C1634D9B62ED FOREIGN KEY (requested_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_15D3C1634D9B62ED ON top_menu_export_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_15D3C1636BF700BD ON top_menu_export_queue (status)');
        $this->addSql(
            "CREATE UNIQUE INDEX uniq_top_menu_export_queue_open_item
            ON top_menu_export_queue ((1))
            WHERE status IN ('pending', 'processing')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE top_menu_export_queue');
    }
}
