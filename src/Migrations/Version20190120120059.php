<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190120120059 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            CREATE PROCEDURE `send_sms_message_to_all`(IN `p_time` time,IN `p_message` varchar(500),IN `p_max_try_count` smallint,IN `p_timeout` smallint,IN `p_priority` smallint,IN `p_provider` smallint)
            BEGIN
                DECLARE v_sex, v_username, v_email, v_phone, v_first_name, v_last_name, v_city, v_message VARCHAR(500);
                DECLARE v_finished INT DEFAULT 0;
                DECLARE get_users_cursor CURSOR FOR
                    SELECT
                        u.username, u.email, u.phone, u.first_name, u.last_name, u.sex, c.name
                    FROM `user` u
                    LEFT JOIN city c ON u.city_id = c.id
                    WHERE u.`status` = 1;
                DECLARE CONTINUE HANDLER FOR
                    NOT FOUND SET v_finished = 1;
        
                OPEN get_users_cursor;
                get_users: LOOP
                        FETCH get_users_cursor INTO v_username, v_email, v_phone, v_first_name, v_last_name, v_sex, v_city;
                        IF v_finished = 1 THEN 
                                LEAVE get_users;
                        END IF;
        
                        IF v_phone IS NOT NULL AND v_phone != '' THEN
                                SET v_message = p_message;
                                SET v_message = REPLACE(v_message, '{username}', IFNULL(v_username,''));
                                SET v_message = REPLACE(v_message, '{email}', IFNULL(v_email,''));
                                SET v_message = REPLACE(v_message, '{phone}', IFNULL(v_phone,''));
                                SET v_message = REPLACE(v_message, '{firstName}', IFNULL(v_first_name,''));
                                SET v_message = REPLACE(v_message, '{lastName}', IFNULL(v_last_name,''));
                                SET v_message = REPLACE(v_message, '{city}', IFNULL(v_city,''));
                                SET v_message = REPLACE(v_message, '{sex}', IF(v_sex = 'f', 'زن', 'مرد'));
                                
                                INSERT INTO `sms_message` (`time`, `message`, `receptor`, `provider`, `created_at`, `updated_at`, `max_try_count`, `timeout`, `priority`, `status`)
                                    VALUES (p_time, v_message, v_phone, p_provider, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), p_max_try_count, p_timeout, p_priority, 0);
                        END IF;
        
                END LOOP get_users;
                CLOSE get_users_cursor;
        
                SELECT * FROM sms_message WHERE id = LAST_INSERT_ID();
            END;");

        $this->addSql("
            CREATE PROCEDURE `send_email_message_to_all`(IN `p_template` int, IN `p_args` JSON, IN `p_time` time, IN `p_priority` smallint, IN `p_sender_email` varchar(500))
            BEGIN
                DECLARE v_sex, v_username, v_email, v_phone, v_first_name, v_last_name, v_city VARCHAR(500);
                DECLARE v_args, v_user_obj JSON;
                DECLARE v_finished INT DEFAULT 0;
                DECLARE get_users_cursor CURSOR FOR
                    SELECT
                        u.username, u.email, u.phone, u.first_name, u.last_name, u.sex, c.name
                    FROM `user` u
                    LEFT JOIN city c ON u.city_id = c.id
                    WHERE u.`status` = 1;
                DECLARE CONTINUE HANDLER FOR
                    NOT FOUND SET v_finished = 1;
        
                OPEN get_users_cursor;
                get_users: LOOP
                        FETCH get_users_cursor INTO v_username, v_email, v_phone, v_first_name, v_last_name, v_sex, v_city;
                        IF v_finished = 1 THEN 
                                LEAVE get_users;
                        END IF;
        
                IF v_email IS NOT NULL AND v_email != '' THEN
                                SET v_user_obj = JSON_OBJECT(
                                        'user_username', IFNULL(v_username,''),
                                        'user_password', '',
                                        'user_email', IFNULL(v_email,''),
                                        'user_phone', IFNULL(v_phone,''),
                                        'user_firstName', IFNULL(v_first_name,''),
                                        'user_lastName', IFNULL(v_last_name,''),
                                        'user_sex', IF(v_sex = 'f', 'زن', 'مرد'),
                                        'user_city', IFNULL(v_city,'')
                                );
        
                                SET v_args = JSON_MERGE_PATCH(p_args, v_user_obj);
                                
                                INSERT INTO `email_message` (`template_id`, `arguments`, `time`, `receptor`, `sender_email`, `created_at`, `updated_at`, `priority`, `status`)
                                    VALUES (p_template, v_args, p_time, v_email, p_sender_email, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), p_priority, 0);
                        END IF;
        
                END LOOP get_users;
                CLOSE get_users_cursor;
        
                SELECT * FROM sms_message WHERE id = LAST_INSERT_ID();
            END;");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
    }
}
