ALTER TABLE user_notifications ADD ticket_priority VARCHAR( 255 ) ,
ADD ticket_status VARCHAR( 255 ) ,
ADD ticket_queue TEXT;

UPDATE user_notifications SET ticket_owners = CONCAT("|", ticket_owners, "|");
UPDATE user_notifications SET ticket_queue = CONCAT("|", ticket_queue, "|");
UPDATE user_notifications SET ticket_priority = CONCAT("|", ticket_priority, "|");
UPDATE user_notifications SET ticket_status = CONCAT("|", ticket_status, "|");
UPDATE user_notifications SET to_statuses = CONCAT("|", to_statuses, "|");
UPDATE user_notifications SET to_priorities = CONCAT("|", to_priorities, "|");
UPDATE user_notifications SET to_queues = CONCAT("|", to_queues, "|");
UPDATE user_notifications SET to_owners = CONCAT("|", to_owners, "|");

UPDATE mail_gateways SET ticket_owners = CONCAT("|", ticket_owners, "|");
UPDATE mail_gateways SET statuses = CONCAT("|", statuses, "|");
UPDATE mail_gateways SET priorities = CONCAT("|", priorities, "|");
UPDATE mail_gateways SET queues = CONCAT("|", queues, "|");


update settings set setting_value = '1.3.5' WHERE setting_key='dbLevel';