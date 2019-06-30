<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190612155642 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE downstream (id INT AUTO_INCREMENT NOT NULL, report_id INT NOT NULL, domain VARCHAR(100) NOT NULL, percent DOUBLE PRECISION DEFAULT NULL, INDEX IDX_846B32874BD2A4C0 (report_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE downstream ADD CONSTRAINT FK_846B32874BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE related_domain ADD score SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE keyword ADD share_percent DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE downstream');
        $this->addSql('ALTER TABLE keyword DROP share_percent');
        $this->addSql('ALTER TABLE related_domain DROP score');
    }
}
