<?php

include_once('lib/class.imap.php');
$imap = new Imap();
$connection_result = $imap->connect('{imap.gmail.com:993/imap/ssl}INBOX', 'aymanprojecttest@gmail.com', 'akbar123*');
    if ($connection_result !== true) {
        echo $connection_result; //Error message!
        exit;
    }
$messages = $imap->getMessages('text'); //Array of messages

?>