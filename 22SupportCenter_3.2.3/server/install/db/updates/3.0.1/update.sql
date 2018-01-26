ALTER TABLE custom_values ADD groupid INTEGER NULL;
update settings set setting_value = '3.0.1' WHERE setting_key='dbLevel';