#ALTER TABLE mails DROP INDEX IDX_mails1;
#ALTER TABLE mails DROP INDEX unique_msg_id;
ALTER TABLE mails ADD INDEX IDX_mails1 ( unique_msg_id , account_id );

update settings set setting_value = '2.2.1' WHERE setting_key='dbLevel';