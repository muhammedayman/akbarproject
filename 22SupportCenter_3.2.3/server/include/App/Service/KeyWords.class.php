<?php
/**
*   Handler class for Key Words handling
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_KeyWords extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			default:
                return false;
                break;
        }
    }

    
    function getWordSeparators() {
    	return array('{', '}', '|', '=', '„', '+', '@', '+', '-', '\\', 
    	';', ',', '.', '<', '>', '?', '`', '~', '!', '#', '^', '&', '*', 
    	'(', ')', '[', ']', '"', "'", ':', '/', "\n", "\t", "\r", "’", "“");
    }
    
    function strtolower($val) {
    	if (function_exists('mb_strtolower')) {
    		return mb_strtolower($val, 'UTF-8');
    	} else {
    		return strtolower($val);
    	}
    }
    
    function getWords($content) {
    	if (strlen($content)) {
    		$content = $this->strtolower(str_replace($this->getWordSeparators(), ' ', $content));
    		$arr = explode(' ', $content);
    		
    		$retArr = array();
    		$count = 0;
    		foreach ($arr as $val) {
    			if ($this->isValidWord($val)) {
    				$count ++;
    				$val = $val;
	    			if (isset($retArr[$val])) {
	    				$retArr[$val]++;
	    			} else {
	    				$retArr[$val] = 1;
	    			}
    			}
    		}
    		
    		return array($count, $retArr);
    	} else {
    		return array(0, array());
    	}
    }

    function loadStopWords() {
   		$arr = explode("\n", file_get_contents(SERVER_PATH . 'settings/stopwords.txt'));
   		$ret = array();
   		foreach ($arr as $word) {
   			$word = trim($word);
   			if (strlen($word)) $ret[] = trim($word);
   		}
   		
   		return array_unique($ret);
    }
    
    function isStopWord($word) {
    	static $arrStopWords;
    	if (empty($arrStopWords)) {
    		$arrStopWords = $this->loadStopWords();
    	}
       // return array_search($this->strtolower($word), $arrStopWords) !== false;
    	return $this->arraySearch($this->strtolower($word), $arrStopWords) !== false;
    }

    function arraySearch($needle, $array) {
        foreach ($array as $id => $item) {
            if (strcmp($item, $needle) == 0) {
                return $id;
            }
        }
        return false;
    }
    
    function isValidWord($word) {
    	if ($this->strlen(trim($word)) < 3 || $this->isStopWord($word)) return false;
    	return true;
    }
    
    function strlen($word) {
    	if (function_exists('iconv_strlen')) {
    		return iconv_strlen($word);
    	} else if (function_exists('mb_strlen')) {
    		return mb_strlen($word, 'UTF-8');
    	} else {
    		return strlen($word);
    	}
    }
    
    function addMissingWords($arrWords) {
    	$db = $this->state->get('db'); 
    	
		$sql = "INSERT IGNORE INTO words (word_id, word, ranking) VALUES ";
		$values = '';
		foreach ($arrWords as $word => $count) {
			$word = addslashes(trim($word));
			$word_id = md5($word);
			$values .= (strlen($values) ? ',' : '') . "('$word_id','$word',1)";
		}
		if (strlen(trim($values))) {
			$sql .= $values;
			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
			    return false;
			}
		}
    	return true;
    }
    
    /********************************* Knowledge Base *************************/
    /**
     * Delete Word assignments from KB item
     * Waits item_id as parameter
     *
     * @param unknown_type $params
     * @return unknown
     */
    function deleteKBItemWordAssignments($params) {
    	$db = $this->state->get('db'); 
        $params->set('table', 'kb_item_words');
        $params->set('where', "item_id = '".$db->escapeString($params->get('item_id'))."'");
        return $this->callService('SqlTable', 'delete', $params);
    }
    
    /**
     * Awaiting parameters are ticket_id and body
     *
     * @param unknown_type $params
     * @return unknown
     */
    function updateSearchIndexForKBItem($params) {
    	$db = $this->state->get('db'); 

    	$sql = "UPDATE kb_items SET is_indexed='y' WHERE item_id='" . $params->get('item_id') . "'";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(QUnit_Object::isError($sth)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
    	
    	//delete current assignments of words
        if (!$this->callService('KeyWords', 'deleteKBItemWordAssignments', $params)) {
			$this->state->log('error', 'Failed to delete words assignments', 'WordIndex');
        	return false;
        }
        
        $txt = $params->get('subject') . " " . 
        				  $params->get('subject') . " " . 
        				  $params->get('metadescription') . " " . 
        				  $params->get('body');

        //get unique list of words
    	list ($allWords, $arrWords) = $this->getWords(strip_tags($txt));
        
        //add missing words to table
        if (!$this->addMissingWords($arrWords)) {
        	return false;
        }
        
    	//add new assignments of words
    	if ($this->createKBItemWordAssignments($params->get('item_id'), $arrWords, $allWords)) {
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * Add assignments of words to KB Item
     *
     * @param unknown_type $ticket_id
     * @param unknown_type $arrWords
     * @return unknown
     */
    function createKBItemWordAssignments($item_id, $arrWords, $allWords) {
    	$db = $this->state->get('db'); 
    	
    	$sql = "INSERT IGNORE INTO kb_item_words (item_id, word_id, word_ranking)
				VALUES ";
    	$values = '';
    	foreach ($arrWords as $word => $count) {
   			$values .= (strlen($values) ? ',' : '') . "('" . $db->escapeString($item_id) . "','" . md5($word) . "', " . intval(1+($count-1)/$allWords) . ")";
    	}

    	if (strlen(trim($values))) {
    		$sql .= $values;
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
			    return false;
    		}
    	}

    	return $this->updateWordsRanking(array_keys($arrWords));
    }


    function updateWordsRanking($words) {
    	$db = $this->state->get('db'); 
		$response =& $this->getByRef('response');

		$where = '';
    	if (!is_array($words)) {
    		$where = " WHERE word_id='" . md5($words) . "'";
    	} else if(is_array($words)) {
    		
    		$values = '';
    		foreach ($words as $val) {
    			if(strlen($val)) {
    				$values .= (strlen($values) ? ',' : '') . "'" . md5($val) . "'";
    			}
    		}
    		if (strlen($values)) {
    			$where = " WHERE word_id IN (" . $values . ")"; 
    		}
    		
    	}
		
		$sql = "UPDATE words w SET ranking = 1 - ((SELECT count(*) FROM kb_item_words kbw WHERE kbw.word_id = w.word_id) / (SELECT count(*) FROM kb_item_words))";
		$sql .= $where;
		
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(QUnit_Object::isError($sth)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        return true;
	}
 
    function recomputeMissingKBWordIndex($params = false) {
    	$db = $this->state->get('db'); 
    	$params = $this->createParamsObject();
		$response =& $this->getByRef('response');
    	//SELECT just mails with missing word index
    	$sql = "SELECT item_id, subject, body, metadescription FROM kb_items WHERE is_indexed='n'";
    	 
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(QUnit_Object::isError($sth)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');

        while($row = $sth->fetchRow()) {
        	$params->set('item_id', '' . $row[0]);
        	$params->set('subject', '' . $row[1]);
        	$params->set('body', '' . $row[2]);
        	$params->set('metadescription', '' . $row[3]);
        	if (!$this->updateSearchIndexForKBItem($params)) {
				$this->state->log('error', 'Failed to update words assignments', 'WordIndex');
	        	return false;
	        }
        }
        return true;
	}
}
?>