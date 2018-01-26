CREATE TABLE signatures (
    user_id VARCHAR(32) NOT NULL,
    queue_id VARCHAR(32) NOT NULL,
    signature TEXT,
    PRIMARY KEY (user_id, queue_id),
    KEY IDX_signatures1(user_id),
    KEY IDX_signatures2(queue_id)
);

update settings set setting_value = '2.4.0' WHERE setting_key='dbLevel';