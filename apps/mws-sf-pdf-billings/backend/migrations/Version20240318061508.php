<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240318061508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mws_user ADD COLUMN config CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, username, email, phone, description, roles, password, created_at, updated_at FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(25) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, username, email, phone, description, roles, password, created_at, updated_at) SELECT id, username, email, phone, description, roles, password, created_at, updated_at FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE INDEX IDX_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86B63E2EC7 ON mws_user (roles)');
    }
}
