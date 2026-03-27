<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article category table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, short_description VARCHAR(320) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EEC22795E237E06 ON article_category (name)');
        $this->addSql('CREATE INDEX IDX_8EEC22796BF700BD ON article_category (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_category');
    }
}
