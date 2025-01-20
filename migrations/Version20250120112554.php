<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120112554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ingredient_step (ingredient_id INT NOT NULL, step_id INT NOT NULL, INDEX IDX_1DE84EAA933FE08C (ingredient_id), INDEX IDX_1DE84EAA73B21E9C (step_id), PRIMARY KEY(ingredient_id, step_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_user (recipe_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F2888C9659D8A214 (recipe_id), INDEX IDX_F2888C96A76ED395 (user_id), PRIMARY KEY(recipe_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_step (recipe_id INT NOT NULL, step_id INT NOT NULL, INDEX IDX_3CA2A4E359D8A214 (recipe_id), INDEX IDX_3CA2A4E373B21E9C (step_id), PRIMARY KEY(recipe_id, step_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_ingredient (recipe_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_22D1FE1359D8A214 (recipe_id), INDEX IDX_22D1FE13933FE08C (ingredient_id), PRIMARY KEY(recipe_id, ingredient_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ingredient_step ADD CONSTRAINT FK_1DE84EAA933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ingredient_step ADD CONSTRAINT FK_1DE84EAA73B21E9C FOREIGN KEY (step_id) REFERENCES step (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_user ADD CONSTRAINT FK_F2888C9659D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_user ADD CONSTRAINT FK_F2888C96A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_step ADD CONSTRAINT FK_3CA2A4E359D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_step ADD CONSTRAINT FK_3CA2A4E373B21E9C FOREIGN KEY (step_id) REFERENCES step (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE1359D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE13933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ingredient_step DROP FOREIGN KEY FK_1DE84EAA933FE08C');
        $this->addSql('ALTER TABLE ingredient_step DROP FOREIGN KEY FK_1DE84EAA73B21E9C');
        $this->addSql('ALTER TABLE recipe_user DROP FOREIGN KEY FK_F2888C9659D8A214');
        $this->addSql('ALTER TABLE recipe_user DROP FOREIGN KEY FK_F2888C96A76ED395');
        $this->addSql('ALTER TABLE recipe_step DROP FOREIGN KEY FK_3CA2A4E359D8A214');
        $this->addSql('ALTER TABLE recipe_step DROP FOREIGN KEY FK_3CA2A4E373B21E9C');
        $this->addSql('ALTER TABLE recipe_ingredient DROP FOREIGN KEY FK_22D1FE1359D8A214');
        $this->addSql('ALTER TABLE recipe_ingredient DROP FOREIGN KEY FK_22D1FE13933FE08C');
        $this->addSql('DROP TABLE ingredient_step');
        $this->addSql('DROP TABLE recipe_user');
        $this->addSql('DROP TABLE recipe_step');
        $this->addSql('DROP TABLE recipe_ingredient');
    }
}
