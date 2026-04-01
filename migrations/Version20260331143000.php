<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create top menu import queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE top_menu_import_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, requested_by_id INTEGER DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, status VARCHAR(255) NOT NULL, error_message VARCHAR(1000) DEFAULT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_4F6E796D4D9B62ED FOREIGN KEY (requested_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4F6E796D4D9B62ED ON top_menu_import_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_4F6E796D6BF700BD ON top_menu_import_queue (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE top_menu_import_queue');
    }
}
