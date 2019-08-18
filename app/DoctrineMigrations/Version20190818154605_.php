<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190818154605 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ExplainIndex (id INT AUTO_INCREMENT NOT NULL, main_term_id INT NOT NULL, index_term LONGTEXT DEFAULT NULL, index_add_letter VARCHAR(4) DEFAULT NULL, index_kana LONGTEXT DEFAULT NULL, nombre INT NOT NULL, create_date DATETIME NOT NULL, modify_date DATETIME DEFAULT NULL, delete_date DATETIME DEFAULT NULL, delete_flag TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Explains (id INT AUTO_INCREMENT NOT NULL, main_term_id INT NOT NULL, index_term LONGTEXT DEFAULT NULL, index_add_letter VARCHAR(4) DEFAULT NULL, index_kana LONGTEXT DEFAULT NULL, nombre INT NOT NULL, create_date DATETIME NOT NULL, modify_date DATETIME DEFAULT NULL, delete_date DATETIME DEFAULT NULL, delete_flag TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ExplainIndex');
        $this->addSql('DROP TABLE Explains');
    }
}
