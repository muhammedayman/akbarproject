CREATE TABLE groups (
    groupid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    group_name VARCHAR(255) NOT NULL,
    PRIMARY KEY (groupid),
    UNIQUE KEY IDX_groups1(groupid)
);


ALTER TABLE users ADD groupid INTEGER NULL;

ALTER TABLE notifications ADD customer_groups TEXT NULL;

update settings set setting_value = '2.6.0' WHERE setting_key='dbLevel';