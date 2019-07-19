<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190719213032 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE MainTerm (id INT AUTO_INCREMENT NOT NULL, term_id INT NOT NULL, curriculum_id INT NOT NULL, header_id INT NOT NULL, print_order INT NOT NULL, main_term LONGTEXT DEFAULT NULL, red_letter TINYINT(1) DEFAULT \'0\' NOT NULL, text_frequency INT NOT NULL, center_frequency INT NOT NULL, news_exam TINYINT(1) DEFAULT \'0\' NOT NULL, delimiter VARCHAR(4) DEFAULT NULL, western_language LONGTEXT DEFAULT NULL, birth_year LONGTEXT DEFAULT NULL, kana LONGTEXT DEFAULT NULL, index_add_letter VARCHAR(4) DEFAULT NULL, index_kana LONGTEXT DEFAULT NULL, index_original LONGTEXT DEFAULT NULL, index_original_kana LONGTEXT DEFAULT NULL, index_abbreviation LONGTEXT DEFAULT NULL, nombre INT NOT NULL, term_explain LONGTEXT DEFAULT NULL, handover LONGTEXT DEFAULT NULL, illust_filename LONGTEXT DEFAULT NULL, illust_caption LONGTEXT DEFAULT NULL, illust_kana LONGTEXT DEFAULT NULL, illust_nombre INT NOT NULL, user_id VARCHAR(6) DEFAULT NULL, create_date DATETIME NOT NULL, modify_date DATETIME DEFAULT NULL, delete_date DATETIME DEFAULT NULL, delete_flag TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE MainTerm');
    }
}
