<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240322105323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mws_time_qualif ADD COLUMN html_icon CLOB DEFAULT NULL');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, email, username, roles, password, phone, description, created_at, updated_at, config FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, username VARCHAR(25) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, config CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('INSERT INTO mws_user (id, email, username, roles, password, phone, description, created_at, updated_at, config) SELECT id, email, username, roles, password, phone, description, created_at, updated_at, config FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE INDEX IDX_9C21AA86B63E2EC7 ON mws_user (roles)');
        $this->addSql('CREATE INDEX IDX_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_time_qualif AS SELECT id, label, primary_color_rgb, primary_color_hex, shortcut FROM mws_time_qualif');
        $this->addSql('DROP TABLE mws_time_qualif');
        $this->addSql('CREATE TABLE mws_time_qualif (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(255) NOT NULL, primary_color_rgb VARCHAR(255) DEFAULT NULL, primary_color_hex VARCHAR(255) DEFAULT NULL, shortcut INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO mws_time_qualif (id, label, primary_color_rgb, primary_color_hex, shortcut) SELECT id, label, primary_color_rgb, primary_color_hex, shortcut FROM __temp__mws_time_qualif');
        $this->addSql('DROP TABLE __temp__mws_time_qualif');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, username, email, phone, description, roles, password, config, created_at, updated_at FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(25) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, config CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, username, email, phone, description, roles, password, config, created_at, updated_at) SELECT id, username, email, phone, description, roles, password, config, created_at, updated_at FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE INDEX IDX_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86B63E2EC7 ON mws_user (roles)');
    }
}
