ALTER TABLE kb_items DROP item_type;

update settings set setting_value = '1.6.2' WHERE setting_key='dbLevel';