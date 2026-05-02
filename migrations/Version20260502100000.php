<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Optimize article author filter lookups';
    }

    public function up(Schema $schema): void
    {
        $users = $this->connection->fetchAllAssociative('SELECT id, full_name, nickname FROM app_user');

        $this->addSql('DROP INDEX IDX_23A0E66DB03A8386');
        $this->addSql('CREATE INDEX idx_article_created_by_updated_at ON article (created_by_id, updated_at)');
        $this->addSql('ALTER TABLE app_user ADD full_name_search VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD nickname_search VARCHAR(120) DEFAULT NULL');

        foreach ($users as $user) {
            $this->addSql(
                'UPDATE app_user SET full_name_search = :fullNameSearch, nickname_search = :nicknameSearch WHERE id = :id',
                [
                    'fullNameSearch' => $this->normalizeSearchText($user['full_name'] ?? null),
                    'nicknameSearch' => $this->normalizeSearchText($user['nickname'] ?? null),
                    'id' => $user['id'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP COLUMN full_name_search');
        $this->addSql('ALTER TABLE app_user DROP COLUMN nickname_search');
        $this->addSql('DROP INDEX idx_article_created_by_updated_at');
        $this->addSql('CREATE INDEX IDX_23A0E66DB03A8386 ON article (created_by_id)');
    }

    private function normalizeSearchText(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' !== $value ? mb_strtolower($value, 'UTF-8') : null;
    }
}
