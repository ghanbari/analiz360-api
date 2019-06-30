<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190511192503 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE domain_audit (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, date DATE NOT NULL, data JSON NOT NULL, categories_score JSON NOT NULL, score DOUBLE PRECISION NOT NULL, INDEX IDX_4163D7AF115F0EE5 (domain_id), UNIQUE INDEX IDX_DOMAIN_DATE (domain_id, date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domain_audit ADD CONSTRAINT FK_4163D7AF115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('DROP TABLE proxy');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE domain ADD screenshot VARCHAR(255) DEFAULT NULL, ADD last_audit_status SMALLINT DEFAULT NULL, ADD last_audit_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE proxy (id INT AUTO_INCREMENT NOT NULL, ip VARCHAR(46) NOT NULL COLLATE utf8mb4_unicode_ci, port INT NOT NULL, country VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, secure TINYINT(1) NOT NULL, connection_count INT NOT NULL, try_count INT NOT NULL, last_connection DATETIME DEFAULT NULL, last_try DATETIME DEFAULT NULL, usage_count INT NOT NULL, ignore_till DATETIME DEFAULT NULL, usage_time INT DEFAULT 0 NOT NULL, detected_at DATETIME NOT NULL, UNIQUE INDEX unique_proxy (ip, port), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE domain_audit');
        $this->addSql('ALTER TABLE domain DROP screenshot, DROP last_audit_status, DROP last_audit_at');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
