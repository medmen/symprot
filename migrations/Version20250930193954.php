<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930193954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, limit_pages VARCHAR(255) NOT NULL, strip_units BOOLEAN DEFAULT 1 NOT NULL, debug BOOLEAN DEFAULT 0 NOT NULL, output_format VARCHAR(255) NOT NULL, auto_import_parameters BOOLEAN DEFAULT 1 NOT NULL)');
        $this->addSql('CREATE TABLE geraet (geraet_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_name CLOB DEFAULT \'MRT\', geraet_beschreibung CLOB DEFAULT \'Bei mehreren geräten hilfreich zur Unterscheidung\')');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected BOOLEAN NOT NULL, sort_position INTEGER DEFAULT NULL, CONSTRAINT FK_2A979110D2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
        $this->addSql('CREATE TABLE protocol (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, protocol_name VARCHAR(255) NOT NULL, protocol_size INTEGER NOT NULL, protocol_mime_type VARCHAR(255) NOT NULL, protocol_orig_name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_C8C0BC4CD2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C8C0BC4CD2D09835 ON protocol (geraet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP TABLE geraet');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('DROP TABLE protocol');
    }
}
