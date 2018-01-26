ALTER TABLE mails ADD created_date DATE;
UPDATE mails SET created_date = created;
CREATE INDEX IDX_mails_5 ON mails (created_date);
update settings set setting_value = '3.0.0' WHERE setting_key='dbLevel';