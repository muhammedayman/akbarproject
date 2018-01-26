ALTER TABLE escalation_rules ADD action_delete_ticket_users CHAR( 1 ) NULL ;
update settings set setting_value = '2.0.7' WHERE setting_key='dbLevel';