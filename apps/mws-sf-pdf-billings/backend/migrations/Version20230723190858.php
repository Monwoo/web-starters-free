<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230723190858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billing_config ADD COLUMN quotation_source_number VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__billing_config AS SELECT id, client_slug, client_name, document_type, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template, percent_discount, margin_before_start_item, margin_before_end_item, hide_default_outlays_on_empty_outlays FROM billing_config');
        $this->addSql('DROP TABLE billing_config');
        $this->addSql('CREATE TABLE billing_config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_slug VARCHAR(255) NOT NULL, client_name VARCHAR(255) DEFAULT NULL, document_type VARCHAR(255) DEFAULT NULL, quotation_number VARCHAR(255) DEFAULT NULL, client_email VARCHAR(255) DEFAULT NULL, client_tel VARCHAR(255) DEFAULT NULL, client_siret VARCHAR(255) DEFAULT NULL, client_tva_intracom VARCHAR(255) DEFAULT NULL, client_address_l1 VARCHAR(255) DEFAULT NULL, client_address_l2 VARCHAR(255) DEFAULT NULL, client_website CLOB DEFAULT NULL, client_logo_url CLOB DEFAULT NULL, business_logo CLOB DEFAULT NULL, business_workload_hours DOUBLE PRECISION DEFAULT NULL, business_workload_details CLOB DEFAULT NULL, quotation_start_day DATETIME DEFAULT NULL, quotation_end_day DATETIME DEFAULT NULL, quotation_template VARCHAR(255) DEFAULT NULL, percent_discount DOUBLE PRECISION DEFAULT NULL, margin_before_start_item VARCHAR(255) DEFAULT NULL, margin_before_end_item VARCHAR(255) DEFAULT NULL, hide_default_outlays_on_empty_outlays BOOLEAN DEFAULT NULL)');
        $this->addSql('INSERT INTO billing_config (id, client_slug, client_name, document_type, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template, percent_discount, margin_before_start_item, margin_before_end_item, hide_default_outlays_on_empty_outlays) SELECT id, client_slug, client_name, document_type, quotation_number, client_email, client_tel, client_siret, client_tva_intracom, client_address_l1, client_address_l2, client_website, client_logo_url, business_logo, business_workload_hours, business_workload_details, quotation_start_day, quotation_end_day, quotation_template, percent_discount, margin_before_start_item, margin_before_end_item, hide_default_outlays_on_empty_outlays FROM __temp__billing_config');
        $this->addSql('DROP TABLE __temp__billing_config');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91500C2C49015800 ON billing_config (client_slug)');
        $this->addSql('CREATE INDEX client_slug_idx ON billing_config (client_slug)');
    }
}
