<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525203410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__config AS SELECT id, limit_pages, strip_units, debug, output_format FROM config
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE config
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, limit_pages VARCHAR(255) NOT NULL, strip_units BOOLEAN DEFAULT 1 NOT NULL, debug BOOLEAN DEFAULT 0 NOT NULL, output_format VARCHAR(255) NOT NULL, auto_import_parameters BOOLEAN DEFAULT 1 NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO config (id, limit_pages, strip_units, debug, output_format) SELECT id, limit_pages, strip_units, debug, output_format FROM __temp__config
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__config
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default, position FROM parameter
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parameter
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, geraet_id INTEGER NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL, sort_position INTEGER DEFAULT NULL, CONSTRAINT FK_2A979110D2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO parameter (parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default, sort_position) SELECT parameter_id, geraet_id, parameter_name, parameter_selected, parameter_default, position FROM __temp__parameter
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__parameter
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)
        SQL);
        $this->addSql(<<<'SQL'
            WITH RankedParameters AS (
                SELECT parameter_id, ROW_NUMBER() OVER (ORDER BY parameter_id) AS new_sort_position
                FROM parameter
            )
            UPDATE parameter
            SET sort_position = (
                SELECT new_sort_position
                FROM RankedParameters
                WHERE RankedParameters.parameter_id = parameter.parameter_id
            );
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__config AS SELECT id, limit_pages, strip_units, debug, output_format FROM config
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE config
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE config (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, limit_pages VARCHAR(255) NOT NULL, strip_units BOOLEAN NOT NULL, debug BOOLEAN NOT NULL, output_format CLOB NOT NULL --(DC2Type:array)
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO config (id, limit_pages, strip_units, debug, output_format) SELECT id, limit_pages, strip_units, debug, output_format FROM __temp__config
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__config
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__parameter AS SELECT parameter_id, parameter_name, parameter_selected, parameter_default, sort_position, geraet_id FROM parameter
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE parameter
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE parameter (parameter_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, parameter_name CLOB DEFAULT NULL, parameter_selected BOOLEAN NOT NULL, parameter_default BOOLEAN NOT NULL, position INTEGER DEFAULT NULL, geraet_id INTEGER NOT NULL, CONSTRAINT FK_2A979110D2D09835 FOREIGN KEY (geraet_id) REFERENCES geraet (geraet_id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO parameter (parameter_id, parameter_name, parameter_selected, parameter_default, position, geraet_id) SELECT parameter_id, parameter_name, parameter_selected, parameter_default, sort_position, geraet_id FROM __temp__parameter
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__parameter
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2A979110D2D09835 ON parameter (geraet_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX position_idx ON parameter (position)
        SQL);
    }
}
