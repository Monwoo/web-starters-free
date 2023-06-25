<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230625103502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE outlay ADD COLUMN provider_taxes_percent DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__outlay AS SELECT id, provider_name, insert_page_break_before, insert_page_break_after, provider_short_description, percent_on_business_total, provider_taxes, provider_details, provider_added_price, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client FROM outlay');
        $this->addSql('DROP TABLE outlay');
        $this->addSql('CREATE TABLE outlay (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, provider_name VARCHAR(255) NOT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, provider_short_description CLOB DEFAULT NULL, percent_on_business_total DOUBLE PRECISION DEFAULT NULL, provider_taxes DOUBLE PRECISION DEFAULT NULL, provider_details CLOB DEFAULT NULL, provider_added_price DOUBLE PRECISION DEFAULT NULL, use_provider_added_price_for_business BOOLEAN DEFAULT NULL, provider_total_with_taxes_forseen_for_client DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO outlay (id, provider_name, insert_page_break_before, insert_page_break_after, provider_short_description, percent_on_business_total, provider_taxes, provider_details, provider_added_price, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client) SELECT id, provider_name, insert_page_break_before, insert_page_break_after, provider_short_description, percent_on_business_total, provider_taxes, provider_details, provider_added_price, use_provider_added_price_for_business, provider_total_with_taxes_forseen_for_client FROM __temp__outlay');
        $this->addSql('DROP TABLE __temp__outlay');
    }
}
