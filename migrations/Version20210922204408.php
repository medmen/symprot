<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210922204408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_322085BBCCD59258');
        $this->addSql('CREATE TEMPORARY TABLE __temp__geraet AS SELECT geraet_id, protocol_id, geraet_name, geraet_beschreibung FROM geraet');
        $this->addSql('DROP TABLE geraet');
        $this->addSql('CREATE TABLE geraet (geraet_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, protocol_id INTEGER DEFAULT NULL, geraet_name CLOB DEFAULT \'MRT\' COLLATE BINARY, geraet_beschreibung CLOB DEFAULT \'Bei mehreren geräten hilfreich zur Unterscheidung\' COLLATE BINARY, CONSTRAINT FK_322085BBCCD59258 FOREIGN KEY (protocol_id) REFERENCES protocol (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO geraet (geraet_id, protocol_id, geraet_name, geraet_beschreibung) SELECT geraet_id, protocol_id, geraet_name, geraet_beschreibung FROM __temp__geraet');
        $this->addSql('DROP TABLE __temp__geraet');
        $this->addSql('CREATE INDEX IDX_322085BBCCD59258 ON geraet (protocol_id)');
        $this->addSql('DROP INDEX IDX_63ABEC3ED2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__helperfields AS SELECT helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value FROM helperfields');
        $this->addSql('DROP TABLE helperfields');
        $this->addSql('CREATE TABLE helperfields (helperfield_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, helperfield_name CLOB DEFAULT NULL COLLATE BINARY, helperfield_inputtype CLOB DEFAULT \'text\' COLLATE BINARY, helperfield_label CLOB DEFAULT \'label\' COLLATE BINARY, helperfield_help CLOB DEFAULT \'helpful hint\' COLLATE BINARY, helperfield_placeholder CLOB DEFAULT \'beispiel-Wert\' COLLATE BINARY, helperfield_value CLOB DEFAULT NULL COLLATE BINARY, CONSTRAINT FK_63ABEC3ED2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO helperfields (helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value) SELECT helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value FROM __temp__helperfields');
        $this->addSql('DROP TABLE __temp__helperfields');
        $this->addSql('CREATE INDEX IDX_63ABEC3ED2D09835 ON helperfields (geraet_id)');
        $this->addSql('DROP INDEX IDX_2A979110D2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM parameter');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL COLLATE BINARY, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL, CONSTRAINT FK_2A979110D2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO parameter (parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default) SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM __temp__parameter');
        $this->addSql('DROP TABLE __temp__parameter');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_322085BBCCD59258');
        $this->addSql('CREATE TEMPORARY TABLE __temp__geraet AS SELECT geraet_id, protocol_id, geraet_name, geraet_beschreibung FROM geraet');
        $this->addSql('DROP TABLE geraet');
        $this->addSql('CREATE TABLE geraet (geraet_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, protocol_id INTEGER DEFAULT NULL, geraet_name CLOB DEFAULT \'MRT\', geraet_beschreibung CLOB DEFAULT \'Bei mehreren geräten hilfreich zur Unterscheidung\')');
        $this->addSql('INSERT INTO geraet (geraet_id, protocol_id, geraet_name, geraet_beschreibung) SELECT geraet_id, protocol_id, geraet_name, geraet_beschreibung FROM __temp__geraet');
        $this->addSql('DROP TABLE __temp__geraet');
        $this->addSql('CREATE INDEX IDX_322085BBCCD59258 ON geraet (protocol_id)');
        $this->addSql('DROP INDEX IDX_63ABEC3ED2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__helperfields AS SELECT helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value FROM helperfields');
        $this->addSql('DROP TABLE helperfields');
        $this->addSql('CREATE TABLE helperfields (helperfield_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, helperfield_name CLOB DEFAULT NULL, helperfield_inputtype CLOB DEFAULT \'text\', helperfield_label CLOB DEFAULT \'label\', helperfield_help CLOB DEFAULT \'helpful hint\', helperfield_placeholder CLOB DEFAULT \'beispiel-Wert\', helperfield_value CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO helperfields (helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value) SELECT helperfield_id, geraet_id, helperfield_name, helperfield_inputtype, helperfield_label, helperfield_help, helperfield_placeholder, helperfield_value FROM __temp__helperfields');
        $this->addSql('DROP TABLE __temp__helperfields');
        $this->addSql('CREATE INDEX IDX_63ABEC3ED2D09835 ON helperfields (geraet_id)');
        $this->addSql('DROP INDEX IDX_2A979110D2D09835');
        $this->addSql('CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM parameter');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected INTEGER DEFAULT NULL, parameter_default INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO parameter (parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default) SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default FROM __temp__parameter');
        $this->addSql('DROP TABLE __temp__parameter');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
    }
}
