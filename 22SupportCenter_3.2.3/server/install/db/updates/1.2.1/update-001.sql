CREATE TABLE displayed_tickets (
    user_id VARCHAR(32) NOT NULL,
    ticket_id INTEGER UNSIGNED NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (user_id, ticket_id),
    KEY IDX_displayed_tickets1(user_id),
    KEY IDX_displayed_tickets2(ticket_id)
);

CREATE TABLE kb_categories (
    category_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id INTEGER UNSIGNED,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    PRIMARY KEY (category_id),
    UNIQUE KEY IDX_kb_categories1(category_id)
);

CREATE TABLE kb_items (
    item_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    avg_ranking TINYINT,
    created DATETIME,
    item_type CHAR(1),
    access_mode CHAR(1),
    PRIMARY KEY (item_id),
    UNIQUE KEY IDX_kb_items1(item_id)
);

CREATE TABLE kb_category_items (
    category_id INTEGER UNSIGNED NOT NULL,
    item_id INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (category_id, item_id),
    KEY IDX_kb_category_items1(category_id),
    KEY IDX_kb_category_items2(item_id)
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

update settings set setting_value = '1.2.1' WHERE setting_key='dbLevel';