<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional preference cookie domain override to blog settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings ADD preference_cookie_domain VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE blog_settings SET preference_cookie_domain = '.salamonrafal.pl' WHERE preference_cookie_domain IS NULL AND app_url = 'https://www.salamonrafal.pl'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_settings DROP COLUMN preference_cookie_domain');
    }
}
