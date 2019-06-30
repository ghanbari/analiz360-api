<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190613121920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            CREATE PROCEDURE `inactive_unnecessary_domains`()
            BEGIN
                DECLARE v_finished INT DEFAULT 0;
                DECLARE v_domain_id INT;
                DECLARE find_unnecessary_domains_cursor CURSOR FOR
                    SELECT DISTINCT domains.domain_id
                    FROM
                        (
                            SELECT
                                min(global_rank) AS gr,
                                domain_id
                            FROM
                                domain
                            INNER JOIN report r ON domain.id = r.domain_id
                            INNER JOIN geography g ON r.id = g.report_id
                            INNER JOIN country c ON c.id = g.country_id
                            WHERE
                                domain.`status` = 1
                            AND UPPER(SUBSTRING_INDEX(domain, '.', - 1)) NOT IN ('IR', 'IQ', 'AF', 'TR', 'TM', 'TJ', 'PK')
                            AND c.alpha2 NOT IN ('ir', 'iq', 'af', 'tr', 'tm', 'tj', 'pk')
                            GROUP BY
                                domain_id
                        ) AS domains
                    WHERE
                        domains.gr > 20000;
                OPEN find_unnecessary_domains_cursor;
                    find_unnecessary_domains: LOOP
                        FETCH find_unnecessary_domains_cursor INTO v_domain_id;
                        UPDATE domain SET `status` = 0 WHERE id = v_domain_id;
                    END LOOP find_unnecessary_domains;
                CLOSE find_unnecessary_domains_cursor; 
            END;
        ");
        $this->addSql('
            CREATE PROCEDURE `remove_inactive_domains5`()
            BEGIN
                DECLARE v_finished INT DEFAULT 0;
                DECLARE v_domain_id INT;
                DECLARE find_unnecessary_domains_cursor CURSOR FOR
                    SELECT id FROM domain WHERE `status` = 0;
                OPEN find_unnecessary_domains_cursor;
                    find_unnecessary_domains: LOOP
                        FETCH find_unnecessary_domains_cursor INTO v_domain_id;
                        DELETE FROM domain WHERE id = v_domain_id;
                    END LOOP find_unnecessary_domains;
                CLOSE find_unnecessary_domains_cursor;  
            END;
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP PROCEDURE IF EXISTS inactive_unnecessary_domains');
        $this->addSql('DROP PROCEDURE IF EXISTS remove_inactive_domains');
    }
}
