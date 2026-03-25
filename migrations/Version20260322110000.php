<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article export queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_export_queue (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_id INTEGER NOT NULL, status VARCHAR(255) NOT NULL, processed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_A2D1B5A77294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A2D1B5A77294869C ON article_export_queue (article_id)');
        $this->addSql('CREATE INDEX IDX_A2D1B5A76BF700BD ON article_export_queue (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_export_queue');
    }
}
