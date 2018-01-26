DELETE FROM file_contents WHERE file_id not in (select file_id from files);
update settings set setting_value = '3.1.7' WHERE setting_key='dbLevel';