<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional article category relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD category_id INTEGER DEFAULT NULL REFERENCES article_category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_23A0E66D12469DE2 ON article (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_23A0E66D12469DE2');
        $this->addSql('ALTER TABLE article DROP COLUMN category_id');
    }
}
