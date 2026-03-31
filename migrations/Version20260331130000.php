<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20260331130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique name to top menu items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE top_menu_item ADD unique_name VARCHAR(255) DEFAULT NULL');

        $slugger = new AsciiSlugger();
        $rows = $this->connection->fetchAllAssociative('SELECT id, labels FROM top_menu_item ORDER BY id ASC');
        $usedUniqueNames = [];

        foreach ($rows as $row) {
            $labels = json_decode((string) ($row['labels'] ?? '{}'), true);
            $baseValue = is_array($labels) && isset($labels['pl']) && is_string($labels['pl']) && '' !== trim($labels['pl'])
                ? trim($labels['pl'])
                : $this->findFirstNonEmptyLabel($labels);

            $baseUniqueName = strtolower($slugger->slug($baseValue)->toString());
            if ('' === $baseUniqueName) {
                $baseUniqueName = 'menu-item';
            }

            $uniqueName = $baseUniqueName;
            $counter = 2;

            while (isset($usedUniqueNames[$uniqueName])) {
                $uniqueName = sprintf('%s-%d', $baseUniqueName, $counter);
                ++$counter;
            }

            $usedUniqueNames[$uniqueName] = true;

            $this->addSql(
                'UPDATE top_menu_item SET unique_name = :uniqueName WHERE id = :id',
                [
                    'uniqueName' => $uniqueName,
                    'id' => (int) $row['id'],
                ]
            );
        }

        $this->addSql('ALTER TABLE top_menu_item RENAME TO top_menu_item_old');
        $this->addSql('CREATE TABLE top_menu_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_category_id INTEGER DEFAULT NULL, article_id INTEGER DEFAULT NULL, parent_id INTEGER DEFAULT NULL, labels CLOB NOT NULL, unique_name VARCHAR(255) NOT NULL, target_type VARCHAR(255) NOT NULL, external_url VARCHAR(500) DEFAULT NULL, external_url_open_in_new_window BOOLEAN NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_5A0EF2B6A1245306 FOREIGN KEY (article_category_id) REFERENCES article_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B67294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B6727ACA70 FOREIGN KEY (parent_id) REFERENCES top_menu_item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO top_menu_item (id, article_category_id, article_id, parent_id, labels, unique_name, target_type, external_url, external_url_open_in_new_window, position, status, created_at, updated_at) SELECT id, article_category_id, article_id, parent_id, labels, unique_name, target_type, external_url, external_url_open_in_new_window, position, status, created_at, updated_at FROM top_menu_item_old');
        $this->addSql('DROP TABLE top_menu_item_old');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6A1245306 ON top_menu_item (article_category_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B67294869C ON top_menu_item (article_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6727ACA70 ON top_menu_item (parent_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A0EF2B6C38E5A14 ON top_menu_item (unique_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_5A0EF2B6C38E5A14');
        $this->addSql('ALTER TABLE top_menu_item RENAME TO top_menu_item_old');
        $this->addSql('CREATE TABLE top_menu_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_category_id INTEGER DEFAULT NULL, article_id INTEGER DEFAULT NULL, parent_id INTEGER DEFAULT NULL, labels CLOB NOT NULL, target_type VARCHAR(255) NOT NULL, external_url VARCHAR(500) DEFAULT NULL, external_url_open_in_new_window BOOLEAN NOT NULL DEFAULT 0, position INTEGER NOT NULL DEFAULT 0, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_5A0EF2B6A1245306 FOREIGN KEY (article_category_id) REFERENCES article_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B67294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A0EF2B6727ACA70 FOREIGN KEY (parent_id) REFERENCES top_menu_item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO top_menu_item (id, article_category_id, article_id, parent_id, labels, target_type, external_url, external_url_open_in_new_window, position, status, created_at, updated_at) SELECT id, article_category_id, article_id, parent_id, labels, target_type, external_url, external_url_open_in_new_window, position, status, created_at, updated_at FROM top_menu_item_old');
        $this->addSql('DROP TABLE top_menu_item_old');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6A1245306 ON top_menu_item (article_category_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B67294869C ON top_menu_item (article_id)');
        $this->addSql('CREATE INDEX IDX_5A0EF2B6727ACA70 ON top_menu_item (parent_id)');
    }

    /**
     * @param mixed $labels
     */
    private function findFirstNonEmptyLabel(mixed $labels): string
    {
        if (!is_array($labels)) {
            return '';
        }

        foreach ($labels as $value) {
            if (is_string($value) && '' !== trim($value)) {
                return trim($value);
            }
        }

        return '';
    }
}
