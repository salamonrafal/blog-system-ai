<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create category export queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE category_export_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER NOT NULL, requested_by_id INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_59F7CF1912469DE2 FOREIGN KEY (category_id) REFERENCES article_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_59F7CF194D9B62ED FOREIGN KEY (requested_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_59F7CF1912469DE2 ON category_export_queue (category_id)');
        $this->addSql('CREATE INDEX IDX_59F7CF194D9B62ED ON category_export_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_59F7CF196BF700BD ON category_export_queue (status)');
        $this->addSql(
            "CREATE UNIQUE INDEX uniq_category_export_queue_open_category
            ON category_export_queue (category_id)
            WHERE status IN ('pending', 'processing')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE category_export_queue');
    }
}
