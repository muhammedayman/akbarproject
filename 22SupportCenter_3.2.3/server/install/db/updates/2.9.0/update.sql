ALTER TABLE users ADD hide_suggestions CHAR(1) NOT NULL default 'n';
update settings set setting_value = '2.9.0' WHERE setting_key='dbLevel';