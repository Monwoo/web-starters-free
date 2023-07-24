<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230724194925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "transaction" ADD COLUMN reception_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE "transaction" ADD COLUMN reception_number VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__transaction AS SELECT id, billing_config_id, payment_method, label, price_without_taxes, added_taxes FROM "transaction"');
        $this->addSql('DROP TABLE "transaction"');
        $this->addSql('CREATE TABLE "transaction" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, billing_config_id INTEGER DEFAULT NULL, payment_method VARCHAR(255) NOT NULL, label VARCHAR(255) DEFAULT NULL, price_without_taxes DOUBLE PRECISION DEFAULT NULL, added_taxes DOUBLE PRECISION DEFAULT NULL, CONSTRAINT FK_723705D190C864DD FOREIGN KEY (billing_config_id) REFERENCES billing_config (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "transaction" (id, billing_config_id, payment_method, label, price_without_taxes, added_taxes) SELECT id, billing_config_id, payment_method, label, price_without_taxes, added_taxes FROM __temp__transaction');
        $this->addSql('DROP TABLE __temp__transaction');
        $this->addSql('CREATE INDEX IDX_723705D190C864DD ON "transaction" (billing_config_id)');
    }
}
