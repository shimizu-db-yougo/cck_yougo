<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190723113808 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE SubTerm (id INT AUTO_INCREMENT NOT NULL, main_term_id INT NOT NULL, sub_term LONGTEXT DEFAULT NULL, red_letter TINYINT(1) DEFAULT \'0\' NOT NULL, text_frequency INT NOT NULL, center_frequency INT NOT NULL, news_exam TINYINT(1) DEFAULT \'0\' NOT NULL, delimiter VARCHAR(4) DEFAULT NULL, kana LONGTEXT DEFAULT NULL, delimiter_kana VARCHAR(4) DEFAULT NULL, index_add_letter VARCHAR(4) DEFAULT NULL, index_kana LONGTEXT DEFAULT NULL, nombre INT NOT NULL, create_date DATETIME NOT NULL, modify_date DATETIME DEFAULT NULL, delete_date DATETIME DEFAULT NULL, delete_flag TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE SubTerm');
    }
}
