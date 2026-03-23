<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article import queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_import_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, original_filename VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, status VARCHAR(255) NOT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_95D35D4F6BF700BD ON article_import_queue (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_import_queue');
    }
}
