drop table products_relation;
drop table product_installations;
drop table file_downloads;
drop table product_orders;
drop table produkt_files;
drop table products;

ALTER TABLE statuses ADD column due_basetime CHAR(1) NOT NULL DEFAULT 'm';

update settings set setting_value = '2.2.2' WHERE setting_key='dbLevel';