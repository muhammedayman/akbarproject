ALTER TABLE mail_accounts ADD public CHAR( 1 ) DEFAULT 'y' NOT NULL;
CREATE TABLE agent_accounts (
    account_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    PRIMARY KEY (account_id, user_id),
    KEY IDX_agent_accounts2(user_id)
);
update settings set setting_value = '1.2.6' WHERE setting_key='dbLevel';
