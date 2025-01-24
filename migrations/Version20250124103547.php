<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250124103547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE step_operation (id INT AUTO_INCREMENT NOT NULL, step_id INT NOT NULL, operation_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_C4BCDFA673B21E9C (step_id), INDEX IDX_C4BCDFA644AC3583 (operation_id), INDEX IDX_C4BCDFA6933FE08C (ingredient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE step_operation ADD CONSTRAINT FK_C4BCDFA673B21E9C FOREIGN KEY (step_id) REFERENCES step (id)');
        $this->addSql('ALTER TABLE step_operation ADD CONSTRAINT FK_C4BCDFA644AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('ALTER TABLE step_operation ADD CONSTRAINT FK_C4BCDFA6933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE ingredient_step DROP FOREIGN KEY FK_1DE84EAA73B21E9C');
        $this->addSql('ALTER TABLE ingredient_step DROP FOREIGN KEY FK_1DE84EAA933FE08C');
        $this->addSql('ALTER TABLE operation_ingredient DROP FOREIGN KEY FK_253817AD44AC3583');
        $this->addSql('ALTER TABLE operation_ingredient DROP FOREIGN KEY FK_253817AD933FE08C');
        $this->addSql('DROP TABLE ingredient_step');
        $this->addSql('DROP TABLE operation_ingredient');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ingredient_step (ingredient_id INT NOT NULL, step_id INT NOT NULL, INDEX IDX_1DE84EAA933FE08C (ingredient_id), INDEX IDX_1DE84EAA73B21E9C (step_id), PRIMARY KEY(ingredient_id, step_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE operation_ingredient (id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, ingredient_id INT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_253817AD44AC3583 (operation_id), INDEX IDX_253817AD933FE08C (ingredient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ingredient_step ADD CONSTRAINT FK_1DE84EAA73B21E9C FOREIGN KEY (step_id) REFERENCES step (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ingredient_step ADD CONSTRAINT FK_1DE84EAA933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE operation_ingredient ADD CONSTRAINT FK_253817AD44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('ALTER TABLE operation_ingredient ADD CONSTRAINT FK_253817AD933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE step_operation DROP FOREIGN KEY FK_C4BCDFA673B21E9C');
        $this->addSql('ALTER TABLE step_operation DROP FOREIGN KEY FK_C4BCDFA644AC3583');
        $this->addSql('ALTER TABLE step_operation DROP FOREIGN KEY FK_C4BCDFA6933FE08C');
        $this->addSql('DROP TABLE step_operation');
    }
}
