<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120161433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE step ADD step_recipe_id INT NOT NULL');
        $this->addSql('ALTER TABLE step ADD CONSTRAINT FK_43B9FE3C84E5DBDF FOREIGN KEY (step_recipe_id) REFERENCES recipe (id)');
        $this->addSql('CREATE INDEX IDX_43B9FE3C84E5DBDF ON step (step_recipe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE step DROP FOREIGN KEY FK_43B9FE3C84E5DBDF');
        $this->addSql('DROP INDEX IDX_43B9FE3C84E5DBDF ON step');
        $this->addSql('ALTER TABLE step DROP step_recipe_id');
    }
}
