<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240103183123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, owner_id INTEGER NOT NULL, project_id VARCHAR(255) DEFAULT NULL, dest_id VARCHAR(255) NOT NULL, monwoo_amount DOUBLE PRECISION DEFAULT NULL, project_delay_in_open_days DOUBLE PRECISION DEFAULT NULL, as_new_offer BOOLEAN DEFAULT NULL, source_id VARCHAR(255) DEFAULT NULL, crm_logs CLOB DEFAULT NULL --(DC2Type:json)
        , messages CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_8AAB05497E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8AAB05497E3C61F9 ON mws_message (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_message');
    }
}
