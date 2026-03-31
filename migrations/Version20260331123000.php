<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20260331123000 extends AbstractMigration
{
    private const MAX_SLUG_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Add unique slug to article categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('PRAGMA foreign_keys = OFF');
        $this->addSql('PRAGMA legacy_alter_table = ON');
        $this->addSql('ALTER TABLE article_category ADD slug VARCHAR(255) DEFAULT NULL');

        $slugger = new AsciiSlugger();
        $rows = $this->connection->fetchAllAssociative('SELECT id, name, titles FROM article_category ORDER BY id ASC');
        $usedSlugs = [];

        foreach ($rows as $row) {
            $titles = json_decode((string) ($row['titles'] ?? '{}'), true);
            $baseValue = is_array($titles) && isset($titles['pl']) && is_string($titles['pl']) && '' !== trim($titles['pl'])
                ? trim($titles['pl'])
                : trim((string) $row['name']);

            $baseSlug = $this->truncateValue(strtolower($slugger->slug($baseValue)->toString()));
            if ('' === $baseSlug) {
                $baseSlug = 'category';
            }

            $slug = $baseSlug;
            $counter = 2;

            while (isset($usedSlugs[$slug])) {
                $suffix = sprintf('-%d', $counter);
                $slug = $this->truncateValue($baseSlug, strlen($suffix)).$suffix;
                ++$counter;
            }

            $usedSlugs[$slug] = true;

            $this->addSql(
                'UPDATE article_category SET slug = :slug WHERE id = :id',
                [
                    'slug' => $slug,
                    'id' => (int) $row['id'],
                ]
            );
        }

        $this->addSql('ALTER TABLE article_category RENAME TO article_category_old');
        $this->addSql("CREATE TABLE article_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, short_description VARCHAR(320) DEFAULT NULL, slug VARCHAR(255) NOT NULL, titles CLOB NOT NULL DEFAULT '{}', descriptions CLOB NOT NULL DEFAULT '{}', icon VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)");
        $this->addSql("INSERT INTO article_category (id, name, short_description, slug, titles, descriptions, icon, status, created_at, updated_at) SELECT id, name, short_description, slug, titles, descriptions, icon, status, created_at, updated_at FROM article_category_old");
        $this->addSql('DROP TABLE article_category_old');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EEC22795E237E06 ON article_category (name)');
        $this->addSql('CREATE INDEX IDX_8EEC22796BF700BD ON article_category (status)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EEC2279989D9B62 ON article_category (slug)');
        $this->addSql('PRAGMA legacy_alter_table = OFF');
        $this->addSql('PRAGMA foreign_keys = ON');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('PRAGMA foreign_keys = OFF');
        $this->addSql('PRAGMA legacy_alter_table = ON');
        $this->addSql('DROP INDEX UNIQ_8EEC2279989D9B62');
        $this->addSql('DROP INDEX UNIQ_8EEC22795E237E06');
        $this->addSql('DROP INDEX IDX_8EEC22796BF700BD');
        $this->addSql('ALTER TABLE article_category RENAME TO article_category_old');
        $this->addSql("CREATE TABLE article_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, short_description VARCHAR(320) DEFAULT NULL, titles CLOB NOT NULL DEFAULT '{}', descriptions CLOB NOT NULL DEFAULT '{}', icon VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)");
        $this->addSql("INSERT INTO article_category (id, name, short_description, titles, descriptions, icon, status, created_at, updated_at) SELECT id, name, short_description, titles, descriptions, icon, status, created_at, updated_at FROM article_category_old");
        $this->addSql('DROP TABLE article_category_old');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EEC22795E237E06 ON article_category (name)');
        $this->addSql('CREATE INDEX IDX_8EEC22796BF700BD ON article_category (status)');
        $this->addSql('PRAGMA legacy_alter_table = OFF');
        $this->addSql('PRAGMA foreign_keys = ON');
    }

    private function truncateValue(string $value, int $reservedSuffixLength = 0): string
    {
        $maxLength = max(1, self::MAX_SLUG_LENGTH - $reservedSuffixLength);

        return rtrim(substr($value, 0, $maxLength), '-');
    }
}
