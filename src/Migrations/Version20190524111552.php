<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190524111552 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE related_domain (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, source VARCHAR(20) NOT NULL, status VARCHAR(10) NOT NULL, related_with VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B6566AD115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_relations (domain_source INT NOT NULL, domain_target INT NOT NULL, INDEX IDX_F21C6D3630E973BA (domain_source), INDEX IDX_F21C6D36290C2335 (domain_target), PRIMARY KEY(domain_source, domain_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE related_domain ADD CONSTRAINT FK_B6566AD115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_relations ADD CONSTRAINT FK_F21C6D3630E973BA FOREIGN KEY (domain_source) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_relations ADD CONSTRAINT FK_F21C6D36290C2335 FOREIGN KEY (domain_target) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_free_watching CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql("
            CREATE PROCEDURE `detect_new_domains`(p_from_date date, p_till_date date)
            BEGIN
              DECLARE v_domain_exists INT DEFAULT 0;
              DECLARE v_finished INT DEFAULT 0;
              DECLARE v_domain VARCHAR(100);
              DECLARE get_domains_cursor CURSOR FOR
                        SELECT un.domain
                        FROM
                        (
                            SELECT DISTINCT domain FROM `upstream` u INNER JOIN report r ON u.report_id = r.id INNER JOIN geography g ON g.report_id = r.id INNER JOIN country c ON g.country_id = c.id WHERE c.alpha2 IN ('ir', 'iq', 'af', 'tr', 'tm', 'tj', 'pk') AND r.date BETWEEN p_from_date AND p_till_date
                            UNION
                            SELECT domain FROM backlink b INNER JOIN report r ON b.report_id = r.id  INNER JOIN geography g ON g.report_id = r.id INNER JOIN country c ON g.country_id = c.id WHERE c.alpha2 IN ('ir', 'iq', 'af', 'tr', 'tm', 'tj', 'pk') AND r.date BETWEEN p_from_date AND p_till_date
                            UNION
                            SELECT related_with FROM related_domain rd INNER JOIN report r ON r.domain_id = rd.domain_id INNER JOIN geography g ON g.report_id = r.id INNER JOIN country c ON g.country_id = c.id WHERE c.alpha2 IN ('ir', 'iq', 'af', 'tr', 'tm', 'tj', 'pk') AND created_at BETWEEN p_from_date AND p_till_date
                        ) un
                        WHERE
                            un.domain NOT IN (SELECT d.domain FROM domain d);
            
                OPEN get_domains_cursor;
                    get_domains: LOOP
                        FETCH get_domains_cursor INTO v_domain;                    
                            SELECT true INTO v_domain_exists FROM domain d WHERE d.domain = v_domain;
                            IF v_domain_exists IS false THEN
                                INSERT IGNORE INTO `domain` (`name`, `domain`, `registration_date`, `status`, `details`) VALUES (v_domain, v_domain, NOW(), '-1', '[]');
                            END IF;
                    END LOOP get_domains;
              CLOSE get_domains_cursor; 
            END;
        ");

        $this->addSql("
            CREATE EVENT `detect_new_domains`
            ON SCHEDULE EVERY 1 DAY STARTS '2019-05-25 06:00:00'
            ON COMPLETION PRESERVE
            ENABLE
            DO
            CALL detect_new_domains(DATE_SUB(now(),INTERVAL 7 DAY), NOW());
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP EVENT IF EXISTS detect_new_domains');
        $this->addSql('DROP PROCEDURE IF EXISTS detect_new_domains');
        $this->addSql('DROP TABLE related_domain');
        $this->addSql('DROP TABLE domain_relations');
        $this->addSql('ALTER TABLE domain_free_watching CHANGE created_at created_at DATE NOT NULL, CHANGE updated_at updated_at DATE NOT NULL');
    }
}
