<?php
/**
*
*   @author Andrej Harsani
*   @copyright Copyright � 2004
*   @since Version 0.1a
*   $Id: Core.class.php 1370 2004-11-23 16:25:56Z jsujan $
*/

class QUnit_Test_Pop3 extends PHPUnit_TestCase {

	var $pop3;
	
    function setUp() {
        $this->pop3 = QUnit::newObj('QUnit_Net_Mail_Pop3');
    	$this->pop3->hostname="www.qualityunit.com";
    	$this->pop3->join_continuation_header_lines=1;
    }

    function testConnection() {
        $this->assertEquals($this->pop3->Open(), '', 'Failled to Connect to POP3 Server');
        $this->assertEquals($this->pop3->Close(), '', 'Failled to Close POP3 server connection');
    }

    function testLogin() {
        $this->assertEquals($this->pop3->Open(), '', 'Failled to Connect to POP3 Server');
        $this->assertEquals($this->pop3->Login('supportcenter+qualityunit.com','support42'), '', 'Failled to Login to POP3 Server');
        $this->assertEquals($this->pop3->Close(), '', 'Failled to Close POP3 server connection');
    }

    function testMailboxStatistics() {
        $this->assertEquals($this->pop3->Open(), '', 'Failled to Connect to POP3 Server');
        $this->assertEquals($this->pop3->Login('supportcenter+qualityunit.com','support42'), '', 'Failled to Login to POP3 Server');
        $this->assertEquals($this->pop3->Statistics($messages,$size), '', 'Failled to retrieve mailbox statistics');
        echo "\nMailbox Statistics: Messages: $messages, Size: $size\n";
        $this->assertEquals($this->pop3->Close(), '', 'Failled to Close POP3 server connection');
    }
    
    
}

?>