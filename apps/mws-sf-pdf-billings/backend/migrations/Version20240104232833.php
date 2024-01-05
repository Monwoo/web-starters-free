<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240104232833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message_tchat_upload AS SELECT id, created_at, updated_at, image_name, image_size FROM mws_message_tchat_upload');
        $this->addSql('DROP TABLE mws_message_tchat_upload');
        $this->addSql('CREATE TABLE mws_message_tchat_upload (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_message_tchat_upload (id, created_at, updated_at, image_name, image_size) SELECT id, created_at, updated_at, image_name, image_size FROM __temp__mws_message_tchat_upload');
        $this->addSql('DROP TABLE __temp__mws_message_tchat_upload');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mws_message_tchat_upload ADD COLUMN image_original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_message_tchat_upload ADD COLUMN image_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_message_tchat_upload ADD COLUMN image_dimensions CLOB DEFAULT NULL');
    }
}
