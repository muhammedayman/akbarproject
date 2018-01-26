ALTER TABLE files ADD contentid VARCHAR(255) NULL;

update settings set setting_value = '2.6.3' WHERE setting_key='dbLevel';