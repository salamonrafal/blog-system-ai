<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add open in new window option for external top menu links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE top_menu_item ADD external_url_open_in_new_window BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE top_menu_item DROP external_url_open_in_new_window');
    }
}
