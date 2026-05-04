<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default admin article ordering index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_article_admin_updated_order ON article (updated_at, created_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_article_admin_updated_order');
    }
}
