<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article export table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_export (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, article_count INTEGER NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_C9D0DF7E6BF700BD ON article_export (status)');
        $this->addSql('CREATE INDEX IDX_C9D0DF7E8CDE5729 ON article_export (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_export');
    }
}
