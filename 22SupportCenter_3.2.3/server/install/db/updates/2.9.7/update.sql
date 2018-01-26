ALTER  TABLE  kb_items  CHANGE  body  body LONGTEXT;
update settings set setting_value = '2.9.7' WHERE setting_key='dbLevel';