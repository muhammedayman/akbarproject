ALTER TABLE products ADD send_notification CHAR(1) NOT NULL default 'n';
ALTER TABLE products ADD notification_subject VARCHAR(255);
ALTER TABLE products ADD notification_body TEXT;

update settings set setting_value = '2.9.1' WHERE setting_key='dbLevel';