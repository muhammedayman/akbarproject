<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Io');

class QUnit_Io_File extends QUnit_Io {

    var $fileName = '';
    var $fileMode;
    var $fileHandler = false;
    var $isOpened = false;

    function _init($fileName = '', $mode = 'r+') {
        parent::_init();
        $this->fileName = $fileName;
        $this->fileMode = $mode;
    }

    function setFileName($name) {
        $this->fileName = $name;
    }

    function setFileMode($mode) {
        $this->fileMode = $mode;
    }

    function getFileHandler() {
        if($this->fileHandler === false) {
            return $this->open();
        }
        return $this->fileHandler;
    }

    function isOpened() {
        return $this->isOpened;
    }

    function open() {
        if(!empty($this->fileName)) {
            if(!($this->fileHandler = @fopen($this->fileName, $this->fileMode))) {
/*                $error =& $this->getErrorObj();
                $error->addMessage("Cannot open file: ".$this->fileName." with mode: ".$this->fileMode);
                return $error;*/
                return false;
            }
            $this->isOpened = true;
            return $this->fileHandler;
        }
        return false;
    }

    function close() {
        if($this->isOpened) {
            fclose($this->fileHandler);
        }
    }

    function readLine($length = 0) {
        $fileHandler = $this->getFileHandler();
        if($length === 0) {
            $length = $this->getSize();
        }
        return fgets($fileHandler, $length);
    }

    function writeLine($string) {
        $fileHandler = $this->getFileHandler();
        return fputs($fileHandler, $string);
    }

    function getSize() {
        return filesize($this->fileName);
    }

    function rewind() {
        $fileHandler = $this->getFileHandler();
        return rewind($fileHandler);
    }

    function read($length = 0) {
        $fileHandler = $this->getFileHandler();
        if($length === 0) {
            $length = $this->getSize();
        }
        return fread($fileHandler, $length);
    }

    function write($string) {
        if($fileHandler = $this->getFileHandler()) {
            return fwrite($fileHandler, $string);
        }
        return false;
    }

    function passthru() {
        $fileHandler = $this->getFileHandler();
        return fpassthru($fileHandler);
    }

    function removeDuplicateLines() {
        $this->setFileMode('r');
        $fileHandler = $this->open();
        if($this->isError($fileHandler)) {
            return $fileHandler;
        }
        while($line = $this->readLine()) {
            $lines[] = $line;
        }
        $lines = array_unique($lines);
        $this->close();

        $this->setFileMode('w');
        $fileHandler = $this->open();
        if($this->isError($fileHandler)) {
            return $fileHandler;
        }

        foreach($lines as $line) {
            $this->writeLine($line);
        }
        $this->close();
        return true;
    }

    function getContents() {
        return file_get_contents($this->fileName);
    }

    function putContents($content) {
        if(function_exists('file_put_contents')) {
            return file_put_contents($this->fileName, $content);
        }
        return $this->write($content);
    }

    function chmod($mode) {
        return chmod($this->fileName, $mode);
    }

}

?>