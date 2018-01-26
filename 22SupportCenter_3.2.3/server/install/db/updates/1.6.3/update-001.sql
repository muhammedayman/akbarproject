CREATE TABLE priorities (
    priority TINYINT UNSIGNED NOT NULL,
    priority_name VARCHAR(32) NOT NULL,
    PRIMARY KEY (priority),
    UNIQUE KEY IDX_Entity_11(priority_name)
);

INSERT INTO priorities(priority, priority_name) VALUES (1, 'Low');
INSERT INTO priorities(priority, priority_name) VALUES (50, 'Normal');
INSERT INTO priorities(priority, priority_name) VALUES (100, 'High');
INSERT INTO priorities(priority, priority_name) VALUES (250, 'Immediate');

INSERT INTO settings ( setting_id, setting_key , user_id , setting_value ) VALUES ( MD5( 'defaultPriority' ) , 'defaultPriority', NULL , '50');

ALTER TABLE tickets ADD priobackup CHAR(1);

UPDATE tickets SET priobackup = priority;

ALTER TABLE tickets DROP COLUMN priority;

ALTER TABLE tickets ADD COLUMN priority TINYINT UNSIGNED ZEROFILL NOT NULL DEFAULT 0;

UPDATE tickets SET priority=1 WHERE priobackup = 'l';
UPDATE tickets SET priority=50 WHERE priobackup = 'n';
UPDATE tickets SET priority=100 WHERE priobackup = 'h';
UPDATE tickets SET priority=250 WHERE priobackup = 'i';

ALTER TABLE tickets DROP COLUMN priobackup;

update settings set setting_value = '1.6.3' WHERE setting_key='dbLevel';