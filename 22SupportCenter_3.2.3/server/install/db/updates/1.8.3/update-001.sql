ALTER TABLE ticket_words ADD word_ranking FLOAT NULL;
ALTER TABLE kb_item_words ADD word_ranking FLOAT NULL ;

UPDATE ticket_words SET word_ranking = 1;
UPDATE kb_item_words SET word_ranking = 1;

ALTER TABLE words ADD kb_ranking FLOAT NULL ;
ALTER TABLE words ADD ticket_ranking FLOAT NULL ;

UPDATE words w SET kb_ranking = 1 - ((SELECT count(*) FROM kb_item_words kbw WHERE kbw.word_id = w.word_id) / (SELECT count(*) FROM kb_item_words));
UPDATE words w SET ticket_ranking = 1 - ((SELECT count(*) FROM ticket_words tw WHERE tw.word_id = w.word_id) / (SELECT count(*) FROM ticket_words));

update settings set setting_value = '1.8.3' WHERE setting_key='dbLevel';