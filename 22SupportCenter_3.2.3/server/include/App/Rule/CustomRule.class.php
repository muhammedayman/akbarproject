<?php
/**
 *   Represents Custom Rule executed on ticket.
 *
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */
QUnit::includeClass("QUnit_Rpc_Service");

class App_Rule_CustomRule extends QUnit_Rpc_Service {
    
    
    /**
     * Execute custom rule on ticket.
     *
     * @return boolean Return true if something was changed in ticket
     */
    function execute(&$paramsTicket, &$paramsMail, &$parser) {
        //By default we execute no custom rule - it is up to customer enable processing of custom rule
        return false;
        
        $config['Host'] = 'localhost';
        //Mysql User has to be different as is used for SupportCenter database !!! - Bug in Mysql library in php doesn't allow to use same mysql user for more databases !
        $config['User'] = 'dbusr';
        $config['Password'] = 'password';
        $config['Driver'] = 'Mysql';
        $config['Database'] = 'orders';
        $sql = 'SELECT email FROM users WHERE email=\'%s\'';
        $customQueueId = '3';
        
        QUnit::includeClass('QUnit_Db_Object');
        $db = QUnit_Db_Object::getDriver($config);
        $connect = $db->connect();

        $mailHeaders = unserialize($paramsMail->get('headers'));     
        QUnit::includeClass('QUnit_Net_Mail');   
        $from_mail = QUnit_Net_Mail::getEmailAddress($mailHeaders['from:']);
        $ret = $connect->execute(sprintf($sql, $from_mail));
        if(QUnit_Object::isError($ret)) {
            //Error handling - custom sql statement failed !
            //For now we do no handling
            return false;
        }
        
        $rows = $ret->fetchAllRows();
        
        foreach ($rows as $row) {
            if ($row[0] == $from_mail) {
                //Set queue id of ticket
                $paramsTicket->setField('queue_id', $customQueueId);
                return true;
            }
        }
        
        return false;
    }
}
?>
