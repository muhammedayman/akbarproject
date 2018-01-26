ALTER TABLE mail_accounts ADD smtp_tls CHAR(1) NOT NULL DEFAULT 'n';

update settings set setting_value = '2.8.3' WHERE setting_key='dbLevel';