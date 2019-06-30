<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190528072920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX IDX_REPORT_COUNTRY ON geography (report_id, country_id)');
        $this->addSql('ALTER TABLE domain ADD last_report_quality SMALLINT DEFAULT NULL, CHANGE score score NUMERIC(6, 3) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_DOMAIN ON domain (domain)');
        $this->addSql('CREATE INDEX IDX_STATUS ON domain (status)');
        $this->addSql('CREATE INDEX IDX_DOMAIN_STATUS ON domain (domain, status)');
        $this->addSql('CREATE INDEX IDX_LAST_REPORT ON domain (last_report_status, last_report_at)');
        $this->addSql('CREATE INDEX IDX_LAST_REPORT_STATUS ON domain (last_report_status, last_report_at, status)');
        $this->addSql('CREATE INDEX IDX_LAST_AUDIT ON domain (last_audit_status, last_audit_at)');
        $this->addSql('CREATE INDEX IDX_SCORE ON domain (score, score_updated_at)');
        $this->addSql('CREATE INDEX IDX_LAST_REPORT_QUALITY ON domain (last_report_quality)');
        $this->addSql('CREATE INDEX IDX_ORDER_BY ON domain (last_report_status, last_report_quality, score)');
        $this->addSql('CREATE INDEX IDX_ALPHA_2 ON country (alpha2)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_ALPHA_2 ON country');
        $this->addSql('DROP INDEX IDX_DOMAIN ON domain');
        $this->addSql('DROP INDEX IDX_STATUS ON domain');
        $this->addSql('DROP INDEX IDX_DOMAIN_STATUS ON domain');
        $this->addSql('DROP INDEX IDX_LAST_REPORT ON domain');
        $this->addSql('DROP INDEX IDX_LAST_REPORT_STATUS ON domain');
        $this->addSql('DROP INDEX IDX_LAST_AUDIT ON domain');
        $this->addSql('DROP INDEX IDX_SCORE ON domain');
        $this->addSql('DROP INDEX IDX_LAST_REPORT_QUALITY ON domain');
        $this->addSql('DROP INDEX IDX_ORDER_BY ON domain');
        $this->addSql('ALTER TABLE domain DROP last_report_quality, CHANGE score score NUMERIC(5, 3) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_REPORT_COUNTRY ON geography');
    }
}
