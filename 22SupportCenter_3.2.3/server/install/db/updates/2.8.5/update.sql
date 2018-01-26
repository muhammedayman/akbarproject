CREATE TABLE products (
    productid VARCHAR(32) NOT NULL,
    name VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    created DATETIME NOT NULL,
    max_downloads MEDIUMINT NOT NULL DEFAULT 0,
    valid_days MEDIUMINT NOT NULL DEFAULT 0,
    product_code VARCHAR(255) NOT NULL,
    is_enabled CHAR(1) NOT NULL DEFAULT 'y',
    tree_path TEXT NOT NULL,
    CONSTRAINT PK_products PRIMARY KEY (productid)
);

CREATE TABLE product_files (
    file_id VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_files PRIMARY KEY (file_id, productid)
);

CREATE TABLE product_orders (
    orderid VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    groupid INTEGER UNSIGNED,
    user_id VARCHAR(32),
    valid_from DATETIME NOT NULL,
    valid_until DATETIME,
    max_downloads INTEGER NOT NULL DEFAULT 0,
    licenseid VARCHAR(255),
    order_number VARCHAR(255),
    order_info TEXT,
    price FLOAT,
    created DATETIME NOT NULL,
    CONSTRAINT PK_product_orders PRIMARY KEY (orderid)
);

CREATE TABLE product_bundles (
    bundled_productid VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_bundles PRIMARY KEY (bundled_productid, productid)
);

CREATE TABLE product_downloads (
    downloadid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    file_id VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    orderid VARCHAR(32) NOT NULL,
    created DATETIME NOT NULL,
    ip VARCHAR(15) NOT NULL,
    CONSTRAINT PK_product_downloads PRIMARY KEY (downloadid)
);


ALTER TABLE files ADD etag VARCHAR(32) NULL;

update settings set setting_value = '2.8.5' WHERE setting_key='dbLevel';