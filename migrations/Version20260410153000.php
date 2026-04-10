<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separate read status from displayed state for user notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification ADD COLUMN read_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE user_notification SET read_at = displayed_at WHERE displayed_at IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_BA86D0B4D6AAB794 ON user_notification (read_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_BA86D0B4D6AAB794');
        $this->addSql('ALTER TABLE user_notification DROP COLUMN read_at');
    }
}
