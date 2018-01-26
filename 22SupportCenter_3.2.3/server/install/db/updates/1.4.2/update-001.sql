CREATE TABLE statistic_days (
    day_date DATE NOT NULL,
    PRIMARY KEY (day_date)
);

update settings set setting_value = '1.4.2' WHERE setting_key='dbLevel';