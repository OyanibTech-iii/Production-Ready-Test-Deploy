<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activity_log table, add product.owner_id, fix product.is_available boolean';
    }

    public function up(Schema $schema): void
    {
        // activity_log
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, username VARCHAR(180) DEFAULT NULL, role VARCHAR(50) DEFAULT NULL, action VARCHAR(50) NOT NULL, target LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // product owner + is_available boolean
        $this->addSql('ALTER TABLE product ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D34A04AD7E3C61F9 ON product (owner_id)');
        $this->addSql('ALTER TABLE product CHANGE is_available is_available TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7E3C61F9');
        $this->addSql('DROP INDEX IDX_D34A04AD7E3C61F9 ON product');
        $this->addSql('ALTER TABLE product DROP owner_id');
        $this->addSql('ALTER TABLE product CHANGE is_available is_available VARCHAR(255) NOT NULL');
    }
}

