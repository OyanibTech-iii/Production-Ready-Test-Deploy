<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add password reset functionality.
 * Adds password_reset_token and password_reset_token_expires_at columns to the user table.
 */
final class Version20260411120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD password_reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN password_reset_token');
        $this->addSql('ALTER TABLE `user` DROP COLUMN password_reset_token_expires_at');
    }
}
