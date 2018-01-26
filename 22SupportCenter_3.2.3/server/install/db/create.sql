# ---------------------------------------------------------------------- #
# Script generated with: DeZign for Databases v5.1.1                     #
# Target DBMS:           MySQL 4                                         #
# Project file:          support.dez                                     #
# Project name:                                                          #
# Author:                                                                #
# Script type:           Database creation script                        #
# Created on:            2009-08-10 19:19                                #
# Model version:         Version 2009-08-10                              #
# ---------------------------------------------------------------------- #


# ---------------------------------------------------------------------- #
# Tables                                                                 #
# ---------------------------------------------------------------------- #

# ---------------------------------------------------------------------- #
# Add table "users"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE users (
    user_id VARCHAR(32) NOT NULL,
    name VARCHAR(250),
    email VARCHAR(250) NOT NULL,
    email_quality TINYINT,
    created DATETIME NOT NULL,
    password VARCHAR(32) NOT NULL,
    signature TEXT,
    hash VARCHAR(32),
    user_type CHAR(1) NOT NULL,
    picture_id VARCHAR(32),
    groupid INTEGER UNSIGNED,
    hide_suggestions CHAR(1) NOT NULL DEFAULT 'n',
    disable_dm_notifications CHAR(1) NOT NULL DEFAULT 'n',
    CONSTRAINT PK_users PRIMARY KEY (user_id)
);

CREATE UNIQUE INDEX IDX_users1 ON users (email ASC);

# ---------------------------------------------------------------------- #
# Add table "tickets"                                                    #
# ---------------------------------------------------------------------- #

CREATE TABLE tickets (
    ticket_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    queue_id VARCHAR(32) NOT NULL,
    subject_ticket_id VARCHAR(16) NOT NULL,
    thread_id VARCHAR(250) NOT NULL,
    status CHAR(1),
    priority TINYINT UNSIGNED ZEROFILL,
    customer_id VARCHAR(32),
    agent_owner_id VARCHAR(32),
    created DATETIME NOT NULL,
    last_update DATETIME NOT NULL,
    first_subject VARCHAR(255),
    spam_ratio FLOAT,
    mails_count MEDIUMINT UNSIGNED,
    CONSTRAINT PK_tickets PRIMARY KEY (ticket_id)
);

CREATE UNIQUE INDEX IDX_tickets1 ON tickets (subject_ticket_id);

CREATE UNIQUE INDEX IDX_tickets2 ON tickets (thread_id);

CREATE INDEX IDX_tickets3 ON tickets (agent_owner_id);

CREATE INDEX IDX_tickets4 ON tickets (customer_id);

# ---------------------------------------------------------------------- #
# Add table "mails"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE mails (
    mail_id VARCHAR(32) NOT NULL,
    ticket_id INTEGER UNSIGNED,
    account_id VARCHAR(32),
    parent_mail_id VARCHAR(32),
    hdr_message_id VARCHAR(250),
    unique_msg_id VARCHAR(250) NOT NULL,
    subject VARCHAR(255),
    headers TEXT,
    body LONGTEXT,
    body_html LONGTEXT,
    is_answered CHAR(1) NOT NULL,
    created DATETIME NOT NULL,
    delivery_date DATETIME,
    spam_ratio FLOAT,
    delivery_status CHAR(1) NOT NULL,
    is_comment CHAR(1) NOT NULL,
    created_date DATE,
    CONSTRAINT PK_mails PRIMARY KEY (mail_id)
);

CREATE INDEX IDX_mails1 ON mails (unique_msg_id);

CREATE INDEX IDX_mails2 ON mails (ticket_id);

CREATE INDEX IDX_mails3 ON mails (hdr_message_id);

CREATE FULLTEXT INDEX IDX_mails4 ON mails (body,subject);

CREATE INDEX IDX_mails_5 ON mails (created_date);

