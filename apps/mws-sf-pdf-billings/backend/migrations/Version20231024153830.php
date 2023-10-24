<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231024153830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer_status AS SELECT id, slug, label, category_slug, bg_color, text_color, updated_at, created_at, category_allow_multiples_tags FROM mws_offer_status');
        $this->addSql('DROP TABLE mws_offer_status');
        $this->addSql('CREATE TABLE mws_offer_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, category_ok_with_multiples_tags BOOLEAN DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_offer_status (id, slug, label, category_slug, bg_color, text_color, updated_at, created_at, category_ok_with_multiples_tags) SELECT id, slug, label, category_slug, bg_color, text_color, updated_at, created_at, category_allow_multiples_tags FROM __temp__mws_offer_status');
        $this->addSql('DROP TABLE __temp__mws_offer_status');
        $this->addSql('CREATE INDEX IDX_2099F1351306E125 ON mws_offer_status (category_slug)');
        $this->addSql('CREATE INDEX IDX_2099F135989D9B62 ON mws_offer_status (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_offer_status AS SELECT id, slug, label, category_slug, category_ok_with_multiples_tags, bg_color, text_color, created_at, updated_at FROM mws_offer_status');
        $this->addSql('DROP TABLE mws_offer_status');
        $this->addSql('CREATE TABLE mws_offer_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, category_allow_multiples_tags BOOLEAN DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_offer_status (id, slug, label, category_slug, category_allow_multiples_tags, bg_color, text_color, created_at, updated_at) SELECT id, slug, label, category_slug, category_ok_with_multiples_tags, bg_color, text_color, created_at, updated_at FROM __temp__mws_offer_status');
        $this->addSql('DROP TABLE __temp__mws_offer_status');
        $this->addSql('CREATE INDEX IDX_2099F135989D9B62 ON mws_offer_status (slug)');
        $this->addSql('CREATE INDEX IDX_2099F1351306E125 ON mws_offer_status (category_slug)');
    }
}
