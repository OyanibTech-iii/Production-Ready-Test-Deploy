<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration for Chat feature.
 */
final class Version20260423160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chatroom and chat_message tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chatroom (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chatroom_user (chatroom_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A6594DA3AD1B165C (chatroom_id), INDEX IDX_A6594DA3A76ED395 (user_id), PRIMARY KEY(chatroom_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, chatroom_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FAB3FC16AD1B165C (chatroom_id), INDEX IDX_FAB3FC16F624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chatroom_user ADD CONSTRAINT FK_A6594DA3AD1B165C FOREIGN KEY (chatroom_id) REFERENCES chatroom (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chatroom_user ADD CONSTRAINT FK_A6594DA3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16AD1B165C FOREIGN KEY (chatroom_id) REFERENCES chatroom (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chatroom_user DROP FOREIGN KEY FK_A6594DA3AD1B165C');
        $this->addSql('ALTER TABLE chatroom_user DROP FOREIGN KEY FK_A6594DA3A76ED395');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16AD1B165C');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('DROP TABLE chatroom');
        $this->addSql('DROP TABLE chatroom_user');
        $this->addSql('DROP TABLE chat_message');
    }
}
