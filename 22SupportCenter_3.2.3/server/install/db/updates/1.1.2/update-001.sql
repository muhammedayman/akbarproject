CREATE TABLE user_notifications (
    user_id VARCHAR(32) NOT NULL,
    is_active CHAR(1),
    ticket_owners TEXT,
    to_statuses VARCHAR(255),
    to_queues TEXT,
    to_priorities VARCHAR(255),
    to_owners TEXT,
    subject VARCHAR(255),
    body TEXT,
    PRIMARY KEY (user_id)
);

update settings set setting_value = '1.1.2' WHERE setting_key='dbLevel';
