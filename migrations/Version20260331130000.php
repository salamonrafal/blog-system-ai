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
        $this->addSql("ALTER TABLE top_menu_item ADD unique_name VARCHAR(255) NOT NULL DEFAULT ''");

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

        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A0EF2B6C38E5A14 ON top_menu_item (unique_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_5A0EF2B6C38E5A14');
        $this->addSql('ALTER TABLE top_menu_item DROP COLUMN unique_name');
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
