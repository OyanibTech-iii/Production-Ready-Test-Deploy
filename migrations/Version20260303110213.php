<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303110213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

public function up(Schema $schema): void
{
    // 1. Standard auto-generated table and column creation
    $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    $this->addSql('ALTER TABLE stock ADD location_rel_id INT DEFAULT NULL');

    // 2. DATA MIGRATION: Move your old string data to the new table
    // First, insert unique strings from the old column into the new Location table
    $this->addSql('INSERT INTO location (name) SELECT DISTINCT location FROM stock WHERE location IS NOT NULL AND location != ""');
    
    // Second, update the foreign key column by matching the names
    $this->addSql('UPDATE stock s SET location_rel_id = (SELECT l.id FROM location l WHERE l.name = s.location)');

    // 3. Finalize the relationship constraints
    $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566057829CF FOREIGN KEY (location_rel_id) REFERENCES location (id)');
    $this->addSql('CREATE INDEX IDX_4B36566057829CF ON stock (location_rel_id)');
}

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566057829CF');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP INDEX IDX_4B36566057829CF ON stock');
        $this->addSql('ALTER TABLE stock DROP location_rel_id');
    }
}
