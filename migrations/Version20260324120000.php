<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add article creator and last updater columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD created_by_id INTEGER DEFAULT NULL REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE article ADD updated_by_id INTEGER DEFAULT NULL REFERENCES app_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_23A0E66DB03A8386 ON article (created_by_id)');
        $this->addSql('CREATE INDEX IDX_23A0E66D896DBBDE ON article (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_23A0E66DB03A8386');
        $this->addSql('DROP INDEX IDX_23A0E66D896DBBDE');
        $this->addSql('ALTER TABLE article DROP COLUMN created_by_id');
        $this->addSql('ALTER TABLE article DROP COLUMN updated_by_id');
    }
}
