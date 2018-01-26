ALTER TABLE statuses ADD COLUMN due CHAR(1) NOT NULL DEFAULT 'n';
UPDATE statuses SET due='y' WHERE status IN ('n', 'c');

CREATE TABLE products (
    produkt_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    product_type CHAR(1) NOT NULL,
    product_code VARCHAR(64) NOT NULL,
    name VARCHAR(128) NOT NULL,
    description TEXT,
    created DATETIME NOT NULL,
    max_downloads MEDIUMINT UNSIGNED ZEROFILL,
    max_days MEDIUMINT UNSIGNED ZEROFILL,
    max_installations MEDIUMINT UNSIGNED ZEROFILL,
    notif_mail CHAR(1) NOT NULL,
    notif_subject VARCHAR(255),
    notif_body TEXT,
    PRIMARY KEY (produkt_id),
    UNIQUE KEY IDX_products1(produkt_id)
);

CREATE TABLE produkt_files (
    file_id VARCHAR(32) NOT NULL,
    produkt_id INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (file_id, produkt_id),
    KEY IDX_produkt_files1(file_id),
    KEY IDX_produkt_files2(produkt_id)
);

CREATE TABLE product_orders (
    order_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_order_id INTEGER UNSIGNED,
    produkt_id INTEGER UNSIGNED NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    created DATETIME NOT NULL,
    valid_from DATETIME,
    valid_until DATETIME,
    max_installations MEDIUMINT UNSIGNED ZEROFILL,
    max_downloads MEDIUMINT UNSIGNED ZEROFILL,
    status CHAR(1) NOT NULL,
    license_id VARCHAR(32),
    downloads INTEGER UNSIGNED ZEROFILL,
    PRIMARY KEY (order_id),
    KEY IDX_product_orders1(produkt_id),
    KEY IDX_product_orders2(user_id),
    KEY IDX_product_orders3(parent_order_id),
    UNIQUE KEY IDX_product_orders4(order_id)
);

CREATE TABLE file_downloads (
    download_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    file_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32),
    order_id INTEGER UNSIGNED,
    created DATETIME NOT NULL,
    ip VARCHAR(15),
    PRIMARY KEY (download_id),
    KEY IDX_file_downloads1(file_id),
    KEY IDX_file_downloads2(user_id),
    KEY IDX_file_downloads3(order_id)
);

CREATE TABLE products_relation (
    parent_id INTEGER UNSIGNED NOT NULL,
    produkt_id INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (parent_id, produkt_id),
    KEY IDX_products_relation1(parent_id),
    KEY IDX_products_relation2(produkt_id)
);

CREATE TABLE product_installations (
    install_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INTEGER UNSIGNED,
    install_type CHAR(1) NOT NULL,
    created DATETIME NOT NULL,
    IP VARCHAR(15),
    domain VARCHAR(254),
    PRIMARY KEY (install_id),
    KEY IDX_product_installations1(order_id)
);

update settings set setting_value = '2.2.0' WHERE setting_key='dbLevel';