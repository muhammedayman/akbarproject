<?php

class QUnit_DataExchangeHandler_Csv {

	
    function encode($data) {
    	$file = '';
    	$file .= $this->encodeArrayAsCsv($data['result']['md']->colNames);
    	
    	foreach ($data['result']['rs']->rows as $row) {
    		$file .= $this->encodeArrayAsCsv($row);
    	}
    	$data['result'] = $file;
		
    	$encoder = QUnit::newObj('QUnit_DataExchangeHandler_Json');
		return $encoder->encode($data);
    }

    function decode($data) {
    	return $data;
    }
    
    function encodeArrayAsCsv($array) {
    	$line = '';
    	foreach ($array as $val) {
    		$line .= (strlen($line) ? ',' : '') . '"' . str_replace('"', '""', $val) . '"';
    	}
    	$line .=";\n";
    	
    	return $line;
    }
}
?>