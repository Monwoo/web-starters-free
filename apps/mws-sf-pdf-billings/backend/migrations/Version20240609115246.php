<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240609115246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_offer_mws_time_tag (mws_offer_id INTEGER NOT NULL, mws_time_tag_id INTEGER NOT NULL, PRIMARY KEY(mws_offer_id, mws_time_tag_id), CONSTRAINT FK_B20AE5149C40F742 FOREIGN KEY (mws_offer_id) REFERENCES mws_offer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B20AE514273365B1 FOREIGN KEY (mws_time_tag_id) REFERENCES mws_time_tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B20AE5149C40F742 ON mws_offer_mws_time_tag (mws_offer_id)');
        $this->addSql('CREATE INDEX IDX_B20AE514273365B1 ON mws_offer_mws_time_tag (mws_time_tag_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_offer_mws_time_tag');
    }
}
