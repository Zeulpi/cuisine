<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123105419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE operation (id INT AUTO_INCREMENT NOT NULL, operation_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE operation_ingredient (id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, ingredient_id INT NOT NULL, description VARCHAR(255) DEFAULT NULL, INDEX IDX_253817AD44AC3583 (operation_id), INDEX IDX_253817AD933FE08C (ingredient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE operation_ingredient ADD CONSTRAINT FK_253817AD44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('ALTER TABLE operation_ingredient ADD CONSTRAINT FK_253817AD933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation_ingredient DROP FOREIGN KEY FK_253817AD44AC3583');
        $this->addSql('ALTER TABLE operation_ingredient DROP FOREIGN KEY FK_253817AD933FE08C');
        $this->addSql('DROP TABLE operation');
        $this->addSql('DROP TABLE operation_ingredient');
    }
}
