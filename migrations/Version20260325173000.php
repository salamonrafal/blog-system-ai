<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure only one open article export queue item exists per article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE UNIQUE INDEX uniq_article_export_queue_open_article
            ON article_export_queue (article_id)
            WHERE status IN ('pending', 'processing')"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_article_export_queue_open_article');
    }
}
