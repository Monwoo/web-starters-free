<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231021114948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ext_log_entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "action" VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(191) NOT NULL, version INTEGER NOT NULL, data CLOB DEFAULT NULL --(DC2Type:array)
        , username VARCHAR(191) DEFAULT NULL)');
        $this->addSql('CREATE INDEX log_class_lookup_idx ON ext_log_entries (object_class)');
        $this->addSql('CREATE INDEX log_date_lookup_idx ON ext_log_entries (logged_at)');
        $this->addSql('CREATE INDEX log_user_lookup_idx ON ext_log_entries (username)');
        $this->addSql('CREATE INDEX log_version_lookup_idx ON ext_log_entries (object_id, object_class, version)');
        $this->addSql('CREATE TABLE ext_translations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(191) NOT NULL, field VARCHAR(32) NOT NULL, foreign_key VARCHAR(64) NOT NULL, content CLOB DEFAULT NULL)');
        $this->addSql('CREATE INDEX translations_lookup_idx ON ext_translations (locale, object_class, foreign_key)');
        $this->addSql('CREATE INDEX general_translations_lookup_idx ON ext_translations (object_class, foreign_key)');
        $this->addSql('CREATE UNIQUE INDEX lookup_unique_idx ON ext_translations (locale, object_class, field, foreign_key)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer AS SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at FROM mws_offer');
        $this->addSql('DROP TABLE mws_offer');
        $this->addSql('CREATE TABLE mws_offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_username VARCHAR(255) NOT NULL, contact1 VARCHAR(255) DEFAULT NULL, contact2 VARCHAR(255) DEFAULT NULL, contact3 VARCHAR(255) DEFAULT NULL, source_url CLOB DEFAULT NULL, client_url CLOB DEFAULT NULL, current_billing_number VARCHAR(255) DEFAULT NULL, current_status_slug VARCHAR(255) DEFAULT NULL, source_name VARCHAR(255) DEFAULT NULL, source_detail CLOB DEFAULT NULL --(DC2Type:json)
        , slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, title CLOB DEFAULT NULL, description CLOB DEFAULT NULL, budget VARCHAR(255) DEFAULT NULL, lead_start DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_offer (id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at) SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at FROM __temp__mws_offer');
        $this->addSql('DROP TABLE __temp__mws_offer');
        $this->addSql('CREATE INDEX IDX_221CEA63D6B836B2 ON mws_offer (client_username)');
        $this->addSql('CREATE INDEX IDX_221CEA63AB9235CF ON mws_offer (contact1)');
        $this->addSql('CREATE INDEX IDX_221CEA63329B6475 ON mws_offer (contact2)');
        $this->addSql('CREATE INDEX IDX_221CEA63459C54E3 ON mws_offer (contact3)');
        $this->addSql('CREATE INDEX IDX_221CEA63989D9B62 ON mws_offer (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ext_log_entries');
        $this->addSql('DROP TABLE ext_translations');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer AS SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at FROM mws_offer');
        $this->addSql('DROP TABLE mws_offer');
        $this->addSql('CREATE TABLE mws_offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_username VARCHAR(255) NOT NULL, contact1 VARCHAR(255) DEFAULT NULL, contact2 VARCHAR(255) DEFAULT NULL, contact3 VARCHAR(255) DEFAULT NULL, source_url CLOB DEFAULT NULL, client_url CLOB DEFAULT NULL, current_billing_number VARCHAR(255) DEFAULT NULL, current_status_slug VARCHAR(255) DEFAULT NULL, source_name VARCHAR(255) DEFAULT NULL, source_detail CLOB DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_offer (id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at) SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug, source_name, source_detail, slug, created_at, updated_at FROM __temp__mws_offer');
        $this->addSql('DROP TABLE __temp__mws_offer');
    }
}
