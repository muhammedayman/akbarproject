<?php
/**
 *   Handler class for Repository Synchronisation
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("App_DownloadManager_Integrator");
class App_DownloadManager_Swreg extends App_DownloadManager_Integrator {

    function execute() {
        $response =& $this->getByRef('response');

        //check if product exists
        if ($product = $this->loadProduct($_REQUEST['pc'])) {
            if ($user = $this->loadUser($_REQUEST['email'], $_REQUEST['name'] . $_REQUEST['initals'])) {
                if ($this->assignProductToUser($product, $user, $_REQUEST)) {
                    $this->state->log('info', 'Product assigned to customer ' . var_export($_REQUEST, true), 'SWREG');
                } else {
                    $this->state->log('error', $response->get('error'), 'SWREG');
                    $this->state->log('error', 'Failed to assign product to customer: ' . var_export($_REQUEST, true), 'SWREG');
                }
            } else {
                $this->state->log('error', $response->get('error'), 'SWREG');
                $this->state->log('error', 'Failed to load customer: ' . var_export($_REQUEST, true), 'SWREG');
            }
        } else {
            $this->state->log('error', $response->get('error'), 'SWREG');
            $this->state->log('error', 'Failed to load product: ' . var_export($_REQUEST, true), 'SWREG');
        }


        echo '<softshop></softshop>';
        return true;
    }
}
?>