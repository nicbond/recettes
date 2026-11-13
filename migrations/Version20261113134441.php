<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113134441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create address table and associate it with consumers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE address (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', consumer_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', street VARCHAR(50) NOT NULL, complement VARCHAR(50) DEFAULT NULL, postal_code VARCHAR(5) NOT NULL, city VARCHAR(40) NOT NULL, country_code VARCHAR(2) NOT NULL, phone_number VARCHAR(10) DEFAULT NULL, extra_information VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_D4E6F8137FDBD6D (consumer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8137FDBD6D FOREIGN KEY (consumer_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8137FDBD6D');
        $this->addSql('DROP TABLE address');
    } 
}
