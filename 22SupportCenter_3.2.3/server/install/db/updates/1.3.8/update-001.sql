DROP TABLE IF EXISTS kb_items;
DROP TABLE IF EXISTS kb_comments;
DROP TABLE IF EXISTS kb_item_files;
DROP TABLE IF EXISTS kb_item_words;


CREATE TABLE kb_items (
    item_id INTEGER UNSIGNED NOT NULL,
    right_id INTEGER UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    metadescription TEXT,
    url VARCHAR(255),
    body TEXT,
    user_id VARCHAR(32) NOT NULL,
    created DATETIME,
    access_mode CHAR(1),
    parent_id INTEGER UNSIGNED,
    comments CHAR(1),
    PRIMARY KEY (item_id),
    UNIQUE KEY IDX_kb_items1(item_id)
);

CREATE TABLE kb_comments (
    comment_id INTEGER UNSIGNED NOT NULL,
    item_id INTEGER UNSIGNED,
    ip VARCHAR(15),
    user_id VARCHAR(32),
    email VARCHAR(255),
    fullname VARCHAR(128),
    ranking TINYINT,
    comment TEXT,
    created DATETIME NOT NULL,
    status CHAR(1),
    PRIMARY KEY (comment_id),
    KEY IDX_kb_comments1(item_id)
);

CREATE TABLE kb_item_files (
    item_id INTEGER UNSIGNED NOT NULL,
    file_id VARCHAR(32) NOT NULL,
    PRIMARY KEY (item_id, file_id),
    KEY IDX_kb_item_files1(item_id),
    KEY IDX_kb_item_files2(file_id)
);

CREATE TABLE kb_item_words (
    item_id INTEGER UNSIGNED NOT NULL,
    word_id INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (item_id, word_id),
    KEY IDX_kb_item_words1(item_id),
    KEY IDX_kb_item_words2(word_id)
);

update settings set setting_value = '1.3.8' WHERE setting_key='dbLevel';