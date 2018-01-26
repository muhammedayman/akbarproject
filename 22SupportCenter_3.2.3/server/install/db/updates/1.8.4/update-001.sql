ALTER TABLE files ADD downloads INT NULL DEFAULT 0;
update settings set setting_value = '1.8.4' WHERE setting_key='dbLevel';