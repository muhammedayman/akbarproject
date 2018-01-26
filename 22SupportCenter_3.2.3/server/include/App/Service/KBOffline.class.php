<?php
/**
*   Handler class for generating offline Knowledge Base items
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("App_Template");
QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_KBOffline extends QUnit_Rpc_Service {
	var $extension = '.html';
	
    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			default:
                return false;
                break;
        }
    }
    
    function checkKBDirectory() {
    	if (!strlen($this->state->config->get('knowledgeBaseURL'))) {
    		$this->state->log('error', $this->state->lang->get('KnowledgeBaseURLNotSet'), 'KnowledgeBase');
    		return false;
    	}
    	
    	if (!strlen($this->state->config->get('knowledgeBasePath'))) {
    		$this->state->log('error', $this->state->lang->get('KnowledgeBasePathNotSet'), 'KnowledgeBase');
    		return false;
    	}
    	
    	if (!file_exists($this->state->config->get('knowledgeBasePath'))) {
    		$this->state->log('error', $this->state->lang->get('KnowledgeBasePathDoesNotExist'), 'KnowledgeBase');
    		return false;
    	}
    	
    	if (!is_writeable($this->state->config->get('knowledgeBasePath'))) {
    		$this->state->log('error', $this->state->lang->get('KnowledgeBasePathIsNotWriteable'), 'KnowledgeBase');
    		return false;
    	}
    	
    	return true;
    }
    
    function full_rmdir( $dir )
    {
        if ( !is_writable( $dir ) )
        {
            if ( !@chmod( $dir, 0777 ) )
            {
    			$this->state->log('error', "Failed to remove directory $dir", 'KnowledgeBase');
            	return FALSE;
            }
        }
       
        $d = dir( $dir );
        while ( FALSE !== ( $entry = $d->read() ) )
        {
            if ( $entry == '.' || $entry == '..' )
            {
                continue;
            }
            $entry = $dir . '/' . $entry;
            if ( is_dir( $entry ) )
            {
                if ( !$this->full_rmdir( $entry ) )
                {
                    return FALSE;
                }
                continue;
            }
            if ( !@unlink( $entry ) )
            {
                $d->close();
    			$this->state->log('error', "Failed to delete file $entry", 'KnowledgeBase');
                return FALSE;
            }
        }
        $d->close();
        rmdir( $dir );
        return TRUE;
    }    
    
    function removeAllExistingFiles() {
        $d = dir( $this->state->config->get('knowledgeBasePath') );
        while ( false !== ( $entry = $d->read() ) )
        {
            if ( $entry == '.' || 
            	$entry == '..' || 
            	$entry == '.svn' || 
            	$entry == 'css' || 
            	$entry == 'js' || 
            	$entry == 'img' || 
            	$entry = 'submit_form.php' || 
            	$entry = 'kb_search.php' || 
            	$entry = 'search.php')
            {
                continue;
            }
            $entry = $this->state->config->get('knowledgeBasePath') . $entry;
            if ( is_dir( $entry ) )
            {
                if ( !$this->full_rmdir( $entry ) )
                {
                    return false;
                }
                continue;
            }
            if ( !@unlink( $entry ) )
            {
                $d->close();
    			$this->state->log('error', "Failed to delte file $entry", 'KnowledgeBase');
                return false;
            }
        }
        $d->close();
        return true;
    }

    
    function loadChildItems($tree_path) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        
        $objKB = QUnit::newObj('App_Service_KBItems');
        $objKB->state = $this->state;
        $objKB->response = $response;
        $params = $this->createParamsObject();
        $params->set('tree_path', $tree_path);
        $params->set('all_columns', true);
        
        $items = array();
    	//load ticket
   		if ($ret = $objKB->getItemsList($params)) {
   			$res = & $objKB->response->getByRef('result');
   			if ($res['count'] > 0) {
				$items = $res['rs']->getRows($res['md']);
   			}
   		}
    	return $items;
    }

    function loadItemAttachments($item_id) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        
        $objKB = QUnit::newObj('App_Service_Attachments');
        $objKB->state = $this->state;
        $objKB->response = $response;
        $params = $this->createParamsObject();
        $params->set('item_id', $item_id);
        
        $items = array();
    	//load 
   		if ($ret = $objKB->getKBAttachmentsList($params)) {
   			$res = & $objKB->response->getByRef('result');
   			if ($res['count'] > 0) {
				$items = $res['rs']->getRows($res['md']);
   			}
   		}
    	return $items;
    }
    
    function loadSimiraItemsToItem($item_id) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        
        $objKB = QUnit::newObj('App_Service_KBItems');
        $objKB->state = $this->state;
        $objKB->response = $response;
        $params = $this->createParamsObject();
        $params->set('item_id', $item_id);
        $params->set('limit', 10);
        
        $items = array();
    	//load
   		if ($ret = $objKB->getSimilarItemsToItem($params)) {
   			$res = & $objKB->response->getByRef('result');
   			if ($res['count'] > 0) {
				$items = $res['rs']->getRows($res['md']);
   			}
   		}
    	return $items;
    }
    
    
    function generateKBIndexFile() {
    	
    	$tree_path = '0|';
    	//load childs
		$childs = $this->loadChildItems($tree_path);
		
		foreach ($childs as $id => $child) {
			if ($child['children_count'] > 0) {
				$childs[$id]['children'] = $this->loadChildItems($tree_path . $child['item_id'] . '|');
			}
		}
		
    	//generate index file
		if (!$this->generateIndexFile($this->state->config->get('knowledgeBasePath') . 'index' . $this->extension, SERVER_PATH . 'templates/knowledgebase/index.inc.php', $childs)) {
    		$this->state->log('error', "Failed to create Knowledge Base index file", 'KnowledgeBase');
			return false;
		}

		//generate the rest of files
		foreach ($childs as $id => $child) {
			if (!$this->generateKBItemFile($child, array(array('children' => $childs)))) {
				return false;
			}
		}
		
		
    	return true;
    }

    function generateIndexFile($filename, $template, $childs) {
    	$params = array(
    		'knowledgeBaseURL' => $this->state->config->get('knowledgeBaseURL'),
    		'knowledgeBasePath' => $this->state->config->get('knowledgeBasePath'),
    		'applicationURL' => $this->state->config->get('applicationURL'),
    		'fileExtension' => $this->extension,
    		'pageTitle' => 'SupportCenter',
    		'metaDescription' => 'SupportCenter Knowledgebase',
    		'crumbs' => '',
    		'languages' => ''
		);

	    global $state;
		global $config;
		$arrLanguages = $state->lang->getAvaibleLanguages();
		foreach ($arrLanguages as $language) {
			if (strlen($language[0])) {
				if ($config->get('defaultLanguage') == $language[0]) {
					$params['languages'].= '<option value="' . $language[0] . '" selected>' . $language[0] . '</option>';
				} else {
					$params['languages'].= '<option value="' . $language[0] . '">' . $language[0] . '</option>';
				}
			}
		}
    		
    	ob_start();
    	include($template);
    	$params['content'] = ob_get_contents();
    	ob_end_clean();

    	return $this->saveToFile($filename, App_Template::loadTemplateContent('knowledgebase/page_layout.html', $params));
    }

    function generateItemFile($filename, $template, $item, $parents) {
    	
    	//init params
    	$params = array(
    		'knowledgeBaseURL' => $this->state->config->get('knowledgeBaseURL'),
    		'knowledgeBasePath' => $this->state->config->get('knowledgeBasePath'),
    		'applicationURL' => $this->state->config->get('applicationURL'),
    		'fileExtension' => $this->extension,
    		'metaDescription' => $item['metadescription'],
    		'crums' => '',
    		'languages' => '',
    		'pageTitle' => ''
		);

		//prepare page title variable
		foreach ($parents as $parent) {
			if (strlen($parent['fileurl'])) {
				$params['pageTitle'] = $parent['subject'] . ' | ' . $params['pageTitle'];
			}
		}
		$params['pageTitle'] = $item['subject'] . ' | ' . $params['pageTitle'];
		
		//prepare crumbs
		foreach ($parents as $parent) {
			if (strlen($parent['fileurl'])) {
				$params['crumbs'] .= ' &gt;&gt; <a href="' . $parent['fileurl'] . '">' . $parent['subject'] . '</a>';
			}
		}
		$params['crumbs'] .= ' &gt;&gt; <a href="' . $item['fileurl'] . '">' . $item['subject'] . '</a>';		
		
	    global $state;
		global $config;
		$arrLanguages = $state->lang->getAvaibleLanguages();
		foreach ($arrLanguages as $language) {
			if (strlen($language[0])) {
				if ($config->get('defaultLanguage') == $language[0]) {
					$params['languages'].= '<option value="' . $language[0] . '" selected>' . $language[0] . '</option>';
				} else {
					$params['languages'].= '<option value="' . $language[0] . '">' . $language[0] . '</option>';
				}
			}
		}
		
		
    	ob_start();
    	include($template);
    	$params['content'] = ob_get_contents();
    	ob_end_clean();

    	return $this->saveToFile($filename, App_Template::loadTemplateContent('knowledgebase/page_layout.html', $params));
    }
    
    
    function generateKBItemFile($item, $parents) {
    	
		//load childs of this item
    	if ($item['children_count'] > 0 && !isset($item['children'])) {    	
			$item['children'] = $this->loadChildItems($item['tree_path'] . $item['item_id'] . '|');
		}
		
		if ($item['attachments_count'] > 0 && !isset($item['attachments'])) {
			$item['attachments'] = $this->loadItemAttachments($item['item_id']);
		}
		
		$item['similar_items'] = $this->loadSimiraItemsToItem($item['item_id']);
		
		$urlParents = '';
		foreach ($parents as $parent) {
			if (strlen($parent['url'])) {
				$urlParents .= $parent['url'] . '/';
			}
		}
		
		$item['fileurl'] = $this->state->config->get('knowledgeBaseURL') . $urlParents . $item['url'] . $this->extension;
		
		//generate index file
		if (!$this->generateItemFile($this->state->config->get('knowledgeBasePath') . $urlParents . $item['url'] . $this->extension, 
									SERVER_PATH . 'templates/knowledgebase/item.inc.php', 
									$item, $parents)) {
    			$this->state->log('error', "Failed to create file " . $this->state->config->get('knowledgeBasePath') . $urlParents . $item['url'] . $this->extension, 'KnowledgeBase');
				return false;
		}

		if (is_array($item['children'])) {
			foreach ($item['children'] as $child) {
				if (!$this->generateKBItemFile($child, array_merge($parents, array($item)))) {
					return false;
				}
			}
		}
    	return true;
    }
    
    
    function createAllDirs($dirname) {
    	if (!is_dir($dirname)) {
    		$dirname = str_replace($this->state->config->get('knowledgeBasePath'), '', $dirname);
		    $folder = preg_split( "/[\\\\\/]/" , $dirname );
		    $mkfolder = '';
		    for(  $i=0 ; isset( $folder[$i] ) ; $i++ )
		    {
		        if(!strlen(trim($folder[$i]))) {
		        	continue;
		        }
		        $mkfolder .= $folder[$i];
		        if( !is_dir( $this->state->config->get('knowledgeBasePath') . $mkfolder ) ) {
		            mkdir( $this->state->config->get('knowledgeBasePath') . $mkfolder ,  0777);
		        }
		        $mkfolder .= '/';
		    }
    	}
    	
    	return true;
    }
    
    function saveToFile($fileName, $content) {
    	//check if all required directories exist
		if (!$this->createAllDirs(dirname($fileName))){
    		$this->state->log('error', "Failed to create directory structure for: " . $fileName, 'KnowledgeBase');
			return false;
		}
    	
    	if ($fp = fopen($fileName, "w")) {
    		fwrite($fp, $content);
    		fclose($fp);
    		if (!chmod($fileName, 0777)) {
		    	$this->state->log('error', "Failed to set permissions for file: $fileName", 'KnowledgeBase');
		    	return false;
    		}
    		return true;
    	}
    	$this->state->log('error', "Failed to create file $fileName", 'KnowledgeBase');
    	return false;
    }
    
    function generateKnowledgeBaseFiles() {
    	
    	if ($this->state->config->get('knowledgeBaseModule') != 'y') {
    		return true;
    	}
    	
    	if (!$this->checkKBDirectory()) {
    		return false;
    	}
    	
    	if (!$this->removeAllExistingFiles()) {
    		$this->state->log('error', $this->state->lang->get('FailedToDeleteOldKBFiles'), 'KnowledgeBase');
    		return false;
    	}
    	
    	if (!$this->generateKBIndexFile()) {
    		$this->state->log('error', $this->state->lang->get('FailedToGenerateKBItemFile'), 'KnowledgeBase');
    		return false;
    	}
    	
    	return true;
    }
}
?>