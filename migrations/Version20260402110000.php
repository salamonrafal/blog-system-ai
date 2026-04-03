<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article keywords and article keyword assignments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_keyword (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, color VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_article_keyword_language_name ON article_keyword (language, name)');
        $this->addSql('CREATE INDEX IDX_4D1A6C3782F1BAF4 ON article_keyword (language)');
        $this->addSql('CREATE INDEX IDX_4D1A6C37BF1C2D24 ON article_keyword (status)');
        $this->addSql('CREATE TABLE article_keyword_assignment (article_id INTEGER NOT NULL, article_keyword_id INTEGER NOT NULL, PRIMARY KEY(article_id, article_keyword_id), CONSTRAINT FK_37E4EABF7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_37E4EABF161D1BDB FOREIGN KEY (article_keyword_id) REFERENCES article_keyword (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_37E4EABF7294869C ON article_keyword_assignment (article_id)');
        $this->addSql('CREATE INDEX IDX_37E4EABF161D1BDB ON article_keyword_assignment (article_keyword_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_keyword_assignment');
        $this->addSql('DROP TABLE article_keyword');
    }
}
