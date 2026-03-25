<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add error message column to article import queue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_import_queue ADD error_message VARCHAR(1000) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_import_queue DROP error_message');
    }
}
