<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240118042950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message_tchat_upload AS SELECT id, created_at, updated_at, image_size FROM mws_message_tchat_upload');
        $this->addSql('DROP TABLE mws_message_tchat_upload');
        $this->addSql('CREATE TABLE mws_message_tchat_upload (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, media_size INTEGER DEFAULT NULL, media_name VARCHAR(255) DEFAULT NULL, media_original_name VARCHAR(255) DEFAULT NULL, media_mime_type VARCHAR(255) DEFAULT NULL, media_dimensions CLOB DEFAULT NULL --(DC2Type:simple_array)
        )');
        $this->addSql('INSERT INTO mws_message_tchat_upload (id, created_at, updated_at, media_size) SELECT id, created_at, updated_at, image_size FROM __temp__mws_message_tchat_upload');
        $this->addSql('DROP TABLE __temp__mws_message_tchat_upload');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message_tchat_upload AS SELECT id, created_at, updated_at, media_size FROM mws_message_tchat_upload');
        $this->addSql('DROP TABLE mws_message_tchat_upload');
        $this->addSql('CREATE TABLE mws_message_tchat_upload (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, image_size INTEGER DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, image_original_name VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_message_tchat_upload (id, created_at, updated_at, image_size) SELECT id, created_at, updated_at, media_size FROM __temp__mws_message_tchat_upload');
        $this->addSql('DROP TABLE __temp__mws_message_tchat_upload');
    }
}
