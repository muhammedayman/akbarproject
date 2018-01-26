ALTER TABLE user_notifications ADD sendto_notif_owner CHAR( 1 ) DEFAULT 'y' NOT NULL ,
ADD sendto_customer CHAR( 1 ) DEFAULT 'n' NOT NULL ,
ADD sendto_agent_owner CHAR( 1 ) DEFAULT 'n' NOT NULL ,
ADD sendto_recipients TEXT;

ALTER TABLE user_notifications RENAME notifications;

ALTER TABLE mails DROP INDEX IDX_mails1;
ALTER TABLE mails ADD INDEX ( unique_msg_id ) ;

update settings set setting_value = '1.6.5' WHERE setting_key='dbLevel';