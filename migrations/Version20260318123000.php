<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add articles per page to blog settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings ADD articles_per_page INTEGER NOT NULL DEFAULT 5');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings DROP COLUMN articles_per_page');
    }
}
