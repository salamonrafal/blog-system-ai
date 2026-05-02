<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite article author update index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_article_created_by_updated_at ON article (created_by_id, updated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_article_created_by_updated_at');
    }
}
