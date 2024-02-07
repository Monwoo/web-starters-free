<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240207063719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mws_time_slot ADD COLUMN source_stamp VARCHAR(512) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_slot AS SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, created_at, updated_at FROM mws_time_slot');
        $this->addSql('DROP TABLE mws_time_slot');
        $this->addSql('CREATE TABLE mws_time_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, source_time DATETIME DEFAULT NULL, source CLOB NOT NULL --(DC2Type:json)
        , range_day_idx_by10_min INTEGER DEFAULT NULL, range_day_idx_by_custom_norm INTEGER DEFAULT NULL, keywords CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_time_slot (id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, created_at, updated_at) SELECT id, source_time, source, range_day_idx_by10_min, range_day_idx_by_custom_norm, keywords, created_at, updated_at FROM __temp__mws_time_slot');
        $this->addSql('DROP TABLE __temp__mws_time_slot');
        $this->addSql('CREATE INDEX IDX_DE1C400A6E1E1D46 ON mws_time_slot (source_time)');
        $this->addSql('CREATE INDEX IDX_DE1C400A20DE2850 ON mws_time_slot (range_day_idx_by10_min)');
        $this->addSql('CREATE INDEX IDX_DE1C400A57E12A7E ON mws_time_slot (range_day_idx_by_custom_norm)');
    }
}
