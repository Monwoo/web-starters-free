<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231022002532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_offer_mws_offer_status (mws_offer_id INTEGER NOT NULL, mws_offer_status_id INTEGER NOT NULL, PRIMARY KEY(mws_offer_id, mws_offer_status_id), CONSTRAINT FK_15B5D88A9C40F742 FOREIGN KEY (mws_offer_id) REFERENCES mws_offer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_15B5D88AC0951977 FOREIGN KEY (mws_offer_status_id) REFERENCES mws_offer_status (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_15B5D88A9C40F742 ON mws_offer_mws_offer_status (mws_offer_id)');
        $this->addSql('CREATE INDEX IDX_15B5D88AC0951977 ON mws_offer_mws_offer_status (mws_offer_status_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_offer_mws_offer_status');
    }
}
