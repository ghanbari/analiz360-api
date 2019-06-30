<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190526070644 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE related_domain DROP FOREIGN KEY FK_B6566AD115F0EE5');
        $this->addSql('ALTER TABLE related_domain ADD CONSTRAINT FK_B6566AD115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_audit DROP FOREIGN KEY FK_4163D7AF115F0EE5');
        $this->addSql('ALTER TABLE domain_audit ADD CONSTRAINT FK_4163D7AF115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain ADD score NUMERIC(6, 3) DEFAULT NULL, ADD score_updated_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE domain_free_watching DROP FOREIGN KEY FK_156CD76B115F0EE5');
        $this->addSql('ALTER TABLE domain_free_watching ADD CONSTRAINT FK_156CD76B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE domain DROP score, DROP score_updated_at');
        $this->addSql('ALTER TABLE domain_audit DROP FOREIGN KEY FK_4163D7AF115F0EE5');
        $this->addSql('ALTER TABLE domain_audit ADD CONSTRAINT FK_4163D7AF115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_free_watching DROP FOREIGN KEY FK_156CD76B115F0EE5');
        $this->addSql('ALTER TABLE domain_free_watching ADD CONSTRAINT FK_156CD76B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE related_domain DROP FOREIGN KEY FK_B6566AD115F0EE5');
        $this->addSql('ALTER TABLE related_domain ADD CONSTRAINT FK_B6566AD115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
    }
}
