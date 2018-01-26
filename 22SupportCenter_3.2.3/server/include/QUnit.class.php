<?php
set_include_path(CLASS_PATH.'/Pear/'.PATH_SEPARATOR.'./'.PATH_SEPARATOR.get_include_path());

/**
*   Base object creating class.
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SeHistory
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/
class QUnit {

	/**
	* Return created object
	*
	* @author 	Viktor Zeman
	* @param	string Name of a class to create,
	* @param    next parameters are optionals and are sent as arguments to object constructor
	* @return	object Created object
	*/
	function newObj($class) {
		if (!class_exists($class)) QUnit::includeClass($class);
		if (func_num_args() > 1) {
			$arg_list = func_get_args();
			$str_arg_list = '$arg_list[1]';
			for ($i = 2; $i < count($arg_list); $i++) $str_arg_list .= ', $arg_list[' . $i . ']';
			eval("\$obj = new $class($str_arg_list);");
			return $obj;
		} else {
			return new $class;
		}
	}

	function includeClass($class_name) {
		if (class_exists($class_name)) {
			return true;
		}
		$fileName = QUnit::existsClass($class_name);
		if(!$fileName) {
			if(function_exists('debug_backtrace')) {
				foreach (debug_backtrace() as $stackElement) {
					if(isset($stackElement['line']) && isset($stackElement['file'])) {
						echo sprintf("At line %s, file %s\n<br>", $stackElement['line'], $stackElement['file']);
					}
				}
			}

			die(sprintf('Fatal Error: Class %s is missing', $class_name));
		}
		require_once($fileName);
		return true;
	}


	function existsClass($className) {
		$fileName = QUnit::_existsClass($className, CLASS_PATH);
		if($fileName) {
			return $fileName;
		}
		return false;
	}

	function _existsClass($className, $pathPrefix = '') {
		$fileName = QUnit::_getFileName($className, $pathPrefix);

		if(!is_file($fileName)) {
			$fileName = QUnit::_getFileNameCaseInsensitive($className, $pathPrefix);
		}
		return $fileName;
	}

	function _getRelativeClassPath($className) {
		$classPath = explode('_', $className);
		return implode('/', $classPath);
	}

	function _getFileName($className, $pathPrefix = '') {
		$fileName = $pathPrefix . '/' . QUnit::_getRelativeClassPath($className) . '.class.php';
		return $fileName;
	}

	function _getFileNameCaseInsensitive($className, $pathPrefix = '') {
		$fileNames = glob($pathPrefix . '/' . sql_regcase(QUnit::_getRelativeClassPath($className))
		. '.class.php');
		if(!$fileNames) {
			return false;
		}
		return $fileNames[0];
	}

	function includeFile($file) {
		$fileName = CLASS_PATH . '/' . QUnit::_getRelativeClassPath($file);
		if(!is_file($fileName)) {
			if(function_exists('debug_backtrace')) {
				foreach (debug_backtrace() as $stackElement) {
					if(isset($stackElement['line']) && isset($stackElement['file'])) {
						echo sprintf("At line %s, file %s\n<br>", $stackElement['line'], $stackElement['file']);
					}
				}
			}

			die(sprintf('Fatal Error: File %s is missing', $file));
		}
		require_once($fileName);
		return true;
	}
}

global $serverDeleteTickets;
$serverDeleteTickets = false;

if (!function_exists('iconv') && function_exists('libiconv')) {
   function iconv($input_encoding, $output_encoding, $string) {
       return libiconv($input_encoding, $output_encoding, $string);
   }
}

?>