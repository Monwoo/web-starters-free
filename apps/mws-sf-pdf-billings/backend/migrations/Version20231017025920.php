<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231017025920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_username VARCHAR(255) NOT NULL, contact1 VARCHAR(255) DEFAULT NULL, contact2 VARCHAR(255) DEFAULT NULL, contact3 VARCHAR(255) DEFAULT NULL, source_url CLOB DEFAULT NULL, client_url CLOB DEFAULT NULL, current_billing_number VARCHAR(255) DEFAULT NULL, current_status_slug VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE mws_offer_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE mws_offer_tracking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, offer_id INTEGER NOT NULL, owner_id INTEGER DEFAULT NULL, offer_status_slug VARCHAR(255) DEFAULT NULL, comment CLOB DEFAULT NULL, CONSTRAINT FK_1B58F13353C674EE FOREIGN KEY (offer_id) REFERENCES mws_offer (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1B58F1337E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1B58F13353C674EE ON mws_offer_tracking (offer_id)');
        $this->addSql('CREATE INDEX IDX_1B58F1337E3C61F9 ON mws_offer_tracking (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_offer');
        $this->addSql('DROP TABLE mws_offer_status');
        $this->addSql('DROP TABLE mws_offer_tracking');
    }
}
