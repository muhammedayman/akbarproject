ALTER TABLE mails ADD is_comment CHAR( 1 ) DEFAULT 'n' NOT NULL;

update settings set setting_value = '1.6.1' WHERE setting_key='dbLevel';