# ---------------------------------------------------------------------- #
# Add table "mail_accounts"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE mail_accounts (
    account_id VARCHAR(32) NOT NULL,
    account_name VARCHAR(250) NOT NULL,
    account_email VARCHAR(250) NOT NULL,
    from_name_format CHAR(1) NOT NULL,
    from_name VARCHAR(255),
    pop3_server VARCHAR(250),
    pop3_port SMALLINT UNSIGNED,
    pop3_ssl CHAR(1),
    pop3_username VARCHAR(64),
    pop3_password VARCHAR(64),
    use_smtp CHAR(1) NOT NULL,
    smtp_server VARCHAR(250),
    smtp_port SMALLINT UNSIGNED,
    smtp_ssl CHAR(1),
    smtp_tls CHAR(1) NOT NULL DEFAULT 'n',
    smtp_require_auth CHAR(1),
    smtp_username VARCHAR(64),
    smtp_password VARCHAR(64),
    delete_messages CHAR(1) NOT NULL,
    last_unique_msg_id VARCHAR(250),
    is_default CHAR(1),
    last_msg_received DATETIME,
    public CHAR(1) NOT NULL,
    last_processing DATETIME,
    CONSTRAINT PK_mail_accounts PRIMARY KEY (account_id)
);

# ---------------------------------------------------------------------- #
# Add table "queues"                                                     #
# ---------------------------------------------------------------------- #

CREATE TABLE queues (
    queue_id VARCHAR(32) NOT NULL,
    name VARCHAR(64) NOT NULL,
    ticket_id_prefix VARCHAR(32),
    queue_email VARCHAR(250),
    answer_time INTEGER NOT NULL,
    autorespond_nt CHAR(1) NOT NULL,
    autorespond_nt_subject VARCHAR(250),
    autorespond_nt_body TEXT,
    is_default CHAR(1) NOT NULL,
    public CHAR(1),
    opened_for_users CHAR(1) NOT NULL,
    queue_signature TEXT,
    CONSTRAINT PK_queues PRIMARY KEY (queue_id)
);

# ---------------------------------------------------------------------- #
# Add table "mail_users"                                                 #
# ---------------------------------------------------------------------- #

CREATE TABLE mail_users (
    user_id VARCHAR(32) NOT NULL,
    mail_role VARCHAR(16) NOT NULL,
    mail_id VARCHAR(32) NOT NULL,
    CONSTRAINT PK_mail_users PRIMARY KEY (user_id, mail_role, mail_id)
);

# ---------------------------------------------------------------------- #
# Add table "queue_agents"                                               #
# ---------------------------------------------------------------------- #

CREATE TABLE queue_agents (
    user_id VARCHAR(32) NOT NULL,
    queue_id VARCHAR(32) NOT NULL,
    CONSTRAINT PK_queue_agents PRIMARY KEY (user_id, queue_id)
);

# ---------------------------------------------------------------------- #
# Add table "parsing_rules"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE parsing_rules (
    rule_id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    rule_order MEDIUMINT UNSIGNED,
    from_addr_cond VARCHAR(16),
    from_addr_value VARCHAR(250),
    to_addr_cond VARCHAR(16),
    to_addr_value VARCHAR(250),
    cc_addr_cond VARCHAR(16),
    cc_addr_value VARCHAR(250),
    queue_cond VARCHAR(16),
    queue_id VARCHAR(32),
    subject_cond VARCHAR(16),
    subject_value VARCHAR(250),
    body_cond VARCHAR(16),
    body_value VARCHAR(250),
    is_new_ticket_cond VARCHAR(16),
    account_cond VARCHAR(16),
    account_value VARCHAR(32),
    group_cond VARCHAR(16),
    group_id INTEGER ZEROFILL,
    move_to_queue_id VARCHAR(32),
    change_to_status_id CHAR(1),
    change_to_prio TINYINT UNSIGNED,
    assign_to_agent_id VARCHAR(32),
    delete_mail CHAR(1),
    stop_processing CHAR(1),
    is_registered_user_cond VARCHAR(16),
    CONSTRAINT PK_parsing_rules PRIMARY KEY (rule_id)
);

# ---------------------------------------------------------------------- #
# Add table "syslogs"                                                    #
# ---------------------------------------------------------------------- #

