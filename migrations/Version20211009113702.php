<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211009113702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, limit_pages VARCHAR(255) NOT NULL, strip_units BOOLEAN NOT NULL, debug BOOLEAN NOT NULL, output_format CLOB NOT NULL --(DC2Type:array)
        )');
        $this->addSql('DROP TABLE helperfields');
        $this->addSql('DROP INDEX IDX_2A979110D2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM parameter');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL COLLATE BINARY, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL, CONSTRAINT FK_2A979110D2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO parameter (parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default) SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM __temp__parameter');
        $this->addSql('DROP TABLE __temp__parameter');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
        $this->addSql('DROP INDEX IDX_C8C0BC4CD2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__protocol AS SELECT id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at FROM protocol');
        $this->addSql('DROP TABLE protocol');
        $this->addSql('CREATE TABLE protocol (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, protocol_name VARCHAR(255) NOT NULL COLLATE BINARY, protocol_size INTEGER NOT NULL, protocol_mime_type VARCHAR(255) NOT NULL COLLATE BINARY, protocol_orig_name VARCHAR(255) NOT NULL COLLATE BINARY, updated_at DATETIME NOT NULL, CONSTRAINT FK_C8C0BC4CD2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO protocol (id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at) SELECT id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at FROM __temp__protocol');
        $this->addSql('DROP TABLE __temp__protocol');
        $this->addSql('CREATE INDEX IDX_C8C0BC4CD2D09835 ON protocol (geraet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE helperfields (helperfield_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, helperfield_name CLOB DEFAULT NULL COLLATE BINARY, helperfield_inputtype CLOB DEFAULT \'text\' COLLATE BINARY, helperfield_label CLOB DEFAULT \'label\' COLLATE BINARY, helperfield_help CLOB DEFAULT \'helpful hint\' COLLATE BINARY, helperfield_placeholder CLOB DEFAULT \'beispiel-Wert\' COLLATE BINARY, helperfield_value CLOB DEFAULT NULL COLLATE BINARY)');
        $this->addSql('CREATE INDEX IDX_63ABEC3ED2D09835 ON helperfields (geraet_id)');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP INDEX IDX_2A979110D2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM parameter');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO parameter (parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default) SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM __temp__parameter');
        $this->addSql('DROP TABLE __temp__parameter');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
        $this->addSql('DROP INDEX IDX_C8C0BC4CD2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__protocol AS SELECT id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at FROM protocol');
        $this->addSql('DROP TABLE protocol');
        $this->addSql('CREATE TABLE protocol (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, protocol_name VARCHAR(255) NOT NULL, protocol_size INTEGER NOT NULL, protocol_mime_type VARCHAR(255) NOT NULL, protocol_orig_name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO protocol (id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at) SELECT id, geraet_id, protocol_name, protocol_size, protocol_mime_type, protocol_orig_name, updated_at FROM __temp__protocol');
        $this->addSql('DROP TABLE __temp__protocol');
        $this->addSql('CREATE INDEX IDX_C8C0BC4CD2D09835 ON protocol (geraet_id)');
    }
}
