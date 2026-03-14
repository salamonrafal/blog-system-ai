<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create application users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL)");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3D1FF2E6E7927C74 ON app_user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
    }
}