CREATE TABLE syslogs (
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    level VARCHAR(40) NOT NULL,
    log_type VARCHAR(40) NOT NULL,
    created DATETIME NOT NULL,
    log_text TEXT NOT NULL,
    ip VARCHAR(15),
    CONSTRAINT PK_syslogs PRIMARY KEY (log_id)
);

# ---------------------------------------------------------------------- #
# Add table "log_users"                                                  #
# ---------------------------------------------------------------------- #

CREATE TABLE log_users (
    user_id VARCHAR(32) NOT NULL,
    log_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT PK_log_users PRIMARY KEY (user_id, log_id)
);

# ---------------------------------------------------------------------- #
# Add table "filters"                                                    #
# ---------------------------------------------------------------------- #

CREATE TABLE filters (
    filter_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    filter_name VARCHAR(255) NOT NULL,
    grid_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    filter_value TEXT,
    last_used DATETIME,
    is_global CHAR(1) NOT NULL DEFAULT 'n',
    CONSTRAINT PK_filters PRIMARY KEY (filter_id)
);

CREATE UNIQUE INDEX IDX_filters1 ON filters (filter_name,user_id);

# ---------------------------------------------------------------------- #
# Add table "words"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE words (
    word_id CHAR(32) NOT NULL,
    word VARCHAR(250) NOT NULL,
    ranking FLOAT,
    CONSTRAINT PK_words PRIMARY KEY (word_id)
);

# ---------------------------------------------------------------------- #
# Add table "file_contents"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE file_contents (
    file_id VARCHAR(32) NOT NULL,
    content_nr INTEGER UNSIGNED NOT NULL,
    content LONGBLOB,
    CONSTRAINT PK_file_contents PRIMARY KEY (file_id, content_nr)
);

# ---------------------------------------------------------------------- #
# Add table "files"                                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE files (
    file_id VARCHAR(32) NOT NULL,
    filename VARCHAR(250) NOT NULL,
    filesize INTEGER UNSIGNED,
    filetype VARCHAR(250),
    created DATETIME NOT NULL,
    downloads INTEGER ZEROFILL,
    contentid VARCHAR(255),
    etag VARCHAR(32),
    CONSTRAINT PK_files PRIMARY KEY (file_id)
);

# ---------------------------------------------------------------------- #
# Add table "mail_attachments"                                           #
# ---------------------------------------------------------------------- #

CREATE TABLE mail_attachments (
    mail_id VARCHAR(32) NOT NULL,
    file_id VARCHAR(32) NOT NULL,
    CONSTRAINT PK_mail_attachments PRIMARY KEY (mail_id, file_id)
);

# ---------------------------------------------------------------------- #
# Add table "ticket_changes"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE ticket_changes (
    log_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INTEGER UNSIGNED,
    created DATETIME NOT NULL,
    created_by_user_id VARCHAR(32),
    new_status CHAR(1),
    new_queue_id VARCHAR(32),
    new_agent_owner_id VARCHAR(32),
    new_priority TINYINT UNSIGNED,
    CONSTRAINT PK_ticket_changes PRIMARY KEY (log_id)
);

# ---------------------------------------------------------------------- #
# Add table "settings"                                                   #
# ---------------------------------------------------------------------- #

CREATE TABLE settings (
    setting_id VARCHAR(32) NOT NULL,
    setting_key VARCHAR(32) NOT NULL,
    user_id VARCHAR(32),
    setting_value TEXT,
    CONSTRAINT PK_settings PRIMARY KEY (setting_id)
);

# ---------------------------------------------------------------------- #
# Add table "logins"                                                     #
# ---------------------------------------------------------------------- #

CREATE TABLE logins (
    login_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    login DATETIME,
    last_request DATETIME,
    logout DATETIME,
    ip VARCHAR(15),
    last_action VARCHAR(32),
    ticket_id INTEGER UNSIGNED,
    CONSTRAINT PK_logins PRIMARY KEY (login_id)
);

CREATE INDEX IDX_logins1 ON logins (user_id);

