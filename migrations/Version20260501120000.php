<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable meta author to blog settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE blog_settings ADD meta_author VARCHAR(255) DEFAULT 'Rafał Salamon' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings DROP COLUMN meta_author');
    }
}
