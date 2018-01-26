ALTER TABLE user_notifications ADD status_check CHAR( 1 ) DEFAULT 'y' NOT NULL ,
ADD queue_check CHAR( 1 ) DEFAULT 'y' NOT NULL ,
ADD priority_check CHAR( 1 ) DEFAULT 'y' NOT NULL ,
ADD owner_check CHAR( 1 ) DEFAULT 'y' NOT NULL ;

update settings set setting_value = '1.1.3' WHERE setting_key='dbLevel';
