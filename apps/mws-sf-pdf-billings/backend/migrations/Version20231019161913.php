<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231019161913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_contact (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(255) NOT NULL, status CLOB DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, avatar_url CLOB DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, source_detail CLOB DEFAULT NULL --(DC2Type:json)
        , source_name VARCHAR(255) DEFAULT NULL, business_url CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE mws_contact_tracking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, contact_id INTEGER NOT NULL, owner_id INTEGER NOT NULL, comment CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_D5E974E8E7A1254A FOREIGN KEY (contact_id) REFERENCES mws_contact (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D5E974E87E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D5E974E8E7A1254A ON mws_contact_tracking (contact_id)');
        $this->addSql('CREATE INDEX IDX_D5E974E87E3C61F9 ON mws_contact_tracking (owner_id)');
        $this->addSql('CREATE TABLE mws_offer_mws_contact (mws_offer_id INTEGER NOT NULL, mws_contact_id INTEGER NOT NULL, PRIMARY KEY(mws_offer_id, mws_contact_id), CONSTRAINT FK_250AEF689C40F742 FOREIGN KEY (mws_offer_id) REFERENCES mws_offer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_250AEF6891A2CBB3 FOREIGN KEY (mws_contact_id) REFERENCES mws_contact (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_250AEF689C40F742 ON mws_offer_mws_contact (mws_offer_id)');
        $this->addSql('CREATE INDEX IDX_250AEF6891A2CBB3 ON mws_offer_mws_contact (mws_contact_id)');
        $this->addSql('CREATE TABLE mws_user_mws_contact (mws_user_id INTEGER NOT NULL, mws_contact_id INTEGER NOT NULL, PRIMARY KEY(mws_user_id, mws_contact_id), CONSTRAINT FK_3EDF90F89B78E6A3 FOREIGN KEY (mws_user_id) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3EDF90F891A2CBB3 FOREIGN KEY (mws_contact_id) REFERENCES mws_contact (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3EDF90F89B78E6A3 ON mws_user_mws_contact (mws_user_id)');
        $this->addSql('CREATE INDEX IDX_3EDF90F891A2CBB3 ON mws_user_mws_contact (mws_contact_id)');
        $this->addSql('ALTER TABLE mws_offer ADD COLUMN source_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_offer ADD COLUMN source_detail CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_offer ADD COLUMN slug VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE mws_offer ADD COLUMN created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mws_offer ADD COLUMN updated_at DATETIME NOT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer_status AS SELECT id, slug, label, category_slug, bg_color, text_color, updated_at, created_at FROM mws_offer_status');
        $this->addSql('DROP TABLE mws_offer_status');
        $this->addSql('CREATE TABLE mws_offer_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_offer_status (id, slug, label, category_slug, bg_color, text_color, updated_at, created_at) SELECT id, slug, label, category_slug, bg_color, text_color, updated_at, created_at FROM __temp__mws_offer_status');
        $this->addSql('DROP TABLE __temp__mws_offer_status');
        $this->addSql('CREATE INDEX IDX_2099F135989D9B62 ON mws_offer_status (slug)');
        $this->addSql('CREATE INDEX IDX_2099F1351306E125 ON mws_offer_status (category_slug)');
        $this->addSql('ALTER TABLE mws_offer_tracking ADD COLUMN created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mws_offer_tracking ADD COLUMN updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_contact');
        $this->addSql('DROP TABLE mws_contact_tracking');
        $this->addSql('DROP TABLE mws_offer_mws_contact');
        $this->addSql('DROP TABLE mws_user_mws_contact');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer AS SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug FROM mws_offer');
        $this->addSql('DROP TABLE mws_offer');
        $this->addSql('CREATE TABLE mws_offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_username VARCHAR(255) NOT NULL, contact1 VARCHAR(255) DEFAULT NULL, contact2 VARCHAR(255) DEFAULT NULL, contact3 VARCHAR(255) DEFAULT NULL, source_url CLOB DEFAULT NULL, client_url CLOB DEFAULT NULL, current_billing_number VARCHAR(255) DEFAULT NULL, current_status_slug VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_offer (id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug) SELECT id, client_username, contact1, contact2, contact3, source_url, client_url, current_billing_number, current_status_slug FROM __temp__mws_offer');
        $this->addSql('DROP TABLE __temp__mws_offer');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer_status AS SELECT id, slug, label, category_slug, bg_color, text_color, created_at, updated_at FROM mws_offer_status');
        $this->addSql('DROP TABLE mws_offer_status');
        $this->addSql('CREATE TABLE mws_offer_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO mws_offer_status (id, slug, label, category_slug, bg_color, text_color, created_at, updated_at) SELECT id, slug, label, category_slug, bg_color, text_color, created_at, updated_at FROM __temp__mws_offer_status');
        $this->addSql('DROP TABLE __temp__mws_offer_status');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer_tracking AS SELECT id, offer_id, owner_id, offer_status_slug, comment FROM mws_offer_tracking');
        $this->addSql('DROP TABLE mws_offer_tracking');
        $this->addSql('CREATE TABLE mws_offer_tracking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, offer_id INTEGER NOT NULL, owner_id INTEGER DEFAULT NULL, offer_status_slug VARCHAR(255) DEFAULT NULL, comment CLOB DEFAULT NULL, CONSTRAINT FK_1B58F13353C674EE FOREIGN KEY (offer_id) REFERENCES mws_offer (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1B58F1337E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_offer_tracking (id, offer_id, owner_id, offer_status_slug, comment) SELECT id, offer_id, owner_id, offer_status_slug, comment FROM __temp__mws_offer_tracking');
        $this->addSql('DROP TABLE __temp__mws_offer_tracking');
        $this->addSql('CREATE INDEX IDX_1B58F13353C674EE ON mws_offer_tracking (offer_id)');
        $this->addSql('CREATE INDEX IDX_1B58F1337E3C61F9 ON mws_offer_tracking (owner_id)');
    }
}
