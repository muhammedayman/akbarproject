<?php
/**
*   Base handler class for Languages
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SeHistory
*/

QUnit::includeClass('QUnit_Rpc_Service');
class App_Service_Languages extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'getAvailableLanguages':
                return true;
                break;
            default:
                return $this->callService('Users', 'authenticateAdmin', $params);
                break;
        }
    }

    /**
     *  getProjectList
     *
     *  @return object returns resultset
     */
    function getAvailableLanguages($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
        
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $languages = $this->state->lang->getAvaibleLanguages();
        $rs->setRows($languages);
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'languages');
        $md->setColumnNames(array('language'));
        $md->setColumnTypes(array('string'));
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $response->setResultVar('count', count($languages));
        return true;
    }
}
?>