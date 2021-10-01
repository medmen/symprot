<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211001210036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE geraet (geraet_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_name CLOB DEFAULT \'MRT\', geraet_beschreibung CLOB DEFAULT \'Bei mehreren geräten hilfreich zur Unterscheidung\')');
        $this->addSql('CREATE TABLE helperfields (helperfield_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, helperfield_name CLOB DEFAULT NULL, helperfield_inputtype CLOB DEFAULT \'text\', helperfield_label CLOB DEFAULT \'label\', helperfield_help CLOB DEFAULT \'helpful hint\', helperfield_placeholder CLOB DEFAULT \'beispiel-Wert\', helperfield_value CLOB DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_63ABEC3ED2D09835 ON helperfields (geraet_id)');
        $this->addSql('CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL)');
        $this->addSql('CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)');
        $this->addSql('CREATE TABLE protocol (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, protocol_name VARCHAR(255) NOT NULL, protocol_size INTEGER NOT NULL, protocol_mime_type VARCHAR(255) NOT NULL, protocol_orig_name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_C8C0BC4CD2D09835 ON protocol (geraet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE geraet');
        $this->addSql('DROP TABLE helperfields');
        $this->addSql('DROP TABLE parameter');
        $this->addSql('DROP TABLE protocol');
    }
}
