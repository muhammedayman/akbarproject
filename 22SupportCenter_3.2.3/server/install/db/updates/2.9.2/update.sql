CREATE TABLE product_installations (
    installation_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    created DATETIME NOT NULL,
    ip VARCHAR(15) NOT NULL,
    orderid VARCHAR(32),
    license VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_installations PRIMARY KEY (installation_id)
);

ALTER TABLE escalation_rules ADD COLUMN customer_cond VARCHAR(255) NULL;
ALTER TABLE escalation_rules ADD COLUMN group_cond TEXT NULL;

update settings set setting_value = '2.9.2' WHERE setting_key='dbLevel';