CREATE INDEX IDX_logins2 ON logins (ticket_id);

# ---------------------------------------------------------------------- #
# Add table "notifications"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE notifications (
    notification_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id VARCHAR(32),
    name VARCHAR(128) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    sendto_notif_owner CHAR(1) NOT NULL,
    sendto_customer CHAR(1) NOT NULL,
    sendto_agent_owner CHAR(1) NOT NULL,
    sendto_recipients TEXT,
    ticket_owners TEXT,
    ticket_queue TEXT,
    ticket_status VARCHAR(255),
    ticket_priority VARCHAR(255),
    customer_groups TEXT,
    to_statuses VARCHAR(255),
    to_queues TEXT,
    to_priorities VARCHAR(255),
    to_owners TEXT,
    subject VARCHAR(255),
    body TEXT,
    status_check CHAR(1) NOT NULL,
    priority_check CHAR(1) NOT NULL,
    queue_check CHAR(1) NOT NULL,
    owner_check CHAR(1) NOT NULL,
    call_url TEXT,
    custom_from_mail VARCHAR(255),
    CONSTRAINT PK_notifications PRIMARY KEY (notification_id)
);

CREATE INDEX IDX_user_notifications1 ON notifications (user_id);

# ---------------------------------------------------------------------- #
# Add table "displayed_tickets"                                          #
# ---------------------------------------------------------------------- #

CREATE TABLE displayed_tickets (
    user_id VARCHAR(32) NOT NULL,
    ticket_id INTEGER UNSIGNED NOT NULL,
    created DATETIME NOT NULL,
    CONSTRAINT PK_displayed_tickets PRIMARY KEY (user_id, ticket_id)
);

# ---------------------------------------------------------------------- #
# Add table "kb_items"                                                   #
# ---------------------------------------------------------------------- #

CREATE TABLE kb_items (
    item_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    tree_path VARCHAR(128),
    item_order MEDIUMINT UNSIGNED,
    subject VARCHAR(255) NOT NULL,
    metadescription TEXT,
    url VARCHAR(128),
    body LONGTEXT,
    user_id VARCHAR(32) NOT NULL,
    created DATETIME,
    access_mode CHAR(1),
    is_indexed CHAR(1) NOT NULL,
    CONSTRAINT PK_kb_items PRIMARY KEY (item_id)
);

CREATE FULLTEXT INDEX IDX_kb_fulltext ON kb_items (subject,metadescription,body);

CREATE UNIQUE INDEX IDX_kb_items2 ON kb_items (tree_path,url);

# ---------------------------------------------------------------------- #
# Add table "kb_comments"                                                #
# ---------------------------------------------------------------------- #

CREATE TABLE kb_comments (
    comment_id INTEGER UNSIGNED NOT NULL,
    item_id INTEGER UNSIGNED,
    ip VARCHAR(15),
    user_id VARCHAR(32),
    email VARCHAR(255),
    fullname VARCHAR(128),
    ranking TINYINT,
    comment TEXT,
    created DATETIME NOT NULL,
    status CHAR(1),
    CONSTRAINT PK_kb_comments PRIMARY KEY (comment_id)
);

# ---------------------------------------------------------------------- #
# Add table "kb_item_files"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE kb_item_files (
    item_id INTEGER UNSIGNED NOT NULL,
    file_id VARCHAR(32) NOT NULL,
    CONSTRAINT PK_kb_item_files PRIMARY KEY (item_id, file_id)
);

# ---------------------------------------------------------------------- #
# Add table "kb_item_words"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE kb_item_words (
    item_id INTEGER UNSIGNED NOT NULL,
    word_id CHAR(32) NOT NULL,
    word_ranking FLOAT,
    CONSTRAINT PK_kb_item_words PRIMARY KEY (item_id, word_id)
);

# ---------------------------------------------------------------------- #
# Add table "agent_accounts"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE agent_accounts (
    account_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    CONSTRAINT PK_agent_accounts PRIMARY KEY (account_id, user_id)
);

