<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the user who requested article exports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_export_queue ADD requested_by_id INTEGER DEFAULT NULL REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE article_export ADD requested_by_id INTEGER DEFAULT NULL REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A2D1B5A74D9B62ED ON article_export_queue (requested_by_id)');
        $this->addSql('CREATE INDEX IDX_C9D0DF7E4D9B62ED ON article_export (requested_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_A2D1B5A74D9B62ED');
        $this->addSql('DROP INDEX IDX_C9D0DF7E4D9B62ED');
        $this->addSql('ALTER TABLE article_export_queue DROP COLUMN requested_by_id');
        $this->addSql('ALTER TABLE article_export DROP COLUMN requested_by_id');
    }
}
