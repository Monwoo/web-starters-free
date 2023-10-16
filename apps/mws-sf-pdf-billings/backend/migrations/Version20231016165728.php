<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016165728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_calendar_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, start DATETIME DEFAULT NULL, stop DATETIME DEFAULT NULL, current_status_slug VARCHAR(255) DEFAULT NULL, current_comment CLOB DEFAULT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_9DA32A849F79558F ON mws_calendar_event (start)');
        $this->addSql('CREATE INDEX IDX_9DA32A84B95616B6 ON mws_calendar_event (stop)');
        $this->addSql('CREATE INDEX IDX_9DA32A841E4F9D2F ON mws_calendar_event (current_status_slug)');
        $this->addSql('CREATE TABLE mws_client_event_mws_user (mws_calendar_event_id INTEGER NOT NULL, mws_user_id INTEGER NOT NULL, PRIMARY KEY(mws_calendar_event_id, mws_user_id), CONSTRAINT FK_7319C120A0C9488B FOREIGN KEY (mws_calendar_event_id) REFERENCES mws_calendar_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7319C1209B78E6A3 FOREIGN KEY (mws_user_id) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7319C120A0C9488B ON mws_client_event_mws_user (mws_calendar_event_id)');
        $this->addSql('CREATE INDEX IDX_7319C1209B78E6A3 ON mws_client_event_mws_user (mws_user_id)');
        $this->addSql('CREATE TABLE mws_observer_event_mws_user (mws_calendar_event_id INTEGER NOT NULL, mws_user_id INTEGER NOT NULL, PRIMARY KEY(mws_calendar_event_id, mws_user_id), CONSTRAINT FK_A3CDE55BA0C9488B FOREIGN KEY (mws_calendar_event_id) REFERENCES mws_calendar_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A3CDE55B9B78E6A3 FOREIGN KEY (mws_user_id) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A3CDE55BA0C9488B ON mws_observer_event_mws_user (mws_calendar_event_id)');
        $this->addSql('CREATE INDEX IDX_A3CDE55B9B78E6A3 ON mws_observer_event_mws_user (mws_user_id)');
        $this->addSql('CREATE TABLE mws_owner_event_mws_user (mws_calendar_event_id INTEGER NOT NULL, mws_user_id INTEGER NOT NULL, PRIMARY KEY(mws_calendar_event_id, mws_user_id), CONSTRAINT FK_8128C40BA0C9488B FOREIGN KEY (mws_calendar_event_id) REFERENCES mws_calendar_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8128C40B9B78E6A3 FOREIGN KEY (mws_user_id) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8128C40BA0C9488B ON mws_owner_event_mws_user (mws_calendar_event_id)');
        $this->addSql('CREATE INDEX IDX_8128C40B9B78E6A3 ON mws_owner_event_mws_user (mws_user_id)');
        $this->addSql('CREATE TABLE mws_calendar_status (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, label CLOB DEFAULT NULL, category_slug VARCHAR(255) DEFAULT NULL, bg_color VARCHAR(255) DEFAULT NULL, text_color VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_D9C1194E989D9B62 ON mws_calendar_status (slug)');
        $this->addSql('CREATE INDEX IDX_D9C1194E1306E125 ON mws_calendar_status (category_slug)');
        $this->addSql('CREATE TABLE mws_calendar_tracking (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, calendar_event_id INTEGER NOT NULL, owner_id INTEGER NOT NULL, status_slug VARCHAR(255) DEFAULT NULL, comment CLOB DEFAULT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_7942C21C7495C8E3 FOREIGN KEY (calendar_event_id) REFERENCES mws_calendar_event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7942C21C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES mws_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7942C21C7495C8E3 ON mws_calendar_tracking (calendar_event_id)');
        $this->addSql('CREATE INDEX IDX_7942C21C7E3C61F9 ON mws_calendar_tracking (owner_id)');
        $this->addSql('CREATE TABLE mws_user_mws_user (mws_user_source INTEGER NOT NULL, mws_user_target INTEGER NOT NULL, PRIMARY KEY(mws_user_source, mws_user_target), CONSTRAINT FK_9F4D8977FECF2D18 FOREIGN KEY (mws_user_source) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9F4D8977E72A7D97 FOREIGN KEY (mws_user_target) REFERENCES mws_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9F4D8977FECF2D18 ON mws_user_mws_user (mws_user_source)');
        $this->addSql('CREATE INDEX IDX_9F4D8977E72A7D97 ON mws_user_mws_user (mws_user_target)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, email, username, roles, password FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, username VARCHAR(25) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, email, username, roles, password) SELECT id, email, username, roles, password FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE INDEX IDX_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE INDEX IDX_9C21AA86E7927C74 ON mws_user (email)');
        $this->addSql('CREATE INDEX IDX_9C21AA86B63E2EC7 ON mws_user (roles)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_calendar_event');
        $this->addSql('DROP TABLE mws_client_event_mws_user');
        $this->addSql('DROP TABLE mws_observer_event_mws_user');
        $this->addSql('DROP TABLE mws_owner_event_mws_user');
        $this->addSql('DROP TABLE mws_calendar_status');
        $this->addSql('DROP TABLE mws_calendar_tracking');
        $this->addSql('DROP TABLE mws_user_mws_user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__mws_user AS SELECT id, username, email, roles, password FROM mws_user');
        $this->addSql('DROP TABLE mws_user');
        $this->addSql('CREATE TABLE mws_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(25) NOT NULL, email VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO mws_user (id, username, email, roles, password) SELECT id, username, email, roles, password FROM __temp__mws_user');
        $this->addSql('DROP TABLE __temp__mws_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86F85E0677 ON mws_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C21AA86E7927C74 ON mws_user (email)');
    }
}
