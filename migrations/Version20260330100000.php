<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create top menu items management with nested menu support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE top_menu_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_category_id INTEGER DEFAULT NULL, article_id INTEGER DEFAULT NULL, parent_id INTEGER DEFAULT NULL, labels CLOB NOT NULL --(DC2Type:json)
, target_type VARCHAR(255) NOT NULL, external_url VARCHAR(500) DEFAULT NULL, position INTEGER NOT NULL DEFAULT 0, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_5A0EF2B6A1245306 FOREIGN KEY (article_category_id) REFERENCES article_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B67294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B6727ACA70 FOREIGN KEY (parent_id) REFERENCES top_menu_item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6A1245306 ON top_menu_item (article_category_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B67294869C ON top_menu_item (article_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6727ACA70 ON top_menu_item (parent_id)');

        $now = '2026-03-30 10:00:00';
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"Start\",\"en\":\"Home\"}', 'external_url', 'https://www.salamonrafal.pl/', 10, 'active', '$now', '$now')");
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"O mnie\",\"en\":\"About\"}', 'external_url', 'https://www.salamonrafal.pl/about.html', 20, 'active', '$now', '$now')");
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"Moje projekty\",\"en\":\"Projects\"}', 'external_url', 'https://www.salamonrafal.pl/projects.html', 30, 'active', '$now', '$now')");
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"Moje narzędzia\",\"en\":\"Tools\"}', 'external_url', 'https://www.salamonrafal.pl/tools.html', 40, 'active', '$now', '$now')");
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"Blog\",\"en\":\"Blog\"}', 'blog_home', NULL, 50, 'active', '$now', '$now')");
        $this->addSql("INSERT INTO top_menu_item (labels, target_type, external_url, position, status, created_at, updated_at) VALUES ('{\"pl\":\"Kontakt\",\"en\":\"Contact\"}', 'external_url', 'https://www.salamonrafal.pl/contact.html', 60, 'active', '$now', '$now')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE top_menu_item');
    }
}
