ALTER TABLE filters ADD is_global CHAR(1) NOT NULL DEFAULT 'n';

update settings set setting_value = '2.6.7' WHERE setting_key='dbLevel';