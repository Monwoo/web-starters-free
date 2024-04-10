<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240410012606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mws_page (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, views INTEGER DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE mws_view_counter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, mws_page_id INTEGER DEFAULT NULL, ip CLOB NOT NULL, view_date DATETIME NOT NULL, CONSTRAINT FK_6AB3620A305056D2 FOREIGN KEY (mws_page_id) REFERENCES mws_page (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6AB3620A305056D2 ON mws_view_counter (mws_page_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mws_page');
        $this->addSql('DROP TABLE mws_view_counter');
    }
}
