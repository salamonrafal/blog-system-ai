<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create media image table with custom name support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE media_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, requested_by_id INTEGER DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, custom_name VARCHAR(255) DEFAULT NULL, file_path VARCHAR(500) NOT NULL, file_size INTEGER NOT NULL, mime_type VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_F4A24791E1EED52D FOREIGN KEY (requested_by_id) REFERENCES user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F4A24791E1EED52D ON media_image (requested_by_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_media_image_custom_name_lower ON media_image (LOWER(custom_name))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE media_image');
    }
}
