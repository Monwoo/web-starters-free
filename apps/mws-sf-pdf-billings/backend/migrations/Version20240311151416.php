<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240311151416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mws_time_slot ADD COLUMN max_path CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_slot AS SELECT id, max_price_tag_id, source_time_gmt, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM mws_time_slot');
        $this->addSql('DROP TABLE mws_time_slot');
        $this->addSql('CREATE TABLE mws_time_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, max_price_tag_id INTEGER DEFAULT NULL, source_time_gmt DATETIME DEFAULT NULL, source CLOB NOT NULL --(DC2Type:json)
        , range_day_idx_by10_min INTEGER DEFAULT NULL, range_day_idx_by_custom_norm INTEGER DEFAULT NULL, keywords CLOB DEFAULT NULL, source_stamp VARCHAR(512) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_DE1C400A5C275899 FOREIGN KEY (max_price_tag_id) REFERENCES mws_time_tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO mws_time_slot (id, max_price_tag_id, source_time_gmt, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at) SELECT id, max_price_tag_id, source_time_gmt, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, source_stamp, created_at, updated_at FROM __temp__mws_time_slot');
        $this->addSql('DROP TABLE __temp__mws_time_slot');
        $this->addSql('CREATE INDEX IDX_DE1C400A5C275899 ON mws_time_slot (max_price_tag_id)');
        $this->addSql('CREATE INDEX IDX_DE1C400AAC43F9C0 ON mws_time_slot (source_time_gmt)');
        $this->addSql('CREATE INDEX IDX_DE1C400A20DE2850 ON mws_time_slot (range_day_idx_by10_min)');
        $this->addSql('CREATE INDEX IDX_DE1C400A57E12A7E ON mws_time_slot (range_day_idx_by_custom_norm)');
    }
}
