<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the user who requested article imports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_import_queue ADD requested_by_id INTEGER DEFAULT NULL REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F46706A14D9B62ED ON article_import_queue (requested_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_F46706A14D9B62ED');
        $this->addSql('ALTER TABLE article_import_queue DROP COLUMN requested_by_id');
    }
}
