<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190504123139 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, voucher_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, info JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), INDEX IDX_F52993984584665A (product_id), INDEX IDX_F529939828AA1B6F (voucher_id), INDEX IDX_F5299398DE12AB56 (created_by), INDEX IDX_F529939816FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scheduled_message (id INT AUTO_INCREMENT NOT NULL, template_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, arguments JSON NOT NULL, message VARCHAR(500) DEFAULT NULL, max_usage_count INT DEFAULT NULL, usage_count INT NOT NULL, start_at DATETIME DEFAULT NULL, expire_at DATETIME DEFAULT NULL, expired TINYINT(1) NOT NULL, date_type SMALLINT NOT NULL, dates JSON NOT NULL, receptors JSON DEFAULT NULL, max_try_count SMALLINT NOT NULL, timeout SMALLINT NOT NULL, priority SMALLINT NOT NULL, provider VARCHAR(50) DEFAULT NULL, sender_email VARCHAR(50) DEFAULT NULL, message_type VARCHAR(10) NOT NULL, last_usage_time DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D27A663B5DA0FB8 (template_id), INDEX IDX_D27A663BDE12AB56 (created_by), INDEX IDX_D27A663B16FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scheduled_message_user (scheduled_message_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F262CD01CD089B90 (scheduled_message_id), INDEX IDX_F262CD01A76ED395 (user_id), PRIMARY KEY(scheduled_message_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, province_id INT NOT NULL, county_id INT NOT NULL, name VARCHAR(50) NOT NULL, INDEX IDX_2D5B0234E946114A (province_id), INDEX IDX_2D5B023485E73F45 (county_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE province (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_verify (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, owner_id INT NOT NULL, secret VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CE46268A115F0EE5 (domain_id), INDEX IDX_CE46268A7E3C61F9 (owner_id), UNIQUE INDEX UNIQUE_REQUEST (domain_id, secret), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE county (id INT AUTO_INCREMENT NOT NULL, province_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, INDEX IDX_58E2FF25E946114A (province_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_message (id INT AUTO_INCREMENT NOT NULL, template_id INT NOT NULL, scheduled_message_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, arguments JSON NOT NULL, time DATETIME NOT NULL, receptor VARCHAR(255) NOT NULL, priority SMALLINT NOT NULL, sender_email VARCHAR(50) DEFAULT NULL, status SMALLINT NOT NULL, sensitive_data TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B7D58B05DA0FB8 (template_id), INDEX IDX_B7D58B0CD089B90 (scheduled_message_id), INDEX IDX_B7D58B0DE12AB56 (created_by), INDEX IDX_B7D58B016FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sms_outbox (id INT AUTO_INCREMENT NOT NULL, message_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, status SMALLINT NOT NULL, send_time DATETIME NOT NULL, tracking_code VARCHAR(25) DEFAULT NULL, cost SMALLINT DEFAULT NULL, status_check_count SMALLINT NOT NULL, sender VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_7FBF4BE0537A1329 (message_id), INDEX IDX_7FBF4BE0DE12AB56 (created_by), INDEX IDX_7FBF4BE016FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, path VARCHAR(3000) DEFAULT NULL, title VARCHAR(64) NOT NULL, lvl INT DEFAULT NULL, hash VARCHAR(255) DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, content_url VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6A2CA10CDE12AB56 (created_by), INDEX IDX_6A2CA10C16FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_free_watching (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, watcher_id INT NOT NULL, created_at DATE NOT NULL, updated_at DATE NOT NULL, INDEX IDX_156CD76B115F0EE5 (domain_id), INDEX IDX_156CD76BC300AB5D (watcher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, template LONGTEXT NOT NULL, parameters JSON NOT NULL, locked TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_9C0600CA5E237E06 (name), INDEX IDX_9C0600CADE12AB56 (created_by), INDEX IDX_9C0600CA16FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wallet (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, voucher_id INT DEFAULT NULL, order_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, amount INT NOT NULL, unit SMALLINT NOT NULL, type SMALLINT NOT NULL, info JSON NOT NULL, description VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_7C68921F7E3C61F9 (owner_id), INDEX IDX_7C68921F28AA1B6F (voucher_id), INDEX IDX_7C68921F8D9F6D38 (order_id), INDEX IDX_7C68921FDE12AB56 (created_by), INDEX IDX_7C68921F16FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE one_time_password (id INT AUTO_INCREMENT NOT NULL, receptor VARCHAR(255) NOT NULL, token VARCHAR(10) NOT NULL, requested_at DATETIME NOT NULL, ip VARCHAR(50) NOT NULL, try_count SMALLINT NOT NULL, is_valid TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voucher (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(10) NOT NULL, percent SMALLINT NOT NULL, max_usage INT NOT NULL, max_usage_per_user SMALLINT NOT NULL, usable_from DATE DEFAULT NULL, usable_till DATE DEFAULT NULL, product_type SMALLINT DEFAULT NULL, INDEX IDX_1392A5D84584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, price INT NOT NULL, unit SMALLINT NOT NULL, type SMALLINT NOT NULL, title VARCHAR(255) NOT NULL, visible_from DATETIME DEFAULT NULL, visible_till DATETIME DEFAULT NULL, service JSON NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D34A04AD3DA5256D (image_id), INDEX IDX_D34A04ADDE12AB56 (created_by), INDEX IDX_D34A04AD16FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domain_watcher (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, watcher_id INT NOT NULL, product_id INT NOT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, expire_at DATETIME NOT NULL, history SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5058FFB4115F0EE5 (domain_id), INDEX IDX_5058FFB4C300AB5D (watcher_id), INDEX IDX_5058FFB44584665A (product_id), INDEX IDX_5058FFB4DE12AB56 (created_by), INDEX IDX_5058FFB416FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sms_message (id INT AUTO_INCREMENT NOT NULL, scheduled_message_id INT DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, time DATETIME NOT NULL, message VARCHAR(500) NOT NULL, receptor VARCHAR(12) NOT NULL, max_try_count SMALLINT NOT NULL, timeout SMALLINT NOT NULL, priority SMALLINT NOT NULL, provider VARCHAR(50) DEFAULT NULL, status SMALLINT NOT NULL, sensitive_data TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_46A7FBA5CD089B90 (scheduled_message_id), INDEX IDX_46A7FBA5DE12AB56 (created_by), INDEX IDX_46A7FBA516FE72E1 (updated_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939828AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939816FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE scheduled_message ADD CONSTRAINT FK_D27A663B5DA0FB8 FOREIGN KEY (template_id) REFERENCES email_template (id)');
        $this->addSql('ALTER TABLE scheduled_message ADD CONSTRAINT FK_D27A663BDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE scheduled_message ADD CONSTRAINT FK_D27A663B16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE scheduled_message_user ADD CONSTRAINT FK_F262CD01CD089B90 FOREIGN KEY (scheduled_message_id) REFERENCES scheduled_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE scheduled_message_user ADD CONSTRAINT FK_F262CD01A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B0234E946114A FOREIGN KEY (province_id) REFERENCES province (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B023485E73F45 FOREIGN KEY (county_id) REFERENCES county (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domain_verify ADD CONSTRAINT FK_CE46268A115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_verify ADD CONSTRAINT FK_CE46268A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE county ADD CONSTRAINT FK_58E2FF25E946114A FOREIGN KEY (province_id) REFERENCES province (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_message ADD CONSTRAINT FK_B7D58B05DA0FB8 FOREIGN KEY (template_id) REFERENCES email_template (id)');
        $this->addSql('ALTER TABLE email_message ADD CONSTRAINT FK_B7D58B0CD089B90 FOREIGN KEY (scheduled_message_id) REFERENCES scheduled_message (id)');
        $this->addSql('ALTER TABLE email_message ADD CONSTRAINT FK_B7D58B0DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE email_message ADD CONSTRAINT FK_B7D58B016FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sms_outbox ADD CONSTRAINT FK_7FBF4BE0537A1329 FOREIGN KEY (message_id) REFERENCES sms_message (id)');
        $this->addSql('ALTER TABLE sms_outbox ADD CONSTRAINT FK_7FBF4BE0DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sms_outbox ADD CONSTRAINT FK_7FBF4BE016FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE domain_free_watching ADD CONSTRAINT FK_156CD76B115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_free_watching ADD CONSTRAINT FK_156CD76BC300AB5D FOREIGN KEY (watcher_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CADE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE email_template ADD CONSTRAINT FK_9C0600CA16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F28AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921FDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D84584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD3DA5256D FOREIGN KEY (image_id) REFERENCES media (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE domain_watcher ADD CONSTRAINT FK_5058FFB4115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id)');
        $this->addSql('ALTER TABLE domain_watcher ADD CONSTRAINT FK_5058FFB4C300AB5D FOREIGN KEY (watcher_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE domain_watcher ADD CONSTRAINT FK_5058FFB44584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE domain_watcher ADD CONSTRAINT FK_5058FFB4DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE domain_watcher ADD CONSTRAINT FK_5058FFB416FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sms_message ADD CONSTRAINT FK_46A7FBA5CD089B90 FOREIGN KEY (scheduled_message_id) REFERENCES scheduled_message (id)');
        $this->addSql('ALTER TABLE sms_message ADD CONSTRAINT FK_46A7FBA5DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sms_message ADD CONSTRAINT FK_46A7FBA516FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('DROP TABLE register_request');
        $this->addSql('ALTER TABLE user ADD city_id INT DEFAULT NULL, ADD referrer_id INT DEFAULT NULL, ADD created_by INT DEFAULT NULL, ADD updated_by INT DEFAULT NULL, ADD username VARCHAR(100) NOT NULL, ADD status SMALLINT NOT NULL, ADD code VARCHAR(100) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD created_at DATETIME DEFAULT now() NOT NULL, ADD credit INT DEFAULT 0 NOT NULL, ADD updated_at DATETIME DEFAULT now() NOT NULL, DROP wrong_password_count, CHANGE phone phone VARCHAR(12) DEFAULT NULL, CHANGE first_name first_name VARCHAR(255) NOT NULL, CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE sex sex VARCHAR(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649798C22DB FOREIGN KEY (referrer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649DE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64916FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D6498BAC62AF ON user (city_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649798C22DB ON user (referrer_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649DE12AB56 ON user (created_by)');
        $this->addSql('CREATE INDEX IDX_8D93D64916FE72E1 ON user (updated_by)');
        $this->addSql('ALTER TABLE domain ADD owner_id INT DEFAULT NULL, ADD category_id INT DEFAULT NULL, ADD province_id INT DEFAULT NULL, ADD secure TINYINT(1) DEFAULT NULL, ADD details JSON NOT NULL, CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0BE946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('CREATE INDEX IDX_A7A91E0B7E3C61F9 ON domain (owner_id)');
        $this->addSql('CREATE INDEX IDX_A7A91E0B12469DE2 ON domain (category_id)');
        $this->addSql('CREATE INDEX IDX_A7A91E0BE946114A ON domain (province_id)');
        $this->addSql('CREATE INDEX IDX_DATE ON report (date)');
        $this->addSql('CREATE INDEX IDX_GLOBAL_RANK ON report (global_rank)');
        $this->addSql('CREATE UNIQUE INDEX IDX_DOMAIN_DATE ON report (domain_id, date)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY FK_7C68921F8D9F6D38');
        $this->addSql('ALTER TABLE scheduled_message_user DROP FOREIGN KEY FK_F262CD01CD089B90');
        $this->addSql('ALTER TABLE email_message DROP FOREIGN KEY FK_B7D58B0CD089B90');
        $this->addSql('ALTER TABLE sms_message DROP FOREIGN KEY FK_46A7FBA5CD089B90');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6498BAC62AF');
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B0234E946114A');
        $this->addSql('ALTER TABLE county DROP FOREIGN KEY FK_58E2FF25E946114A');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0BE946114A');
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B023485E73F45');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B12469DE2');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD3DA5256D');
        $this->addSql('ALTER TABLE scheduled_message DROP FOREIGN KEY FK_D27A663B5DA0FB8');
        $this->addSql('ALTER TABLE email_message DROP FOREIGN KEY FK_B7D58B05DA0FB8');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939828AA1B6F');
        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY FK_7C68921F28AA1B6F');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D84584665A');
        $this->addSql('ALTER TABLE domain_watcher DROP FOREIGN KEY FK_5058FFB44584665A');
        $this->addSql('ALTER TABLE sms_outbox DROP FOREIGN KEY FK_7FBF4BE0537A1329');
        $this->addSql('CREATE TABLE register_request (id INT AUTO_INCREMENT NOT NULL, phone VARCHAR(12) NOT NULL COLLATE utf8mb4_unicode_ci, token VARCHAR(10) NOT NULL COLLATE utf8mb4_unicode_ci, requested_at DATETIME NOT NULL, ip VARCHAR(50) NOT NULL COLLATE utf8mb4_unicode_ci, try_count SMALLINT NOT NULL, is_valid TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE scheduled_message');
        $this->addSql('DROP TABLE scheduled_message_user');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE province');
        $this->addSql('DROP TABLE domain_verify');
        $this->addSql('DROP TABLE county');
        $this->addSql('DROP TABLE email_message');
        $this->addSql('DROP TABLE sms_outbox');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE domain_free_watching');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE wallet');
        $this->addSql('DROP TABLE one_time_password');
        $this->addSql('DROP TABLE voucher');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE domain_watcher');
        $this->addSql('DROP TABLE sms_message');
        $this->addSql('ALTER TABLE domain DROP FOREIGN KEY FK_A7A91E0B7E3C61F9');
        $this->addSql('DROP INDEX IDX_A7A91E0B7E3C61F9 ON domain');
        $this->addSql('DROP INDEX IDX_A7A91E0B12469DE2 ON domain');
        $this->addSql('DROP INDEX IDX_A7A91E0BE946114A ON domain');
        $this->addSql('ALTER TABLE domain DROP owner_id, DROP category_id, DROP province_id, DROP secure, DROP details, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('DROP INDEX IDX_DATE ON report');
        $this->addSql('DROP INDEX IDX_GLOBAL_RANK ON report');
        $this->addSql('DROP INDEX IDX_DOMAIN_DATE ON report');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649798C22DB');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649DE12AB56');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64916FE72E1');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('DROP INDEX IDX_8D93D6498BAC62AF ON user');
        $this->addSql('DROP INDEX IDX_8D93D649798C22DB ON user');
        $this->addSql('DROP INDEX IDX_8D93D649DE12AB56 ON user');
        $this->addSql('DROP INDEX IDX_8D93D64916FE72E1 ON user');
        $this->addSql('ALTER TABLE user ADD wrong_password_count SMALLINT DEFAULT NULL, DROP city_id, DROP referrer_id, DROP created_by, DROP updated_by, DROP username, DROP status, DROP code, DROP description, DROP created_at, DROP credit, DROP updated_at, CHANGE phone phone VARCHAR(12) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE first_name first_name VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE last_name last_name VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE sex sex SMALLINT DEFAULT NULL');
    }
}
