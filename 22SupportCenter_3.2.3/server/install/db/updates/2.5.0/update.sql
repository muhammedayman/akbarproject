ALTER TABLE notifications ADD custom_from_mail VARCHAR( 255 ) NULL ;

CREATE TABLE work_reports (
    work_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    approved CHAR(1) NOT NULL DEFAULT 'n',
    created DATETIME NOT NULL,
    login_id VARCHAR(32),
    user_id VARCHAR(32),
    ticket_id INTEGER UNSIGNED,
    billing_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    work_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    ticket_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    note TEXT,
    PRIMARY KEY (work_id),
    KEY IDX_work_reports1(user_id),
    KEY IDX_work_reports2(ticket_id)
);

update settings set setting_value = '2.5.0' WHERE setting_key='dbLevel';