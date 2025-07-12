<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250712005341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stats columns to surveillance_point';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE surveillance_point
                ADD population INT NOT NULL DEFAULT 0,
                ADD symptomatic INT NOT NULL DEFAULT 0,
                ADD positive INT NOT NULL DEFAULT 0
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE surveillance_point
                DROP COLUMN population,
                DROP COLUMN symptomatic,
                DROP COLUMN positive
        SQL);
    }
}
