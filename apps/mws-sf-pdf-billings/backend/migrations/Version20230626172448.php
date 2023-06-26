<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626172448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__outlay AS SELECT id, provider_name, percent_on_business_total, provider_added_price, provider_details, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client, insert_page_break_before, provider_short_description, insert_page_break_after FROM outlay');
        $this->addSql('DROP TABLE outlay');
        $this->addSql('CREATE TABLE outlay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provider_name VARCHAR(255) NOT NULL, percent_on_business_total DOUBLE PRECISION DEFAULT NULL, provider_added_price DOUBLE PRECISION DEFAULT NULL, provider_details CLOB DEFAULT NULL, use_provider_added_price_for_business BOOLEAN DEFAULT NULL, provider_total_with_taxes_forseen_for_client DOUBLE PRECISION DEFAULT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, provider_short_description CLOB DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, taxes_percent_included_in_percent_on_business_total DOUBLE PRECISION DEFAULT NULL, provider_added_price_taxes DOUBLE PRECISION DEFAULT NULL, provider_added_price_taxes_percent DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO outlay (id, provider_name, percent_on_business_total, provider_added_price, provider_details, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client, insert_page_break_before, provider_short_description, insert_page_break_after) SELECT id, provider_name, percent_on_business_total, provider_added_price, provider_details, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client, insert_page_break_before, provider_short_description, insert_page_break_after FROM __temp__outlay');
        $this->addSql('DROP TABLE __temp__outlay');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__outlay AS SELECT id, provider_name, percent_on_business_total, provider_total_with_taxes_forseen_for_client, provider_added_price, use_provider_added_price_for_business, provider_short_description, insert_page_break_before, insert_page_break_after, provider_details FROM outlay');
        $this->addSql('DROP TABLE outlay');
        $this->addSql('CREATE TABLE outlay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provider_name VARCHAR(255) NOT NULL, percent_on_business_total DOUBLE PRECISION DEFAULT NULL, provider_total_with_taxes_forseen_for_client DOUBLE PRECISION DEFAULT NULL, provider_added_price DOUBLE PRECISION DEFAULT NULL, use_provider_added_price_for_business BOOLEAN DEFAULT NULL, provider_short_description CLOB DEFAULT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, provider_details CLOB DEFAULT NULL, provider_taxes DOUBLE PRECISION DEFAULT NULL, provider_taxes_percent DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO outlay (id, provider_name, percent_on_business_total, provider_total_with_taxes_forseen_for_client, provider_added_price, use_provider_added_price_for_business, provider_short_description, insert_page_break_before, insert_page_break_after, provider_details) SELECT id, provider_name, percent_on_business_total, provider_total_with_taxes_forseen_for_client, provider_added_price, use_provider_added_price_for_business, provider_short_description, insert_page_break_before, insert_page_break_after, provider_details FROM __temp__outlay');
        $this->addSql('DROP TABLE __temp__outlay');
    }
}
