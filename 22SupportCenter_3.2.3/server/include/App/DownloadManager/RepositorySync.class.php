<?php
/**
 *   Handler class for Repository Synchronisation
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_DownloadManager_RepositorySync extends QUnit_Rpc_Service {
    var $rootFolder = '';
    var $aclFile = '';

    var $extenstions = array(
        'js' => 'application/javascript',
        'json' => 'application/json',
        'doc' => 'application/msword', 
        'dot' => 'application/msword', 
        'bin' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'so' => 'application/octet-stream',
        'iso' => 'application/octet-stream',
        'dmg' => 'application/octet-stream',
        'dist' => 'application/octet-stream',
        'distz' => 'application/octet-stream',
        'pkg' => 'application/octet-stream',
        'bpk' => 'application/octet-stream',
        'dump' => 'application/octet-stream',
        'elc' => 'application/octet-stream',
        'pgp' => 'application/pgp-encrypted',           
        'ps' => 'application/postscript',           
        'eps' => 'application/postscript',           
        'ai' => 'application/postscript',           
        'rss' => 'application/rss+xml',           
        'asf' => 'application/vnd.ms-asf',           
        'cab' => 'application/vnd.ms-cab-compressed',           
        'xls' => 'application/vnd.ms-excel',           
        'xlm' => 'application/vnd.ms-excel',           
        'xla' => 'application/vnd.ms-excel',           
        'xlc' => 'application/vnd.ms-excel',           
        'xlt' => 'application/vnd.ms-excel',           
        'xlw' => 'application/vnd.ms-excel',           
        'chm' => 'application/vnd.ms-htmlhelp',           
        'ppt' => 'application/vnd.ms-powerpoint',           
        'pps' => 'application/vnd.ms-powerpoint',           
        'pot' => 'application/vnd.ms-powerpoint',           
        'exe' => 'application/x-msdownload',           
        'dll' => 'application/x-msdownload',           
        'com' => 'application/x-msdownload',           
        'bat' => 'application/x-msdownload',           
        'msi' => 'application/x-msdownload',           
        'xml' => 'application/xml',           
        'xsl' => 'application/xml',           
        'jpeg' => 'image/jpeg',           
        'jpg' => 'image/jpeg',           
        'jpe' => 'image/jpeg',           
        'tif' => 'image/tiff',           
        'html' => 'text/html',           
        'htm' => 'text/html',           
        'txt' => 'text/plain',           
        'log' => 'text/plain'           
    
        );

        function execute($rootFolder, $aclFile = '') {
            $this->rootFolder = rtrim(str_replace('\\', '/', $rootFolder), '/') . '/';
            $this->aclFile = $aclFile;

            if (strlen($this->rootFolder)) {
                $this->disableAllProducts();
                $this->removeAssignmentsOfFilesToProducts();
                $this->importDir();
                $this->importACL();
                $this->cleanupFiles();
            }
            return true;
        }


        function importACL() {
            echo 'Import Access Control List' . "\n";
            $response =& $this->getByRef('response');
            if ($file = file($this->aclFile)) {
                if (is_array($file)) {
                    foreach ($file as $line) {
                        $line = trim($line);
                        if (strlen($line)) {
                            $attributes = explode("\t", $line);
                            $fileName = array_shift($attributes);
                            $productId = md5(trim($fileName, '/'));
                            foreach ($attributes as $group) {
                                if (strlen($group)) {
                                    $paramsOrder = $this->createParamsObject();
                                    $paramsOrder->setField('orderid', md5($group . '|' . $productId));
                                    $paramsOrder->setField('groupid', $group);
                                    $paramsOrder->setField('productid', $productId);
                                    if (!$this->callService('ProductOrders', 'insertOrder', $paramsOrder)) {
                                        echo "Failed to assign group $group to $fileName with message: " . $response->error . "\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        function cleanupFiles() {
            echo 'Delete not used files from database' . "\n";
            $files = QUnit::newObj('App_Service_Files');
            $files->state = $this->state;
            $files->response = $response;
            $files->cleanupFiles(0);

            echo 'Optimize mysql tables' . "\n";
            $maintainance = QUnit::newObj('App_Service_Maintainance');
            $maintainance->state = $this->state;
            $maintainance->response = $response;
            $maintainance->runOptimizeTables();
        }

        function disableAllProducts() {
            $db = $this->state->get('db');

            $sql = "UPDATE products set is_enabled='n'";

            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
            if(QUnit_Object::isError($sth)) {
                $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
                return false;
            }
            return true;
        }

        function removeAssignmentsOfFilesToProducts() {
            $db = $this->state->get('db');

            $sql = "DELETE from product_files";

            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
            if(QUnit_Object::isError($sth)) {
                $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
                return false;
            }
            return true;
        }


        function importDir($relativeName = '', $tree_path = '0|', $productId = '') {
            $db = $this->state->get('db');
            $response =& $this->getByRef('response');
            $dirName = rtrim($this->rootFolder . $relativeName, '/');
            if (!file_exists($dirName)) {
                echo 'Directory doesn\'t exists (' . $dirName . ')';
                return false;
            }
            $d = dir($dirName);
            while ( false !== ( $entry = $d->read() ) )
            {
                if ( $entry == '.' || $entry == '..') {
                    continue;
                }

                $fileName = $relativeName . '/' . $entry;
                if ( is_dir( $this->rootFolder . $fileName ) ) {
                    echo 'Create Product: ' . $fileName . "\n";
                    //create product or update existing product
                    $productParams = $this->createParamsObject();
                    $productParams->setField('productid', md5(trim($fileName, '/')));
                    $productParams->setField('subtitle', trim($fileName, '/'));
                    $productParams->setField('name', $entry);
                    $productParams->setField('description', '');
                    $productParams->setField('created', $db->getDateString(filemtime($this->rootFolder . $fileName)));
                    $productParams->setField('product_code', ' ');
                    $productParams->setField('is_enabled', 'y');
                    $productParams->setField('tree_path', $tree_path);
                    if ($this->callService('Products', 'insertProduct', $productParams)) {
                        $this->importDir($fileName, $tree_path . $productParams->get('productid') . '|', $productParams->get('productid'));
                    } else {
                        $productParams->unsetField('send_notification');
                        if ($this->callService('Products', 'updateProduct', $productParams)){
                            $this->importDir($fileName, $tree_path . $productParams->get('productid') . '|', $productParams->get('productid'));
                        }
                    }
                } else if (is_file($this->rootFolder . $fileName) && strlen($productId)) {
                    //save file to db and save also etag
                    $file = array();
                    $file['name'] = $entry;
                    $file['tmp_name'] = $this->rootFolder . $fileName;
                    $file['size'] = filesize($file['tmp_name']);
                    $extension = explode('.', $entry);
                    $file['type'] = $this->extensionToContentType($extension[count($extension)-1]);

                    $objFile = QUnit::newObj('App_Service_Files');
                    $objFile->response = $response;
                    $objFile->state = $this->state;

                    //check if same file is not already uploaded
                    if ($fileObj = $objFile->loadFile(false, $objFile->getEtag($file))) {
                        $fileId = $fileObj['file_id'];
                        echo 'Use existing file: ' . $fileName . "\n";
                    } else {
                        echo 'Upload new file: ' . $fileName . "\n";
                        $fileId = $objFile->uploadFileLocal($file);
                    }
                    //assign product to file
                    $attachmentParams = $this->createParamsObject();
                    $attachmentParams->setField('productid', $productId);
                    $attachmentParams->setField('file_id', $fileId);
                    if (!$this->callService('Attachments', 'insertDmAttachment', $attachmentParams)) {
                        echo 'Failed to attach file with error: ' . $response->error;
                        $this->state->log('error', 'Failed to attach file with error: ' . $response->error, 'DownloadManager');
                        return false;
                    }
                }
            }
            $d->close();
            return true;

        }

        function extensionToContentType($extension) {
            if (in_array($extension, $this->extenstions)) {
                return $this->extenstions[$extension];
            }
            return 'application/' . $extension;
        }
}
?>