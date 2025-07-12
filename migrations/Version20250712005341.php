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
                ADD population INT DEFAULT 0,
                ADD symptomatic INT DEFAULT 0,
                ADD positive INT DEFAULT 0
        SQL);
        $this->addSql("UPDATE surveillance_point SET population = 0 WHERE population IS NULL");
        $this->addSql("UPDATE surveillance_point SET symptomatic = 0 WHERE symptomatic IS NULL");
        $this->addSql("UPDATE surveillance_point SET positive = 0 WHERE positive IS NULL");
        $this->addSql(<<<'SQL'
            ALTER TABLE surveillance_point
                ALTER COLUMN population SET NOT NULL,
                ALTER COLUMN symptomatic SET NOT NULL,
                ALTER COLUMN positive SET NOT NULL
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
