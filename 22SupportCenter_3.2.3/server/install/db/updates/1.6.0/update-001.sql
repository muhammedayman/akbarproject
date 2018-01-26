DROP TABLE kb_items;
CREATE TABLE kb_items (
    item_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    tree_path VARCHAR(255),
    item_order MEDIUMINT UNSIGNED,
    subject VARCHAR(255) NOT NULL,
    metadescription TEXT,
    url VARCHAR(255),
    body TEXT,
    user_id VARCHAR(32) NOT NULL,
    created DATETIME,
    item_type CHAR(1),
    access_mode CHAR(1),
    comments CHAR(1),
    PRIMARY KEY (item_id),
    UNIQUE KEY IDX_kb_items1(item_id)
);

update settings set setting_value = '1.6.0' WHERE setting_key='dbLevel';