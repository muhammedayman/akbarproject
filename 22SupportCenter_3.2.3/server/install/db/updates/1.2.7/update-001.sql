drop table IF EXISTS user_notifications;

CREATE TABLE user_notifications (
    notification_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(128) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    ticket_owners TEXT,
    to_statuses VARCHAR(255),
    to_queues TEXT,
    to_priorities VARCHAR(255),
    to_owners TEXT,
    subject VARCHAR(255),
    body TEXT,
    status_check CHAR(1) NOT NULL,
    priority_check CHAR(1) NOT NULL,
    queue_check CHAR(1) NOT NULL,
    owner_check CHAR(1) NOT NULL,
    PRIMARY KEY (notification_id),
    KEY IDX_user_notifications1(user_id)
);

CREATE TABLE mail_gateways (
    gateway_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(128) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    ticket_owners TEXT,
    statuses VARCHAR(255),
    queues TEXT,
    priorities VARCHAR(255),
    owners TEXT,
    PRIMARY KEY (gateway_id),
    KEY IDX_mail_gateways1(user_id)
);


update settings set setting_value = '1.2.7' WHERE setting_key='dbLevel';
