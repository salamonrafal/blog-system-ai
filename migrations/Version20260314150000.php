<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add language to articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE article ADD language VARCHAR(2) NOT NULL DEFAULT 'pl'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP COLUMN language');
    }
}