# ---------------------------------------------------------------------- #
# Add table "mail_gateways"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE mail_gateways (
    gateway_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(128) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    ticket_owners TEXT,
    statuses VARCHAR(255),
    queues TEXT,
    priorities VARCHAR(255),
    CONSTRAINT PK_mail_gateways PRIMARY KEY (gateway_id)
);

CREATE INDEX IDX_mail_gateways1 ON mail_gateways (user_id);

# ---------------------------------------------------------------------- #
# Add table "statistic_days"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE statistic_days (
    day_date DATE NOT NULL,
    CONSTRAINT PK_statistic_days PRIMARY KEY (day_date)
);

# ---------------------------------------------------------------------- #
# Add table "outbox"                                                     #
# ---------------------------------------------------------------------- #

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
    CONSTRAINT PK_outbox PRIMARY KEY (out_id)
);

# ---------------------------------------------------------------------- #
# Add table "outbox_contents"                                            #
# ---------------------------------------------------------------------- #

CREATE TABLE outbox_contents (
    out_id INTEGER UNSIGNED NOT NULL,
    content_nr MEDIUMINT UNSIGNED NOT NULL,
    content LONGBLOB,
    CONSTRAINT PK_outbox_contents PRIMARY KEY (out_id, content_nr)
);

# ---------------------------------------------------------------------- #
# Add table "priorities"                                                 #
# ---------------------------------------------------------------------- #

CREATE TABLE priorities (
    priority TINYINT UNSIGNED ZEROFILL NOT NULL,
    priority_name VARCHAR(32) NOT NULL,
    CONSTRAINT PK_priorities PRIMARY KEY (priority)
);

CREATE UNIQUE INDEX IDX_Entity_11 ON priorities (priority_name);

# ---------------------------------------------------------------------- #
# Add table "statuses"                                                   #
# ---------------------------------------------------------------------- #

CREATE TABLE statuses (
    status CHAR(1) NOT NULL,
    status_name VARCHAR(32) NOT NULL,
    color VARCHAR(64),
    img VARCHAR(250),
    due CHAR(1) NOT NULL,
    due_basetime CHAR(1) NOT NULL,
    CONSTRAINT PK_statuses PRIMARY KEY (status)
);

CREATE UNIQUE INDEX IDX_Entity_11 ON statuses (status_name);

# ---------------------------------------------------------------------- #
# Add table "custom_fields"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE custom_fields (
    field_id VARCHAR(64) NOT NULL,
    field_title VARCHAR(255) NOT NULL,
    field_type CHAR(1) NOT NULL,
    default_value TEXT,
    options TEXT,
    related_to CHAR(1) NOT NULL,
    order_value INTEGER,
    user_access CHAR(1) NOT NULL,
    CONSTRAINT PK_custom_fields PRIMARY KEY (field_id)
);

# ---------------------------------------------------------------------- #
# Add table "custom_values"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE custom_values (
    value_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    field_id VARCHAR(64) NOT NULL,
    ticket_id INTEGER UNSIGNED,
    user_id VARCHAR(32),
    field_value TEXT,
    groupid INTEGER UNSIGNED,
    CONSTRAINT PK_custom_values PRIMARY KEY (value_id)
);

# ---------------------------------------------------------------------- #
# Add table "escalation_rules"                                           #
# ---------------------------------------------------------------------- #

CREATE TABLE escalation_rules (
    rule_id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    rule_order MEDIUMINT,
    queue_cond TEXT,
    owner_cond TEXT,
    status_cond VARCHAR(128),
    priority_cond TEXT,
    last_reply_cond VARCHAR(10),
    ticket_age_cond VARCHAR(10),
    answer_time_cond VARCHAR(10),
    group_cond TEXT,
    customer_cond VARCHAR(255),
    action_queue VARCHAR(32),
    action_owner VARCHAR(32),
    action_status CHAR(1),
    action_priority TINYINT UNSIGNED ZEROFILL,
    action_delete_ticket CHAR(1),
    action_delete_ticket_users CHAR(1),
    last_execution DATETIME,
    CONSTRAINT PK_escalation_rules PRIMARY KEY (rule_id)
);

