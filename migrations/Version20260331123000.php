<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20260331123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique slug to article categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE article_category ADD slug VARCHAR(255) NOT NULL DEFAULT ''");

        $slugger = new AsciiSlugger();
        $rows = $this->connection->fetchAllAssociative('SELECT id, name, titles FROM article_category ORDER BY id ASC');
        $usedSlugs = [];

        foreach ($rows as $row) {
            $titles = json_decode((string) ($row['titles'] ?? '{}'), true);
            $baseValue = is_array($titles) && isset($titles['pl']) && is_string($titles['pl']) && '' !== trim($titles['pl'])
                ? trim($titles['pl'])
                : trim((string) $row['name']);

            $baseSlug = strtolower($slugger->slug($baseValue)->toString());
            if ('' === $baseSlug) {
                $baseSlug = 'category';
            }

            $slug = $baseSlug;
            $counter = 2;

            while (isset($usedSlugs[$slug])) {
                $slug = sprintf('%s-%d', $baseSlug, $counter);
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

        $this->addSql('CREATE UNIQUE INDEX UNIQ_8EEC2279989D9B62 ON article_category (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8EEC2279989D9B62');
        $this->addSql('ALTER TABLE article_category DROP COLUMN slug');
    }
}
