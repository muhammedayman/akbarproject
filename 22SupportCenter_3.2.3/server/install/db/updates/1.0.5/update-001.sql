DROP TABLE IF EXISTS ticket_comments;
ALTER TABLE tickets ADD mails_count MEDIUMINT NOT NULL ;
UPDATE tickets t SET mails_count=(SELECT count(*) from mails m WHERE m.ticket_id = t.ticket_id);

update settings set setting_value = '1.0.5' WHERE setting_key='dbLevel';