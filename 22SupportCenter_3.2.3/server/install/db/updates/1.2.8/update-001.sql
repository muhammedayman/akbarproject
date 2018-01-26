ALTER TABLE mail_gateways DROP COLUMN owners;

update settings set setting_value = '1.2.8' WHERE setting_key='dbLevel';
