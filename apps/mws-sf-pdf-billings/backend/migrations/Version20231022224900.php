<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231022224900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_contact AS SELECT id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at FROM mws_contact');
        $this->addSql('DROP TABLE mws_contact');
        $this->addSql('CREATE TABLE mws_contact (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(255) NOT NULL, status CLOB DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, avatar_url CLOB DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, source_detail CLOB DEFAULT NULL --(DC2Type:json)
        , source_name VARCHAR(255) DEFAULT NULL, business_url CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_contact (id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at) SELECT id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at FROM __temp__mws_contact');
        $this->addSql('DROP TABLE __temp__mws_contact');
        $this->addSql('CREATE INDEX IDX_7074D30EF85E0677 ON mws_contact (username)');
        $this->addSql('CREATE INDEX IDX_7074D30EEA98E376 ON mws_contact (postal_code)');
        $this->addSql('CREATE INDEX IDX_7074D30E2D5B0234 ON mws_contact (city)');
        $this->addSql('CREATE INDEX IDX_7074D30EE7927C74 ON mws_contact (email)');
        $this->addSql('CREATE INDEX IDX_7074D30E444F97DD ON mws_contact (phone)');
        $this->addSql('CREATE INDEX IDX_7074D30E5FA9FB05 ON mws_contact (source_name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_contact AS SELECT id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at FROM mws_contact');
        $this->addSql('DROP TABLE mws_contact');
        $this->addSql('CREATE TABLE mws_contact (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(255) NOT NULL, status CLOB DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, avatar_url CLOB DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, source_detail CLOB DEFAULT NULL --(DC2Type:json)
        , source_name VARCHAR(255) DEFAULT NULL, business_url CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_contact (id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at) SELECT id, username, status, postal_code, city, avatar_url, email, phone, source_detail, source_name, business_url, created_at, updated_at FROM __temp__mws_contact');
        $this->addSql('DROP TABLE __temp__mws_contact');
    }
}
