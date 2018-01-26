ALTER TABLE mails ADD delivery_status CHAR( 1 ) DEFAULT 'd' NOT NULL ;

CREATE TABLE outbox (
    out_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INTEGER UNSIGNED,
    mail_id VARCHAR(32),
    user_id VARCHAR(32),
    recipients TEXT,
    subject VARCHAR(255),
    scheduled DATETIME,
    created DATETIME,
    method VARCHAR(10) NOT NULL,
    params TEXT,
    retry_nr SMALLINT UNSIGNED,
    error_msg TEXT,
    last_retry DATETIME,
    status CHAR(1),
    PRIMARY KEY (out_id),
    UNIQUE KEY IDX_outbox1(out_id)
);

CREATE TABLE outbox_contents (
    out_id INTEGER UNSIGNED NOT NULL,
    content_nr MEDIUMINT UNSIGNED NOT NULL,
    content LONGBLOB,
    PRIMARY KEY (out_id, content_nr),
    KEY IDX_outbox_contents1(out_id)
);

ALTER TABLE mail_accounts ADD last_processing DATETIME;

update settings set setting_value = '1.5.4' WHERE setting_key='dbLevel';