<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional headline image to articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD headline_image VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP COLUMN headline_image');
    }
}
