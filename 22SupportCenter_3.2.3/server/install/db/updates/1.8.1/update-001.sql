ALTER TABLE kb_items ADD FULLTEXT IDX_kb_fulltext (subject ,metadescription ,body) ;
ALTER TABLE kb_items ADD UNIQUE IDX_kb_items2 ( tree_path ( 128 ) , url ( 128 ) ) ;
update settings set setting_value = '1.8.1' WHERE setting_key='dbLevel';