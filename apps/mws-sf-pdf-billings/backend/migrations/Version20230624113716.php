<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230624113716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE billing_config_outlay (billing_config_id INTEGER NOT NULL, outlay_id INTEGER NOT NULL, PRIMARY KEY(billing_config_id, outlay_id), CONSTRAINT FK_32FED87890C864DD FOREIGN KEY (billing_config_id) REFERENCES billing_config (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_32FED878CD1753FF FOREIGN KEY (outlay_id) REFERENCES outlay (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_32FED87890C864DD ON billing_config_outlay (billing_config_id)');
        $this->addSql('CREATE INDEX IDX_32FED878CD1753FF ON billing_config_outlay (outlay_id)');
        $this->addSql('CREATE TABLE outlay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provider_name VARCHAR(255) NOT NULL, percent_on_business_total DOUBLE PRECISION DEFAULT NULL, provider_added_price DOUBLE PRECISION DEFAULT NULL, provider_details CLOB DEFAULT NULL, provider_taxes DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__billing_config AS SELECT client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template FROM billing_config');
        $this->addSql('DROP TABLE billing_config');
        $this->addSql('CREATE TABLE billing_config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_slug VARCHAR(255) NOT NULL, client_name VARCHAR(255) DEFAULT NULL, quotation_number VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, client_tel VARCHAR(255) DEFAULT NULL, client_siret VARCHAR(255) DEFAULT NULL, client_tva_intracom VARCHAR(255) DEFAULT NULL, client_address_l1 VARCHAR(255) DEFAULT NULL, client_address_l2 VARCHAR(255) DEFAULT NULL, client_website CLOB DEFAULT NULL, client_logo_url CLOB DEFAULT NULL, business_logo CLOB DEFAULT NULL, business_workload_hours DOUBLE PRECISION DEFAULT NULL, business_workload_details CLOB DEFAULT NULL, quotation_start_day DATETIME DEFAULT NULL, quotation_end_day DATETIME DEFAULT NULL, quotation_template VARCHAR(255) DEFAULT NULL, percent_discount DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO billing_config (client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template) SELECT client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template FROM __temp__billing_config');
        $this->addSql('DROP TABLE __temp__billing_config');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91500C2C49015800 ON billing_config (client_slug)');
        $this->addSql('CREATE INDEX client_slug_idx ON billing_config (client_slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE billing_config_outlay');
        $this->addSql('DROP TABLE outlay');
        $this->addSql('CREATE TEMPORARY TABLE __temp__billing_config AS SELECT client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template FROM billing_config');
        $this->addSql('DROP TABLE billing_config');
        $this->addSql('CREATE TABLE billing_config (client_slug VARCHAR(255) NOT NULL, client_name VARCHAR(255) DEFAULT NULL, quotation_number VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, client_tel VARCHAR(255) DEFAULT NULL, client_siret VARCHAR(255) DEFAULT NULL, client_tva_intracom VARCHAR(255) DEFAULT NULL, client_address_l1 VARCHAR(255) DEFAULT NULL, client_address_l2 VARCHAR(255) DEFAULT NULL, client_website CLOB DEFAULT NULL, client_logo_url CLOB DEFAULT NULL, business_logo CLOB DEFAULT NULL, business_workload_hours DOUBLE PRECISION DEFAULT NULL, business_workload_details CLOB DEFAULT NULL, quotation_start_day DATETIME DEFAULT NULL, quotation_end_day DATETIME DEFAULT NULL, quotation_template VARCHAR(255) DEFAULT NULL, PRIMARY KEY(client_slug))');
        $this->addSql('INSERT INTO billing_config (client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template) SELECT client_slug, client_name, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template FROM __temp__billing_config');
        $this->addSql('DROP TABLE __temp__billing_config');
    }
}
