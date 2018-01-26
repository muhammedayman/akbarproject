CREATE TABLE escalation_rules (
    rule_id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    rule_order MEDIUMINT,
    queue_cond TEXT,
    owner_cond TEXT,
    status_cond VARCHAR(128),
    priority_cond TEXT,
    last_reply_cond VARCHAR(10),
    ticket_age_cond VARCHAR(10),
    answer_time_cond VARCHAR(10),
    action_queue VARCHAR(32),
    action_owner VARCHAR(32),
    action_status CHAR(1),
    action_priority TINYINT UNSIGNED ZEROFILL,
    action_delete_ticket CHAR(1),
    last_execution DATETIME,
    PRIMARY KEY (rule_id)
);

update settings set setting_value = '2.0.6' WHERE setting_key='dbLevel';