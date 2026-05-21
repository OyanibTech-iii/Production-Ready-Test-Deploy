<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lesson content file (PDF) and nullable text content';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson ADD content_file VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson CHANGE content content LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lesson DROP content_file');
        $this->addSql('ALTER TABLE lesson CHANGE content content LONGTEXT NOT NULL');
    }
}
