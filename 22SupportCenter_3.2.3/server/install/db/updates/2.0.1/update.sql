drop table IF EXISTS ticket_words;
drop table IF EXISTS  kb_item_words;
drop table IF EXISTS  words;

ALTER TABLE mails ADD is_indexed CHAR( 1 ) NOT NULL DEFAULT 'n';
ALTER TABLE kb_items ADD is_indexed CHAR( 1 ) NOT NULL DEFAULT 'n';


CREATE TABLE words (
    word_id CHAR(32) NOT NULL,
    word VARCHAR(250) NOT NULL,
    kb_ranking FLOAT,
    ticket_ranking FLOAT,
    PRIMARY KEY (word_id)
);

CREATE TABLE ticket_words (
    ticket_id INTEGER UNSIGNED NOT NULL,
    word_id CHAR(32) NOT NULL,
    word_ranking FLOAT,
    PRIMARY KEY (ticket_id, word_id),
    KEY IDX_ticket_words1(word_id)
);


CREATE TABLE kb_item_words (
    item_id INTEGER UNSIGNED NOT NULL,
    word_id CHAR(32) NOT NULL,
    word_ranking FLOAT,
    PRIMARY KEY (item_id, word_id),
    KEY IDX_kb_item_words2(word_id)
);

update settings set setting_value = '2.0.1' WHERE setting_key='dbLevel';