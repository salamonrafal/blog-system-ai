<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create per-user notifications for background import and export results';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, recipient_id INTEGER NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, displayed_at DATETIME DEFAULT NULL, CONSTRAINT FK_BA86D0B4E92F8F78 FOREIGN KEY (recipient_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BA86D0B4E92F8F78 ON user_notification (recipient_id)');
        $this->addSql('CREATE INDEX IDX_BA86D0B49A555F3C ON user_notification (displayed_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_notification');
    }
}
