<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240113195925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message AS SELECT id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, source_id, crm_logs, messages, is_draft, created_at, updated_at FROM mws_message');
        $this->addSql('DROP TABLE mws_message');
        $this->addSql('CREATE TABLE mws_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, owner_id INTEGER NOT NULL, project_id VARCHAR(255) DEFAULT NULL, dest_id VARCHAR(255) NOT NULL, monwoo_amount DOUBLE PRECISION DEFAULT NULL, project_delay_in_open_days DOUBLE PRECISION DEFAULT NULL, as_new_offer BOOLEAN DEFAULT NULL, source_id VARCHAR(255) DEFAULT NULL, crm_logs CLOB DEFAULT NULL --(DC2Type:json)
        , messages CLOB DEFAULT NULL --(DC2Type:json)
        , is_draft BOOLEAN DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_template BOOLEAN DEFAULT NULL, CONSTRAINT FK_8AAB05497E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_message (id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, source_id, crm_logs, messages, is_draft, created_at, updated_at) SELECT id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, source_id, crm_logs, messages, is_draft, created_at, updated_at FROM __temp__mws_message');
        $this->addSql('DROP TABLE __temp__mws_message');
        $this->addSql('CREATE INDEX IDX_8AAB0549953C1C61 ON mws_message (source_id)');
        $this->addSql('CREATE INDEX IDX_8AAB054979839897 ON mws_message (dest_id)');
        $this->addSql('CREATE INDEX IDX_8AAB0549166D1F9C ON mws_message (project_id)');
        $this->addSql('CREATE INDEX IDX_8AAB05497E3C61F9 ON mws_message (owner_id)');
        $this->addSql('ALTER TABLE mws_message_tchat_upload ADD COLUMN image_original_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message AS SELECT id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, is_draft, source_id, crm_logs, messages, created_at, updated_at FROM mws_message');
        $this->addSql('DROP TABLE mws_message');
        $this->addSql('CREATE TABLE mws_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, owner_id INTEGER NOT NULL, project_id VARCHAR(255) DEFAULT NULL, dest_id VARCHAR(255) NOT NULL, monwoo_amount DOUBLE PRECISION DEFAULT NULL, project_delay_in_open_days DOUBLE PRECISION DEFAULT NULL, as_new_offer BOOLEAN DEFAULT NULL, is_draft BOOLEAN NOT NULL, source_id VARCHAR(255) DEFAULT NULL, crm_logs CLOB DEFAULT NULL --(DC2Type:json)
        , messages CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_8AAB05497E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_message (id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, is_draft, source_id, crm_logs, messages, created_at, updated_at) SELECT id, owner_id, project_id, dest_id, monwoo_amount, project_delay_in_open_days, as_new_offer, is_draft, source_id, crm_logs, messages, created_at, updated_at FROM __temp__mws_message');
        $this->addSql('DROP TABLE __temp__mws_message');
        $this->addSql('CREATE INDEX IDX_8AAB05497E3C61F9 ON mws_message (owner_id)');
        $this->addSql('CREATE INDEX IDX_8AAB0549166D1F9C ON mws_message (project_id)');
        $this->addSql('CREATE INDEX IDX_8AAB054979839897 ON mws_message (dest_id)');
        $this->addSql('CREATE INDEX IDX_8AAB0549953C1C61 ON mws_message (source_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_message_tchat_upload AS SELECT id, image_name, image_size, created_at, updated_at FROM mws_message_tchat_upload');
        $this->addSql('DROP TABLE mws_message_tchat_upload');
        $this->addSql('CREATE TABLE mws_message_tchat_upload (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_message_tchat_upload (id, image_name, image_size, created_at, updated_at) SELECT id, image_name, image_size, created_at, updated_at FROM __temp__mws_message_tchat_upload');
        $this->addSql('DROP TABLE __temp__mws_message_tchat_upload');
    }
}
