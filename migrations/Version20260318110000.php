<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blog settings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE blog_settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, blog_title VARCHAR(255) NOT NULL, homepage_seo_description VARCHAR(320) NOT NULL, homepage_social_image VARCHAR(500) NOT NULL, homepage_seo_keywords VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE blog_settings');
    }
}
