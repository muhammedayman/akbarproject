ALTER TABLE notifications ADD call_url TEXT NULL ;

update settings set setting_value = '2.3.1' WHERE setting_key='dbLevel';