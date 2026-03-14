<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial article table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE article (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt VARCHAR(320) DEFAULT NULL, content CLOB NOT NULL, status VARCHAR(255) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66D989D9B62 ON article (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article');
    }
}
