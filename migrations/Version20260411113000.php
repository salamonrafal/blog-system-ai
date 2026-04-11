<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article keyword export and import queues';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_keyword_export_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, requested_by_id INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_CE95B0C94D9B62ED FOREIGN KEY (requested_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CE95B0C94D9B62ED ON article_keyword_export_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_CE95B0C96BF700BD ON article_keyword_export_queue (status)');
        $this->addSql("CREATE UNIQUE INDEX uniq_article_keyword_export_queue_open_item\n            ON article_keyword_export_queue ((1))\n            WHERE status IN ('pending', 'processing')");
        $this->addSql('CREATE TABLE article_keyword_import_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, requested_by_id INTEGER DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, status VARCHAR(255) NOT NULL, error_message VARCHAR(1000) DEFAULT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_40B4BE764D9B62ED FOREIGN KEY (requested_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_40B4BE764D9B62ED ON article_keyword_import_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_40B4BE766BF700BD ON article_keyword_import_queue (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_keyword_import_queue');
        $this->addSql('DROP TABLE article_keyword_export_queue');
    }
}
