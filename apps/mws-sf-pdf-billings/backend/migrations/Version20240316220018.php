<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240316220018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_time_qualif_mws_user (mws_time_qualif_id INTEGER NOT NULL, mws_user_id INTEGER NOT NULL, PRIMARY KEY(mws_time_qualif_id, mws_user_id), CONSTRAINT FK_7DE9F94EFCD65DE8 FOREIGN KEY (mws_time_qualif_id) REFERENCES mws_time_qualif (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7DE9F94E9B78E6A3 FOREIGN KEY (mws_user_id) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7DE9F94EFCD65DE8 ON mws_time_qualif_mws_user (mws_time_qualif_id)');
        $this->addSql('CREATE INDEX IDX_7DE9F94E9B78E6A3 ON mws_time_qualif_mws_user (mws_user_id)');
        $this->addSql('ALTER TABLE mws_time_qualif ADD COLUMN primary_color_rgb VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mws_time_qualif ADD COLUMN primary_color_hex VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_time_qualif_mws_user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_qualif AS SELECT id, label, shortcut FROM mws_time_qualif');
        $this->addSql('DROP TABLE mws_time_qualif');
        $this->addSql('CREATE TABLE mws_time_qualif (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(255) NOT NULL, shortcut INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_time_qualif (id, label, shortcut) SELECT id, label, shortcut FROM __temp__mws_time_qualif');
        $this->addSql('DROP TABLE __temp__mws_time_qualif');
    }
}
