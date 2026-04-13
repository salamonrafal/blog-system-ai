<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add article table of contents toggle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD table_of_contents_enabled BOOLEAN DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__article AS SELECT id, title, language, slug, excerpt, headline_image, headline_image_enabled, content, status, published_at, category_id, created_by_id, updated_by_id, created_at, updated_at FROM article');
        $this->addSql('DROP TABLE article');
        $this->addSql('CREATE TABLE article (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER DEFAULT NULL, created_by_id INTEGER DEFAULT NULL, updated_by_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt VARCHAR(320) DEFAULT NULL, headline_image VARCHAR(500) DEFAULT NULL, headline_image_enabled BOOLEAN DEFAULT 1 NOT NULL, content CLOB NOT NULL, status VARCHAR(255) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_23A0E66412469DE2 FOREIGN KEY (category_id) REFERENCES article_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_23A0E664B03A8386 FOREIGN KEY (created_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_23A0E664896DBBDE FOREIGN KEY (updated_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO article (id, title, language, slug, excerpt, headline_image, headline_image_enabled, content, status, published_at, category_id, created_by_id, updated_by_id, created_at, updated_at) SELECT id, title, language, slug, excerpt, headline_image, headline_image_enabled, content, status, published_at, category_id, created_by_id, updated_by_id, created_at, updated_at FROM __temp__article');
        $this->addSql('DROP TABLE __temp__article');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E664989D9B62 ON article (slug)');
        $this->addSql('CREATE INDEX IDX_23A0E66412469DE2 ON article (category_id)');
        $this->addSql('CREATE INDEX IDX_23A0E664B03A8386 ON article (created_by_id)');
        $this->addSql('CREATE INDEX IDX_23A0E664896DBBDE ON article (updated_by_id)');
    }
}
