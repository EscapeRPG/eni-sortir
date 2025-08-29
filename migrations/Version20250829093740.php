<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829093740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE place CHANGE name name VARCHAR(50) DEFAULT NULL, CHANGE street street VARCHAR(100) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(20) DEFAULT NULL, CHANGE city city VARCHAR(100) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE place CHANGE name name VARCHAR(50) NOT NULL, CHANGE street street VARCHAR(100) NOT NULL, CHANGE postal_code postal_code VARCHAR(20) NOT NULL, CHANGE city city VARCHAR(100) NOT NULL, CHANGE latitude latitude DOUBLE PRECISION NOT NULL, CHANGE longitude longitude DOUBLE PRECISION NOT NULL');
    }
}
