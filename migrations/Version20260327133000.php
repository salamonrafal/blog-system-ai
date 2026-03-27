<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable application URL to blog settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE blog_settings ADD app_url VARCHAR(255) NOT NULL DEFAULT 'https://www.salamonrafal.pl'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings DROP COLUMN app_url');
    }
}
