<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230727002048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label CLOB NOT NULL, quantity DOUBLE PRECISION DEFAULT NULL, price_per_unit_without_taxes DOUBLE PRECISION DEFAULT NULL, taxes_percent DOUBLE PRECISION NOT NULL, discount_percent DOUBLE PRECISION DEFAULT NULL, left_title CLOB DEFAULT NULL, left_details CLOB DEFAULT NULL, right_details CLOB DEFAULT NULL, insert_page_break_before BOOLEAN DEFAULT NULL, margin_top VARCHAR(255) DEFAULT NULL, insert_page_break_after BOOLEAN DEFAULT NULL, margin_bottom VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE product_billing_config (product_id INTEGER NOT NULL, billing_config_id INTEGER NOT NULL, PRIMARY KEY(product_id, billing_config_id), CONSTRAINT FK_C23972004584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C239720090C864DD FOREIGN KEY (billing_config_id) REFERENCES billing_config (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C23972004584665A ON product_billing_config (product_id)');
        $this->addSql('CREATE INDEX IDX_C239720090C864DD ON product_billing_config (billing_config_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_billing_config');
    }
}
