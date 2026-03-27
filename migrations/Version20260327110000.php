<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dedicated admin article pagination setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings ADD admin_articles_per_page INTEGER NOT NULL DEFAULT 25');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings DROP COLUMN admin_articles_per_page');
    }
}
