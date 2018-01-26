ALTER TABLE filters DROP INDEX IDX_filters1;
ALTER TABLE filters ADD UNIQUE IDX_filters1 ( filter_name , user_id );  
update settings set setting_value = '1.7.4' WHERE setting_key='dbLevel';