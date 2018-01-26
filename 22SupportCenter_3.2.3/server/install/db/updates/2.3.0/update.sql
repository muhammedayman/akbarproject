CREATE TABLE parsing_rules_fields (
    field_id VARCHAR(64) NOT NULL,
    rule_id INTEGER NOT NULL,
    match_pattern TEXT NOT NULL,
    target_type CHAR(1) NOT NULL,
    condition_type VARCHAR(16),
    condition_value VARCHAR(250),
    PRIMARY KEY (field_id, rule_id),
    KEY IDX_parsing_rules_fields1(field_id),
    KEY IDX_parsing_rules_fields2(rule_id)
);

ALTER TABLE notifications ADD account_id VARCHAR( 32 ) NULL ;

update settings set setting_value = '2.3.0' WHERE setting_key='dbLevel';