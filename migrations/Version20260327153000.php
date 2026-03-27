<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Historical placeholder for article category translation migration';
    }

    public function up(Schema $schema): void
    {
        // Kept as a no-op to preserve migration history for databases
        // that already executed this version before the migration squash.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty.
    }
}
