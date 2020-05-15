<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200515143939 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE SubTerm CHANGE index_add_letter index_add_letter LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE MainTerm CHANGE index_add_letter index_add_letter LONGTEXT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE MainTerm CHANGE index_add_letter index_add_letter VARCHAR(4) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE SubTerm CHANGE index_add_letter index_add_letter VARCHAR(4) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
