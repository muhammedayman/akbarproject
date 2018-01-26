DROP TABLE ticket_words;
ALTER TABLE mails DROP is_indexed;
ALTER TABLE words DROP ticket_ranking;
ALTER TABLE words CHANGE kb_ranking ranking FLOAT NULL DEFAULT NULL;

TRUNCATE TABLE words;
TRUNCATE TABLE kb_item_words;
UPDATE kb_items SET is_indexed='n';

update settings set setting_value = '2.0.5' WHERE setting_key='dbLevel';