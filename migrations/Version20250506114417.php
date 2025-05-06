<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506114417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fridge (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, inventory JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_F2E94D89A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fridge_ingredient (fridge_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_44D72B8514A48E59 (fridge_id), INDEX IDX_44D72B85933FE08C (ingredient_id), PRIMARY KEY(fridge_id, ingredient_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fridge ADD CONSTRAINT FK_F2E94D89A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fridge_ingredient ADD CONSTRAINT FK_44D72B8514A48E59 FOREIGN KEY (fridge_id) REFERENCES fridge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fridge_ingredient ADD CONSTRAINT FK_44D72B85933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fridge DROP FOREIGN KEY FK_F2E94D89A76ED395');
        $this->addSql('ALTER TABLE fridge_ingredient DROP FOREIGN KEY FK_44D72B8514A48E59');
        $this->addSql('ALTER TABLE fridge_ingredient DROP FOREIGN KEY FK_44D72B85933FE08C');
        $this->addSql('DROP TABLE fridge');
        $this->addSql('DROP TABLE fridge_ingredient');
    }
}
