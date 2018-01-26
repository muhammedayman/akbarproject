ALTER TABLE `mails` ADD `body_html` LONGTEXT NULL;
update settings set setting_value = '1.7.3' WHERE setting_key='dbLevel';