<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260826110504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE unit (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quantity ADD unit_id INT NOT NULL, DROP unit');
        $this->addSql('ALTER TABLE quantity ADD CONSTRAINT FK_9FF31636F8BD700D FOREIGN KEY (unit_id) REFERENCES unit (id)');
        $this->addSql('CREATE INDEX IDX_9FF31636F8BD700D ON quantity (unit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quantity DROP FOREIGN KEY FK_9FF31636F8BD700D');
        $this->addSql('DROP TABLE unit');
        $this->addSql('DROP INDEX IDX_9FF31636F8BD700D ON quantity');
        $this->addSql('ALTER TABLE quantity ADD unit VARCHAR(255) NOT NULL, DROP unit_id');
    }
}