# ---------------------------------------------------------------------- #
# Add table "parsing_rules_fields"                                       #
# ---------------------------------------------------------------------- #

CREATE TABLE parsing_rules_fields (
    field_id VARCHAR(64) NOT NULL,
    rule_id INTEGER NOT NULL,
    match_pattern TEXT NOT NULL,
    target_type CHAR(1) NOT NULL,
    condition_type VARCHAR(16),
    condition_value VARCHAR(250),
    CONSTRAINT PK_parsing_rules_fields PRIMARY KEY (field_id, rule_id)
);

# ---------------------------------------------------------------------- #
# Add table "signatures"                                                 #
# ---------------------------------------------------------------------- #

CREATE TABLE signatures (
    user_id VARCHAR(32) NOT NULL,
    queue_id VARCHAR(32) NOT NULL,
    signature TEXT,
    CONSTRAINT PK_signatures PRIMARY KEY (user_id, queue_id)
);

# ---------------------------------------------------------------------- #
# Add table "work_reports"                                               #
# ---------------------------------------------------------------------- #

CREATE TABLE work_reports (
    work_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    approved CHAR(1) NOT NULL,
    created DATETIME NOT NULL,
    login_id VARCHAR(32),
    user_id VARCHAR(32),
    ticket_id INTEGER UNSIGNED,
    billing_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    work_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    ticket_time INTEGER UNSIGNED ZEROFILL DEFAULT 0,
    note TEXT,
    CONSTRAINT PK_work_reports PRIMARY KEY (work_id)
);

# ---------------------------------------------------------------------- #
# Add table "mail_templates"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE mail_templates (
    template_id VARCHAR(64) NOT NULL,
    queue_id VARCHAR(32) NOT NULL,
    is_system CHAR(1) NOT NULL,
    is_queuebased CHAR(1) NOT NULL,
    subject TEXT,
    body_html TEXT,
    body_text TEXT,
    CONSTRAINT PK_mail_templates PRIMARY KEY (template_id, queue_id)
);

# ---------------------------------------------------------------------- #
# Add table "groups"                                                     #
# ---------------------------------------------------------------------- #

CREATE TABLE groups (
    groupid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    group_name VARCHAR(255) NOT NULL,
    CONSTRAINT PK_groups PRIMARY KEY (groupid)
);

# ---------------------------------------------------------------------- #
# Add table "products"                                                   #
# ---------------------------------------------------------------------- #

CREATE TABLE products (
    productid VARCHAR(32) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created DATETIME NOT NULL,
    max_downloads MEDIUMINT DEFAULT 0,
    valid_days MEDIUMINT NOT NULL DEFAULT 0,
    product_code VARCHAR(255) NOT NULL,
    is_enabled CHAR(1) NOT NULL DEFAULT 'y',
    tree_path TEXT NOT NULL,
    subtitle VARCHAR(255),
    send_notification CHAR(1) NOT NULL DEFAULT 'n',
    notification_subject VARCHAR(255),
    notification_body TEXT,
    CONSTRAINT PK_products PRIMARY KEY (productid)
);

# ---------------------------------------------------------------------- #
# Add table "product_files"                                              #
# ---------------------------------------------------------------------- #

CREATE TABLE product_files (
    file_id VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_files PRIMARY KEY (file_id, productid)
);

# ---------------------------------------------------------------------- #
# Add table "product_orders"                                             #
# ---------------------------------------------------------------------- #

CREATE TABLE product_orders (
    orderid VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    groupid INTEGER UNSIGNED,
    user_id VARCHAR(32),
    valid_from DATETIME NOT NULL,
    valid_until DATETIME,
    max_downloads INTEGER NOT NULL DEFAULT 0,
    licenseid VARCHAR(255),
    order_number VARCHAR(255),
    order_info TEXT,
    price FLOAT,
    created DATETIME NOT NULL,
    CONSTRAINT PK_product_orders PRIMARY KEY (orderid)
);

