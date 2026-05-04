<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable analytics script snippets';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE analytics_script (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, page_name VARCHAR(120) NOT NULL, name VARCHAR(255) NOT NULL, scope VARCHAR(255) NOT NULL, placement VARCHAR(255) NOT NULL, script CLOB NOT NULL, enabled BOOLEAN DEFAULT 1 NOT NULL, position INTEGER DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ANALYTICS_SCRIPT_PAGE_NAME ON analytics_script (page_name)');
        $this->addSql('CREATE INDEX idx_analytics_script_render_lookup ON analytics_script (enabled, placement, scope, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE analytics_script');
    }
}
