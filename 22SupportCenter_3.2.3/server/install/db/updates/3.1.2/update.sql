ALTER TABLE parsing_rules CHANGE change_to_prio change_to_prio TINYINT( 3 ) UNSIGNED NULL; 
update settings set setting_value = '3.1.2' WHERE setting_key='dbLevel';