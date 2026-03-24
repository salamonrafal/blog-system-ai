<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add profile fields to app user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD full_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD nickname VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD short_bio VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD avatar VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP COLUMN full_name');
        $this->addSql('ALTER TABLE app_user DROP COLUMN nickname');
        $this->addSql('ALTER TABLE app_user DROP COLUMN short_bio');
        $this->addSql('ALTER TABLE app_user DROP COLUMN avatar');
    }
}
