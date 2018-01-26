ALTER TABLE kb_items DROP comments;
DROP TABLE IF EXISTS kb_categories;
DROP TABLE IF EXISTS kb_category_items;

update settings set setting_value = '1.8.2' WHERE setting_key='dbLevel';