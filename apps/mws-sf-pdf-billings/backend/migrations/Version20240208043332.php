<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240208043332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_time_qualif (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(255) NOT NULL, shortcut INTEGER DEFAULT NULL)');
        $this->addSql('CREATE TABLE mws_time_qualif_mws_time_tag (mws_time_qualif_id INTEGER NOT NULL, mws_time_tag_id INTEGER NOT NULL, PRIMARY KEY(mws_time_qualif_id, mws_time_tag_id), CONSTRAINT FK_1FA386EDFCD65DE8 FOREIGN KEY (mws_time_qualif_id) REFERENCES mws_time_qualif (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1FA386ED273365B1 FOREIGN KEY (mws_time_tag_id) REFERENCES mws_time_tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1FA386EDFCD65DE8 ON mws_time_qualif_mws_time_tag (mws_time_qualif_id)');
        $this->addSql('CREATE INDEX IDX_1FA386ED273365B1 ON mws_time_qualif_mws_time_tag (mws_time_tag_id)');
        $this->addSql('ALTER TABLE mws_time_slot ADD COLUMN max_price_per_hr DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_time_tag ADD COLUMN price_per_hr DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_time_qualif');
        $this->addSql('DROP TABLE mws_time_qualif_mws_time_tag');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_slot AS SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM mws_time_slot');
        $this->addSql('DROP TABLE mws_time_slot');
        $this->addSql('CREATE TABLE mws_time_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source_time DATETIME DEFAULT NULL, source CLOB NOT NULL --(DC2Type:json)
        , range_day_idx_by10_min INTEGER DEFAULT NULL, range_day_idx_by_custom_norm INTEGER DEFAULT NULL, keywords CLOB DEFAULT NULL, source_stamp VARCHAR(512) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_time_slot (id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at) SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM __temp__mws_time_slot');
        $this->addSql('DROP TABLE __temp__mws_time_slot');
        $this->addSql('CREATE INDEX IDX_DE1C400A6E1E1D46 ON mws_time_slot (source_time)');
        $this->addSql('CREATE INDEX IDX_DE1C400A20DE2850 ON mws_time_slot (range_day_idx_by10_min)');
        $this->addSql('CREATE INDEX IDX_DE1C400A57E12A7E ON mws_time_slot (range_day_idx_by_custom_norm)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_tag AS SELECT id, category_id, slug, label, description FROM mws_time_tag');
        $this->addSql('DROP TABLE mws_time_tag');
        $this->addSql('CREATE TABLE mws_time_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER DEFAULT NULL, slug VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, CONSTRAINT FK_168E5F4512469DE2 FOREIGN KEY (category_id) REFERENCES mws_time_tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_time_tag (id, category_id, slug, label, description) SELECT id, category_id, slug, label, description FROM __temp__mws_time_tag');
        $this->addSql('DROP TABLE __temp__mws_time_tag');
        $this->addSql('CREATE INDEX IDX_168E5F4512469DE2 ON mws_time_tag (category_id)');
        $this->addSql('CREATE INDEX IDX_168E5F45989D9B62 ON mws_time_tag (slug)');
    }
}
