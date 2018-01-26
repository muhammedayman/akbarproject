CREATE TABLE statuses (
    status CHAR(1) NOT NULL,
    status_name VARCHAR(32) NOT NULL,
    color VARCHAR(64),
    img VARCHAR(250),
    PRIMARY KEY (status),
    UNIQUE KEY IDX_Entity_11(status_name),
    UNIQUE KEY IDX_statuses2(status)
);


INSERT INTO statuses(status, status_name, color, img) VALUES ('n', 'New', '#ffffA0', 'status_new.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('r', 'Resolved', '#cceedd', 'status_resolved.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('s', 'Spam', 'gray', 'status_spam.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('d', 'Dead', 'gray', 'status_dead.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('w', 'Work In Progress', '#c8c8ff', 'status_work.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('b', 'Bounced', 'gray', 'status_bounced.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('a', 'Awaiting Reply', '#aaffdd', 'status_awaiting_reply.png');
INSERT INTO statuses(status, status_name, color, img) VALUES ('c', 'Customer Reply', '#ffffD0', 'status_customer_reply.png');

UPDATE priorities SET priority=1 WHERE priority = 0;
UPDATE tickets SET priority=1 WHERE priority = 0;

ALTER TABLE parsing_rules ADD priobackup CHAR(1);
UPDATE parsing_rules SET priobackup = change_to_prio;
ALTER TABLE parsing_rules DROP COLUMN change_to_prio;
ALTER TABLE parsing_rules ADD COLUMN change_to_prio TINYINT UNSIGNED;
UPDATE parsing_rules SET change_to_prio=1 WHERE priobackup = 'l';
UPDATE parsing_rules SET change_to_prio=50 WHERE priobackup = 'n';
UPDATE parsing_rules SET change_to_prio=100 WHERE priobackup = 'h';
UPDATE parsing_rules SET change_to_prio=250 WHERE priobackup = 'i';
ALTER TABLE parsing_rules DROP COLUMN priobackup;



ALTER TABLE ticket_changes ADD priobackup CHAR(1);
UPDATE ticket_changes SET priobackup = new_priority;
ALTER TABLE ticket_changes DROP COLUMN new_priority;
ALTER TABLE ticket_changes ADD COLUMN new_priority TINYINT UNSIGNED;
UPDATE ticket_changes SET new_priority=1 WHERE priobackup = 'l';
UPDATE ticket_changes SET new_priority=50 WHERE priobackup = 'n';
UPDATE ticket_changes SET new_priority=100 WHERE priobackup = 'h';
UPDATE ticket_changes SET new_priority=250 WHERE priobackup = 'i';
ALTER TABLE ticket_changes DROP COLUMN priobackup;




update settings set setting_value = '1.6.4' WHERE setting_key='dbLevel';