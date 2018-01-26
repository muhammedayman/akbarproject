CREATE TABLE mail_templates (
    template_id VARCHAR(64) NOT NULL,
    queue_id VARCHAR(32) NOT NULL,
    is_system CHAR(1) NOT NULL DEFAULT 'n',
    is_queuebased CHAR(1) NOT NULL DEFAULT 'n',
    subject TEXT,
    body_html TEXT,
    body_text TEXT,
    PRIMARY KEY (template_id, queue_id)
);

INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'RegistrationMail', 'all', 'y', 'n', '', setting_value, '' FROM settings WHERE setting_key = 'registrationMailBody')
ON DUPLICATE KEY UPDATE body_html=setting_value;

INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'RegistrationMail', 'all', 'y', 'n', setting_value, '', '' FROM settings WHERE setting_key = 'registrationMailSubject')
ON DUPLICATE KEY UPDATE subject=setting_value;

delete from settings where setting_key = 'registrationMailSubject';
delete from settings where setting_key = 'registrationMailBody';



INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'RequestNewPasswordMail', 'all', 'y', 'n', '', setting_value, '' FROM settings WHERE setting_key = 'requestNewPasswordMailBody')
ON DUPLICATE KEY UPDATE body_html=setting_value;

INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'RequestNewPasswordMail', 'all', 'y', 'n', setting_value, '', '' FROM settings WHERE setting_key = 'requestNewPasswordMailSubject')
ON DUPLICATE KEY UPDATE subject=setting_value;

delete from settings where setting_key = 'requestNewPasswordMailSubject';
delete from settings where setting_key = 'requestNewPasswordMailBody';



INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'CoverMail', 'all', 'y', 'y', '${subject}', setting_value, '' FROM settings WHERE setting_key = 'customHtmlMailTemplateBody')
ON DUPLICATE KEY UPDATE body_html=setting_value;

INSERT INTO mail_templates (template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text)
(SELECT 'CoverMail', 'all', 'y', 'y', '${subject}', '', setting_value FROM settings WHERE setting_key = 'customTextMailTemplateBody')
ON DUPLICATE KEY UPDATE body_text=setting_value;

delete from settings where setting_key = 'customHtmlMailTemplateBody';
delete from settings where setting_key = 'customTextMailTemplateBody';


update settings set setting_value = '2.5.1' WHERE setting_key='dbLevel';