CREATE TABLE custom_fields (
    field_id VARCHAR(64) NOT NULL,
    field_title VARCHAR(255) NOT NULL,
    field_type CHAR(1) NOT NULL,
    default_value TEXT,
    options TEXT,
    related_to CHAR(1) NOT NULL,
    order_value INTEGER,
    user_access CHAR(1) NOT NULL,
    PRIMARY KEY (field_id),
    UNIQUE KEY IDX_custom_fields1(field_id)
);

CREATE TABLE custom_values (
    value_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    field_id VARCHAR(64) NOT NULL,
    ticket_id INTEGER UNSIGNED,
    user_id VARCHAR(32),
    field_value TEXT,
    PRIMARY KEY (value_id),
    KEY IDX_custom_values1(field_id),
    KEY IDX_custom_values2(ticket_id),
    KEY IDX_custom_values3(user_id)
);

ALTER TABLE mail_accounts ADD from_name_format CHAR( 1 ) DEFAULT 'a' NOT NULL ;

UPDATE mail_accounts SET from_name_format = 'c' WHERE LENGTH(from_name) > 0;

update settings set setting_value = '1.7.1' WHERE setting_key='dbLevel';