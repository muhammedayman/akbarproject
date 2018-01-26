ALTER TABLE parsing_rules ADD account_cond VARCHAR( 16 ) , ADD account_value VARCHAR( 32 );

update settings set setting_value = '1.3.2' WHERE setting_key='dbLevel';