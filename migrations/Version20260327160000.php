<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace language-specific category fields with generic translation maps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE article_category ADD titles CLOB NOT NULL DEFAULT '{}'");
        $this->addSql("ALTER TABLE article_category ADD descriptions CLOB NOT NULL DEFAULT '{}'");

        /** @var list<array{id:int, title_pl:string, title_en:string, description_pl:?string, description_en:?string}> $rows */
        $rows = $this->connection->fetchAllAssociative('SELECT id, title_pl, title_en, description_pl, description_en FROM article_category');

        foreach ($rows as $row) {
            $titles = [
                'pl' => trim((string) $row['title_pl']),
                'en' => trim((string) $row['title_en']),
            ];

            $descriptions = array_filter([
                'pl' => null !== $row['description_pl'] ? trim((string) $row['description_pl']) : null,
                'en' => null !== $row['description_en'] ? trim((string) $row['description_en']) : null,
            ], static fn (mixed $value): bool => is_string($value) && '' !== $value);

            $this->addSql(
                'UPDATE article_category SET titles = :titles, descriptions = :descriptions WHERE id = :id',
                [
                    'titles' => json_encode($titles, JSON_THROW_ON_ERROR),
                    'descriptions' => json_encode($descriptions, JSON_THROW_ON_ERROR),
                    'id' => $row['id'],
                ],
            );
        }

        $this->addSql('ALTER TABLE article_category DROP COLUMN title_pl');
        $this->addSql('ALTER TABLE article_category DROP COLUMN title_en');
        $this->addSql('ALTER TABLE article_category DROP COLUMN description_pl');
        $this->addSql('ALTER TABLE article_category DROP COLUMN description_en');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE article_category ADD title_pl VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE article_category ADD title_en VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE article_category ADD description_pl VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE article_category ADD description_en VARCHAR(1000) DEFAULT NULL');

        /** @var list<array{id:int, titles:string, descriptions:string}> $rows */
        $rows = $this->connection->fetchAllAssociative('SELECT id, titles, descriptions FROM article_category');

        foreach ($rows as $row) {
            /** @var array<string, mixed> $titles */
            $titles = json_decode($row['titles'], true, 512, JSON_THROW_ON_ERROR);
            /** @var array<string, mixed> $descriptions */
            $descriptions = json_decode($row['descriptions'], true, 512, JSON_THROW_ON_ERROR);

            $this->addSql(
                'UPDATE article_category SET title_pl = :title_pl, title_en = :title_en, description_pl = :description_pl, description_en = :description_en WHERE id = :id',
                [
                    'title_pl' => isset($titles['pl']) && is_string($titles['pl']) ? $titles['pl'] : '',
                    'title_en' => isset($titles['en']) && is_string($titles['en']) ? $titles['en'] : '',
                    'description_pl' => isset($descriptions['pl']) && is_string($descriptions['pl']) ? $descriptions['pl'] : null,
                    'description_en' => isset($descriptions['en']) && is_string($descriptions['en']) ? $descriptions['en'] : null,
                    'id' => $row['id'],
                ],
            );
        }

        $this->addSql('ALTER TABLE article_category DROP COLUMN titles');
        $this->addSql('ALTER TABLE article_category DROP COLUMN descriptions');
    }
}
