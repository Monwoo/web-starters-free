<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231011162314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, email, username, roles, password FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, username VARCHAR(25) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, email, username, roles, password) SELECT id, email, username, roles, password FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, email, username, roles, password FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(25) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, email, username, roles, password) SELECT id, email, username, roles, password FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
    }
}
