<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230620075302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_tel VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_siret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_tva_intracom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_address_l1 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_address_l2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_website CLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE billing_config ADD COLUMN client_logo_url CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__billing_config AS SELECT id, client_name, quotation_number, client_slug FROM billing_config');
        $this->addSql('DROP TABLE billing_config');
        $this->addSql('CREATE TABLE billing_config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_name VARCHAR(255) DEFAULT NULL, quotation_number VARCHAR(255) DEFAULT NULL, client_slug VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO billing_config (id, client_name, quotation_number, client_slug) SELECT id, client_name, quotation_number, client_slug FROM __temp__billing_config');
        $this->addSql('DROP TABLE __temp__billing_config');
    }
}