# ---------------------------------------------------------------------- #
# Add table "product_bundles"                                            #
# ---------------------------------------------------------------------- #

CREATE TABLE product_bundles (
    bundled_productid VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_bundles PRIMARY KEY (bundled_productid, productid)
);

# ---------------------------------------------------------------------- #
# Add table "product_downloads"                                          #
# ---------------------------------------------------------------------- #

CREATE TABLE product_downloads (
    downloadid INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    file_id VARCHAR(32) NOT NULL,
    productid VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    orderid VARCHAR(32) NOT NULL,
    created DATETIME NOT NULL,
    ip VARCHAR(15) NOT NULL,
    CONSTRAINT PK_product_downloads PRIMARY KEY (downloadid)
);

# ---------------------------------------------------------------------- #
# Add table "product_installations"                                      #
# ---------------------------------------------------------------------- #

CREATE TABLE product_installations (
    installation_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    created DATETIME NOT NULL,
    ip VARCHAR(15) NOT NULL,
    orderid VARCHAR(32),
    license VARCHAR(32) NOT NULL,
    CONSTRAINT PK_product_installations PRIMARY KEY (installation_id)
);

# ---------------------------------------------------------------------- #
# Foreign key constraints                                                #
# ---------------------------------------------------------------------- #

ALTER TABLE users ADD CONSTRAINT files_users 
    FOREIGN KEY (picture_id) REFERENCES files (file_id);

ALTER TABLE users ADD CONSTRAINT groups_users 
    FOREIGN KEY (groupid) REFERENCES groups (groupid);

ALTER TABLE tickets ADD CONSTRAINT queues_tickets 
    FOREIGN KEY (queue_id) REFERENCES queues (queue_id);

ALTER TABLE tickets ADD CONSTRAINT priorities_tickets 
    FOREIGN KEY (priority) REFERENCES priorities (priority);

ALTER TABLE tickets ADD CONSTRAINT statuses_tickets 
    FOREIGN KEY (status) REFERENCES statuses (status);

ALTER TABLE mails ADD CONSTRAINT tickets_mails 
    FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id);

ALTER TABLE mails ADD CONSTRAINT mail_accounts_mails 
    FOREIGN KEY (account_id) REFERENCES mail_accounts (account_id);

ALTER TABLE mail_users ADD CONSTRAINT users_mail_users 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE mail_users ADD CONSTRAINT mails_mail_users 
    FOREIGN KEY (mail_id) REFERENCES mails (mail_id);

ALTER TABLE queue_agents ADD CONSTRAINT users_queue_agents 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE queue_agents ADD CONSTRAINT queues_queue_agents 
    FOREIGN KEY (queue_id) REFERENCES queues (queue_id);

ALTER TABLE log_users ADD CONSTRAINT users_log_users 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE log_users ADD CONSTRAINT syslogs_log_users 
    FOREIGN KEY (log_id) REFERENCES syslogs (log_id);

ALTER TABLE filters ADD CONSTRAINT users_filters 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE file_contents ADD CONSTRAINT files_file_contents 
    FOREIGN KEY (file_id) REFERENCES files (file_id);

ALTER TABLE mail_attachments ADD CONSTRAINT mails_mail_attachments 
    FOREIGN KEY (mail_id) REFERENCES mails (mail_id);

ALTER TABLE mail_attachments ADD CONSTRAINT files_mail_attachments 
    FOREIGN KEY (file_id) REFERENCES files (file_id);

ALTER TABLE ticket_changes ADD CONSTRAINT tickets_ticket_changes 
    FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id);

ALTER TABLE notifications ADD CONSTRAINT mail_accounts_notifications 
    FOREIGN KEY (account_id) REFERENCES mail_accounts (account_id);

ALTER TABLE displayed_tickets ADD CONSTRAINT users_displayed_tickets 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE displayed_tickets ADD CONSTRAINT tickets_displayed_tickets 
    FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id);

ALTER TABLE kb_comments ADD CONSTRAINT kb_items_kb_comments 
    FOREIGN KEY (item_id) REFERENCES kb_items (item_id);

