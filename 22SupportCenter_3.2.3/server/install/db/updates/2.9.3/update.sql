ALTER TABLE users ADD COLUMN disable_dm_notifications char(1) NOT NULL default 'n';

update settings set setting_value = '2.9.3' WHERE setting_key='dbLevel';