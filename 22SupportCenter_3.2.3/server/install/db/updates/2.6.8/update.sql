ALTER TABLE queues ADD queue_signature TEXT NULL;

update settings set setting_value = '2.6.8' WHERE setting_key='dbLevel';