ALTER TABLE kb_item_files ADD CONSTRAINT kb_items_kb_item_files 
    FOREIGN KEY (item_id) REFERENCES kb_items (item_id);

ALTER TABLE kb_item_files ADD CONSTRAINT files_kb_item_files 
    FOREIGN KEY (file_id) REFERENCES files (file_id);

ALTER TABLE kb_item_words ADD CONSTRAINT kb_items_kb_item_words 
    FOREIGN KEY (item_id) REFERENCES kb_items (item_id);

ALTER TABLE kb_item_words ADD CONSTRAINT words_kb_item_words 
    FOREIGN KEY (word_id) REFERENCES words (word_id);

ALTER TABLE agent_accounts ADD CONSTRAINT mail_accounts_agent_accounts 
    FOREIGN KEY (account_id) REFERENCES mail_accounts (account_id);

ALTER TABLE agent_accounts ADD CONSTRAINT users_agent_accounts 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE outbox_contents ADD CONSTRAINT outbox_outbox_contents 
    FOREIGN KEY (out_id) REFERENCES outbox (out_id);

ALTER TABLE custom_values ADD CONSTRAINT custom_fields_custom_values 
    FOREIGN KEY (field_id) REFERENCES custom_fields (field_id);

ALTER TABLE custom_values ADD CONSTRAINT tickets_custom_values 
    FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id);

ALTER TABLE custom_values ADD CONSTRAINT users_custom_values 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE custom_values ADD CONSTRAINT groups_custom_values 
    FOREIGN KEY (groupid) REFERENCES groups (groupid);

ALTER TABLE parsing_rules_fields ADD CONSTRAINT custom_fields_parsing_rules_fields 
    FOREIGN KEY (field_id) REFERENCES custom_fields (field_id);

ALTER TABLE parsing_rules_fields ADD CONSTRAINT parsing_rules_parsing_rules_fields 
    FOREIGN KEY (rule_id) REFERENCES parsing_rules (rule_id);

ALTER TABLE signatures ADD CONSTRAINT users_signatures 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE signatures ADD CONSTRAINT queues_signatures 
    FOREIGN KEY (queue_id) REFERENCES queues (queue_id);

ALTER TABLE work_reports ADD CONSTRAINT users_work_reports 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE work_reports ADD CONSTRAINT tickets_work_reports 
    FOREIGN KEY (ticket_id) REFERENCES tickets (ticket_id);

ALTER TABLE product_files ADD CONSTRAINT files_product_files 
    FOREIGN KEY (file_id) REFERENCES files (file_id);

ALTER TABLE product_files ADD CONSTRAINT products_product_files 
    FOREIGN KEY (productid) REFERENCES products (productid);

ALTER TABLE product_orders ADD CONSTRAINT products_product_orders 
    FOREIGN KEY (productid) REFERENCES products (productid);

ALTER TABLE product_orders ADD CONSTRAINT groups_product_orders 
    FOREIGN KEY (groupid) REFERENCES groups (groupid);

ALTER TABLE product_orders ADD CONSTRAINT users_product_orders 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE product_bundles ADD CONSTRAINT products_product_bundles 
    FOREIGN KEY (bundled_productid) REFERENCES products (productid);

ALTER TABLE product_bundles ADD CONSTRAINT products_product_bundles11 
    FOREIGN KEY (productid) REFERENCES products (productid);

ALTER TABLE product_downloads ADD CONSTRAINT product_files_product_downloads 
    FOREIGN KEY (file_id, productid) REFERENCES product_files (file_id,productid);

ALTER TABLE product_downloads ADD CONSTRAINT users_product_downloads 
    FOREIGN KEY (user_id) REFERENCES users (user_id);

ALTER TABLE product_downloads ADD CONSTRAINT product_orders_product_downloads 
    FOREIGN KEY (orderid) REFERENCES product_orders (orderid);

ALTER TABLE product_installations ADD CONSTRAINT product_orders_product_installations 
    FOREIGN KEY (orderid) REFERENCES product_orders (orderid);
