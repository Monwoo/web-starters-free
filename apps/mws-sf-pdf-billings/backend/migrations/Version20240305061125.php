<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240305061125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_slot AS SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, created_at, updated_at, keywords, source_stamp, max_price_per_hr FROM mws_time_slot');
        $this->addSql('DROP TABLE mws_time_slot');
        $this->addSql('CREATE TABLE mws_time_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source_time_gmt DATETIME DEFAULT NULL, source CLOB NOT NULL --(DC2Type:json)
        , range_day_idx_by10_min INTEGER DEFAULT NULL, range_day_idx_by_custom_norm INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, keywords CLOB DEFAULT NULL, source_stamp VARCHAR(512) NOT NULL, max_price_per_hr DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_time_slot (id, source_time_gmt, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, created_at, updated_at, keywords, source_stamp, max_price_per_hr) SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, created_at, updated_at, keywords, source_stamp, max_price_per_hr FROM __temp__mws_time_slot');
        $this->addSql('DROP TABLE __temp__mws_time_slot');
        $this->addSql('CREATE INDEX IDX_DE1C400A57E12A7E ON mws_time_slot (range_day_idx_by_custom_norm)');
        $this->addSql('CREATE INDEX IDX_DE1C400A20DE2850 ON mws_time_slot (range_day_idx_by10_min)');
        $this->addSql('CREATE INDEX IDX_DE1C400AAC43F9C0 ON mws_time_slot (source_time_gmt)');
        $this->addSql('ALTER TABLE mws_time_tag ADD COLUMN price_per_hr_rules CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_slot AS SELECT id, source_time_gmt, source, range_day_idx_by10_min, max_price_per_hr, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM mws_time_slot');
        $this->addSql('DROP TABLE mws_time_slot');
        $this->addSql('CREATE TABLE mws_time_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source_time DATETIME DEFAULT NULL, source CLOB NOT NULL --(DC2Type:json)
        , range_day_idx_by10_min INTEGER DEFAULT NULL, max_price_per_hr DOUBLE PRECISION DEFAULT NULL, range_day_idx_by_custom_norm INTEGER DEFAULT NULL, keywords CLOB DEFAULT NULL, source_stamp VARCHAR(512) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_time_slot (id, source_time, source, range_day_idx_by10_min, max_price_per_hr, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at) SELECT id, source_time_gmt, source, range_day_idx_by10_min, max_price_per_hr, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM __temp__mws_time_slot');
        $this->addSql('DROP TABLE __temp__mws_time_slot');
        $this->addSql('CREATE INDEX IDX_DE1C400A20DE2850 ON mws_time_slot (range_day_idx_by10_min)');
        $this->addSql('CREATE INDEX IDX_DE1C400A57E12A7E ON mws_time_slot (range_day_idx_by_custom_norm)');
        $this->addSql('CREATE INDEX IDX_DE1C400A6E1E1D46 ON mws_time_slot (source_time)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_tag AS SELECT id, category_id, slug, label, description, price_per_hr FROM mws_time_tag');
        $this->addSql('DROP TABLE mws_time_tag');
        $this->addSql('CREATE TABLE mws_time_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER DEFAULT NULL, slug VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, price_per_hr DOUBLE PRECISION DEFAULT NULL, CONSTRAINT FK_168E5F4512469DE2 FOREIGN KEY (category_id) REFERENCES mws_time_tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_time_tag (id, category_id, slug, label, description, price_per_hr) SELECT id, category_id, slug, label, description, price_per_hr FROM __temp__mws_time_tag');
        $this->addSql('DROP TABLE __temp__mws_time_tag');
        $this->addSql('CREATE INDEX IDX_168E5F4512469DE2 ON mws_time_tag (category_id)');
        $this->addSql('CREATE INDEX IDX_168E5F45989D9B62 ON mws_time_tag (slug)');
    }
}
