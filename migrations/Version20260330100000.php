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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE top_menu_item');
    }
}
