<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add multilingual title and description fields to article category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE article_category ADD title_pl VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE article_category ADD title_en VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE article_category ADD description_pl VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE article_category ADD description_en VARCHAR(1000) DEFAULT NULL');
        $this->addSql('UPDATE article_category SET title_pl = name, title_en = name WHERE title_pl = \'\' OR title_en = \'\'');
        $this->addSql('UPDATE article_category SET description_pl = short_description WHERE description_pl IS NULL');
        $this->addSql('UPDATE article_category SET description_en = short_description WHERE description_en IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_category DROP COLUMN title_pl');
        $this->addSql('ALTER TABLE article_category DROP COLUMN title_en');
        $this->addSql('ALTER TABLE article_category DROP COLUMN description_pl');
        $this->addSql('ALTER TABLE article_category DROP COLUMN description_en');
    }
}
