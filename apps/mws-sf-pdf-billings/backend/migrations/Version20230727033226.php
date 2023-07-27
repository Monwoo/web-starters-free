<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230727033226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__product AS SELECT id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom FROM product');
        $this->addSql('DROP TABLE product');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label CLOB NOT NULL, quantity DOUBLE PRECISION DEFAULT NULL, price_per_unit_without_taxes DOUBLE PRECISION DEFAULT NULL, taxes_percent DOUBLE PRECISION DEFAULT NULL, discount_percent DOUBLE PRECISION DEFAULT NULL, left_title CLOB DEFAULT NULL, left_details CLOB DEFAULT NULL, right_details CLOB DEFAULT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, margin_top VARCHAR(255) DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, margin_bottom VARCHAR(255) DEFAULT NULL, used_for_business_total BOOLEAN DEFAULT NULL)');
        $this->addSql('INSERT INTO product (id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom) SELECT id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom FROM __temp__product');
        $this->addSql('DROP TABLE __temp__product');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__product AS SELECT id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom FROM product');
        $this->addSql('DROP TABLE product');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label CLOB NOT NULL, quantity DOUBLE PRECISION DEFAULT NULL, price_per_unit_without_taxes DOUBLE PRECISION DEFAULT NULL, taxes_percent DOUBLE PRECISION NOT NULL, discount_percent DOUBLE PRECISION DEFAULT NULL, left_title CLOB DEFAULT NULL, left_details CLOB DEFAULT NULL, right_details CLOB DEFAULT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, margin_top VARCHAR(255) DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, margin_bottom VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO product (id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom) SELECT id, label, quantity, price_per_unit_without_taxes, taxes_percent, discount_percent, left_title, left_details, right_details, insert_page_break_before, margin_top, insert_page_break_after, margin_bottom FROM __temp__product');
        $this->addSql('DROP TABLE __temp__product');
    }
}
