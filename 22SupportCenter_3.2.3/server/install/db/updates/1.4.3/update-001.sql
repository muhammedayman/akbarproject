ALTER TABLE mail_accounts ADD from_name VARCHAR( 255 );

update settings set setting_value = '1.4.3' WHERE setting_key='dbLevel';