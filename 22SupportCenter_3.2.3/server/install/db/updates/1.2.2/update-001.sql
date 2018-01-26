ALTER TABLE queues ADD opened_for_users CHAR( 1 ) DEFAULT 'n' NOT NULL ;
update settings set setting_value = '1.2.2' WHERE setting_key='dbLevel';