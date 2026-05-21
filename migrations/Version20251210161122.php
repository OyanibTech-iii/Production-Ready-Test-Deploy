<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210161122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_product (stock_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_CAEC140EDCD6110 (stock_id), INDEX IDX_CAEC140E4584665A (product_id), PRIMARY KEY(stock_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_product ADD CONSTRAINT FK_CAEC140EDCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_product ADD CONSTRAINT FK_CAEC140E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADDCD6110');
        $this->addSql('DROP INDEX IDX_D34A04ADDCD6110 ON product');
        $this->addSql('ALTER TABLE product DROP stock_id, CHANGE is_available is_available TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock_product DROP FOREIGN KEY FK_CAEC140EDCD6110');
        $this->addSql('ALTER TABLE stock_product DROP FOREIGN KEY FK_CAEC140E4584665A');
        $this->addSql('DROP TABLE stock_product');
        $this->addSql('ALTER TABLE product ADD stock_id INT DEFAULT NULL, CHANGE is_available is_available TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADDCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_D34A04ADDCD6110 ON product (stock_id)');
    }
}
