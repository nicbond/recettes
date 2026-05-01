<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260501101418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recipe ADD thumbnail_original_name VARCHAR(255) DEFAULT NULL, ADD thumbnail_mime_type VARCHAR(255) DEFAULT NULL, ADD thumbnail_size INT DEFAULT NULL, ADD thumbnail_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE thumbnail thumbnail_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recipe ADD thumbnail VARCHAR(255) DEFAULT NULL, DROP thumbnail_name, DROP thumbnail_original_name, DROP thumbnail_mime_type, DROP thumbnail_size, DROP thumbnail_dimensions');
    }
}
