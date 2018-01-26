ALTER TABLE parsing_rules ADD cc_addr_cond VARCHAR(16) NULL;
ALTER TABLE parsing_rules ADD cc_addr_value VARCHAR(250) NULL;

ALTER TABLE parsing_rules ADD group_cond VARCHAR(16) NULL;
ALTER TABLE parsing_rules ADD group_id INTEGER ZEROFILL;


update settings set setting_value = '2.7.0' WHERE setting_key='dbLevel';