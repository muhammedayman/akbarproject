<?php
/**
*   Handler class for Files
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Files extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
        	case 'getFilesList':
        	case 'deleteFile':
        		return $this->callService('Users', 'authenticateAdmin', $params);
        	default:
                return false;
                break;
        }
    }
    
    function loadFile($fileId = false, $etag = false) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $file = false;

        if ($fileId || $etag) {
            $params = $this->createParamsObject();
            if ($fileId) $params->set('file_id', $fileId);
            if ($etag) $params->set('etag', $etag);
            if ($ret = $this->callService('Files', 'getFilesList', $params)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $file = $res['rs']->getRows($res['md']);
                    $file = $file[0];
                }
            }
        }
        return $file;
        
    }
    
    function uploadFile() {
    	global $_FILES;
    	$response =& $this->getByRef('response');
    	echo '<textarea>';
		$session = QUnit::newObj('QUnit_Session');
		$params = $this->createParamsObject();
		$paramFile = $this->createParamsObject();
		$paramFile->setField('filename', $_FILES["file"]["name"]);
		$paramFile->setField('filesize', $_FILES["file"]["size"]);
		$paramFile->setField('filetype', $_FILES["file"]["type"]);
        $paramFile->setField('etag', $this->getEtag($_FILES["file"]));
        
		if ($ret = $this->callService('Files', 'insertFile', $paramFile)) {
			$paramsContent = $this->createParamsObject();
			$paramsContent->setField('file_id', $paramFile->get('file_id'));
			$paramsContent->set('filename', $_FILES["file"]["tmp_name"]);
			if ($ret = $this->callService('Files', 'loadFileToDb', $paramsContent)) {
				echo '{filename:"'.$_FILES["file"]["name"].
				'", hash:"'.$paramFile->get('file_id').
				'", size: "'.$_FILES["file"]["size"].
				'", type: "'.$_FILES["file"]["type"].'"}';
			} else {
				echo '{"result": null,  "error": "Failed to save content of file to database: ' . $response->get('error') . '"}';
			}
		} else {
			echo '{"result": null, "error": "Failed to save file: '. $response->get('error') . '"}'; 
		}
    	echo '</textarea>';
    }

    function getEtag($file) {
        return md5($file['tmp_name'] . $file['size'] . '|' . filectime($file['tmp_name']));
    }
    
    function uploadFileLocal($file) {
    	$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		$params = $this->createParamsObject();
		$paramFile = $this->createParamsObject();
		$paramFile->setField('filename', $file["name"]);
		$paramFile->setField('filesize', $file["size"]);
		$paramFile->setField('filetype', $file["type"]);
		$paramFile->setField('etag', $this->getEtag($file));
		
		if ($ret = $this->callService('Files', 'insertFile', $paramFile)) {
			$paramsContent = $this->createParamsObject();
			$paramsContent->setField('file_id', $paramFile->get('file_id'));
			$paramsContent->set('filename', $file["tmp_name"]);
			if ($ret = $this->callService('Files', 'loadFileToDb', $paramsContent)) {
				return $paramFile->get('file_id');
			} else {
				$response->set('error', 'Failed to save content of file to database: ' . $response->get('error'));
				return false;
			}
		} else {
			return false;
		}
    }
     
    
    function insertFile(&$params) {
    	$db = $this->state->get('db'); 
        $params->set('table', 'files');
        if (!strlen($params->getField('file_id'))) {
        	$params->setField('file_id', md5(rand() . time()));
        }
        if (!strlen($params->getField('created'))) {
        	$params->setField('created', $db->getDateString());
        }
        if (!strlen($params->getField('downloads'))) {
        	$params->setField('downloads', 0);
        }
        return $this->callService('SqlTable', 'insert', $params);
    }
    
    function loadFileToDb($params) {
    	$filename = $params->get('filename');
    	if (!strlen($filename)) {
    		return false;
    	}
    	
    	$params->set('table', 'file_contents');
    	$iteration = 0;
    	if (file_exists(get_cfg_var('upload_tmp_dir').$filename)) {
    		$tmp_file = get_cfg_var('upload_tmp_dir').$filename;
    	} else {
    		$tmp_file = $filename;
    	}
    	$fp = fopen($tmp_file, 'r');
    	if (!$fp) {
    		return false;
    	}
    	while (!feof($fp)) {
	    	$params->setField('content', fread($fp, 500000));
	    	$params->setField('content_nr', $iteration);
	      	if (!$this->callService('SqlTable', 'insert', $params)) {
	      		fclose($fp);
	      		return false;
	      	}
	      	$iteration++;
    	}
		fclose($fp);      	
      	return true;
    }
   
    function downloadFileFromDb($file_id) {
    	$db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');
    	$sql = 'SELECT content FROM file_contents
		WHERE file_id = \'' . $db->escapeString($file_id) . "'
		ORDER BY content_nr";
    	
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        
        while ($row = $sth->fetchRow()) {
        	echo $row[0];
        }

        //increment downlods counter
    	$sql = 'UPDATE files SET downloads = downloads + 1
		WHERE file_id = \'' . $db->escapeString($file_id) . "'";
    	
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        
        return true;
    }

    function getFileContent($file_id) {
    	$db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');
    	 
    	$sql = 'SELECT content FROM file_contents
		WHERE file_id = \'' . $db->escapeString($file_id) . "'
		ORDER BY content_nr";
    	
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        
        $content = '';
        while ($row = $sth->fetchRow()) {
        	$content .= $row[0];
        }
        return $content;
    }
    
    function getFilesList($params) {
        $db =& $this->state->getByRef('db');

        $params->set('columns', "*");
        $params->set('from', "files");
        $where = "1";
    	if($id = $params->get('file_id')) {
    		$where .= " and file_id = '".$db->escapeString($id)."'";
    	}
        if($id = $params->get('etag')) {
            $where .= " and etag = '".$db->escapeString($id)."'";
        }
    	if($id = $params->get('created_from')) {
			$where .= " AND created > '" . $db->escapeString($id) . "'";
		}
		if($id = $params->get('created_to')) {
			$where .= " AND created < '" . $db->escapeString($id) . "'";
		}
    	$params->set('where', $where);
        $params->set('table', 'files');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    
    function printHeaders($file_id, $download_type = 'attachment') {
    	$db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');

    	$sql = 'SELECT * FROM files WHERE file_id = \'' . $db->escapeString($file_id) . "'";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $rs->setRows($sth->fetchAllRows());
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'files');
        $md->setColumnNames($sth->getNames());
        $md->setColumnTypes($sth->getTypes());
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $rows = $rs->getRows($md);

        if (count($rows) != 1) {
			return false;
		}
		header('Content-type: ' . $rows[0]['filetype']);
		if ($download_type == 'attachment') {
		    header('Pragma: public'); 
			header('Content-Disposition: attachment; filename="' . htmlspecialchars($rows[0]['filename']) . '"');
		}
		header('Content-length: ' . $rows[0]['filesize']);
    	return true;
    }
    
    function downloadFile($file_id, $download_type = 'attachment') {
   		if ($this->printHeaders($file_id, $download_type)) {
   			return $this->downloadFileFromDb($file_id);
   		}
    	return false;
    }
    
    /**
     * Remove all files without reference in system
     */
    function cleanupFiles($delay = 3600) {
    	$db =& $this->state->getByRef('db');
    	$limit = 500;
    	
    	$sql = "SELECT files.file_id 
					FROM files 
						LEFT JOIN users ON (users.picture_id=files.file_id) 
						LEFT JOIN kb_item_files ON (kb_item_files.file_id=files.file_id) 
                        LEFT JOIN product_files ON (product_files.file_id=files.file_id) 
						LEFT JOIN mail_attachments ma ON (ma.file_id = files.file_id) 
					WHERE TO_DAYS(files.created) <= (TO_DAYS('" . $db->getDateString(time() - $delay) . "')) AND 
						ma.file_id IS NULL AND 
						users.picture_id IS NULL AND
						kb_item_files.file_id IS NULL AND
                        product_files.file_id IS NULL 
					LIMIT 0,$limit";
   		$sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   		if(!$this->_checkDbError($sth)) {
   			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
   			return false;
   		}
    	$arr = $sth->fetchAllRows();
   		$file_ids = '';
    	
   		//at once delete max 100 files
   		foreach ($arr as $id => $row) {
   			if (strlen(trim($row[0])) && $id < $limit) {
   				$file_ids .=(strlen($file_ids) ? ',' : '') . "'" . $row[0] . "'";
   			}
   		}
   		if (strlen($file_ids)) {
	    	$sql = "DELETE FROM file_contents 
					WHERE file_id IN ($file_ids)";
	   		$sth = $db->execute($sql);
	   		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
	   		if(!$this->_checkDbError($sth)) {
	   			$this->state->log('error', $sth->get('errorMessage'), 'Queue');
	   			return false;
	   		}
	    	$sql = "DELETE FROM files WHERE file_id IN ($file_ids)";
	   		$sth = $db->execute($sql);
	   		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
	   		if(!$this->_checkDbError($sth)) {
	   			$this->state->log('error', $sth->get('errorMessage'), 'Queue');
	   			return false;
	   		}
   		}
   		if (count($arr)>($limit-1)) return $this->cleanupFiles($delay);
   		return true;
    }
    
    
  /**
     *  delete file
     *
     *  @param string table
     *  @param string id of task
     *  @return boolean
     */
    function deleteFile($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
		if ($this->isDemoMode()) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	$ids = explode('|',$params->get('file_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('file_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}


    	//delete assignments of file to mails
    	$sql = "delete from mail_attachments where file_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage') , 'Files');
    		return false;
    	}

        //delete file_contents
        $sql = "delete from file_contents where file_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage') , 'Files');
            return false;
        }
    	
    	
    	//delete assignments of file to knowledge items
    	$sql = "delete from kb_item_files where file_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage') , 'Files');
    		return false;
    	}

    	//delete assignments of file to user
    	$sql = "UPDATE users SET picture_id=NULL WHERE picture_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage') , 'Files');
    		return false;
    	}
    	
    	$params->set('table', 'files');
    	$params->set('where', "file_id IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
    }
    
    
}
?>