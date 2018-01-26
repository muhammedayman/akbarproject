<?php
/**
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Lang
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

class QUnit_Lang_Parser {

	var $translations;
	var $language;
	var $defaultLanguage;
	
	function QUnit_Lang_Parser() {
		$this->init();
	}
	
	/**************************** Parsing files ***************************/
	
	function generateAllTranslationsFile() {
		$all_translations = array_unique(array_merge($this->findFiles(APP_PATH . '/server'), $this->findFiles(APP_PATH . '/client')));
		$arr = array();
		foreach ($all_translations as $translation){
			if (strlen($translation)) {
				$arr[$translation] = '***' . $translation . '***';
			}
		}
		$this->translations['all_translations'] = $arr;
	}
	
	//recurse through all files

	function findFiles($fileName) {
		$translations = array();
		if (is_dir($fileName)) {
			$directory = dir($fileName);
			while (false !== ($entry = $directory->read())) {
				if (!in_array($entry, array('..', '.', '.svn', '.settings', '.cache', '.metadata'))) {
					$ret = $this->findFiles($fileName . '/' . $entry);
					$translations = array_merge($translations, $ret);
				}
			}
		} else {
			$translations = $this->getTranslations($fileName);				
		}
		return array_unique($translations);
	}
	
	function getTranslations($fileName) {
		$pathinfo = pathinfo($fileName);
		if (!isset($pathinfo['extension'])) {
			return array();
		}
		switch (strtolower($pathinfo['extension'])) {
			case 'php':
				$pattern = '/lang-\>get\(["\']([a-zA-Z0-9_]+?)["\']/ms';
				break;
			case 'js':
				if (strpos($fileName, '.xd.js') !== false) {
					return array();
				}
				$pattern = '/i18n\.get\(["\']([a-zA-Z0-9_]+?)[\'"]\)/ms';
				break;
			case 'html':
				$pattern = '/\$\{app\.i18n\.([a-zA-Z0-9_]+?)\}/ms';
				break;
			default:
				return array();
		}
		$content = file_get_contents($fileName);
		if (preg_match_all($pattern, $content, $match, PREG_PATTERN_ORDER) > 0) {
			return $match[1];
		} else {
			return array();
		}
	}
	
	/**************************** Synchronization of files ****************/
	
	function init() {
		if (isset($_GET['language']) && strlen($_GET['language'])) {
			$this->language = $_GET['language'];
		} else if (isset($_COOKIE['language']) && strlen($_COOKIE['language'])) {
			$this->language = $_COOKIE['language'];
		}
		$this->translations[$this->language] = $this->loadTranslation($this->language);
	}
	
	function setDefaultLanguage($language) {
		if (!strlen(trim($language))) {
			$language = 'English';
		}
		$this->defaultLanguage = $language;
		$this->addTranslation($language);
	}
	
	function addTranslation($language) {
		if (!isset($this->translations[$language])) {
			$this->translations[$language] = $this->loadTranslation($language);
		}
	}
	
	function removeNotUsed($srcLang, $baseLang="English") {
		if ($srcLang == $baseLang) return true;
		foreach ($this->translations[$srcLang] as $id=>$val) {
			if (!isset($this->translations[$baseLang][$id])) {
				unset($this->translations[$srcLang][$id]);
			}
		}
		return true;
	}
	
	function addMissing($srcLang, $baseLang="English") {
		if ($srcLang == $baseLang) return true;
		foreach ($this->translations[$baseLang] as $id=>$val) {
			if (!isset($this->translations[$srcLang][$id])) {
				$this->translations[$srcLang][$id] = $val;
			}
		}
		return true;
	}
	
	
	function getLanguageDir() {
		return SERVER_PATH . '../i18n/';
	}
	
	function getPath($language) {
		return $this->getLanguageDir() . $language . '.lang.js';
	}
	
	function decodeLanguage($content) {
		$res_arr = array();
		$content = explode("\n", $content);
		foreach ($content as $line) {
			if (($pos = strpos($line, ':')) !== false) {
				$param = substr($line, 0, $pos);
				$value = substr($line, $pos+1);
				if (preg_match('/^\s*?["\'](.*)["\']\s*?,{0,1}\s*?$/u', $value, $match)) {
					$res_arr[trim($param)] = stripslashes($match[1]);
				}
			}
		}
		return $res_arr;
	}
	
	function exportLanguage($language) {
		if ($fp = fopen($this->getLanguageDir() . $language . '.lang.js', 'w')) {
			fwrite($fp, "{\n");
			$i = 0;
			foreach ($this->translations[$language] as $param=>$val) {
				$i++;
				fwrite($fp, "\t$param: \"$val\"");
				if (count($this->translations[$language]) > $i) {
					fwrite($fp, ",\n");
				} else {
					fwrite($fp, "\n");
				}
			}
			
			fwrite($fp, "}");
			fclose($fp);
		}
	}
	
	function loadTranslation($language) {
		if (file_exists($this->getPath($language))) {
			return $this->decodeLanguage(file_get_contents($this->getPath($language)));
		} elseif (file_exists($this->getPath('English'))) {
			return $this->decodeLanguage(file_get_contents($this->getPath('English')));
		} else {
			return false;
		}
	}
	
	function replace_arguments($string, $args) {
		$numargs = count($args);
		if ($numargs) {
			$arr_search = array();
			$arr_replace = array();
			for ($i = 1; $i < $numargs; $i++) {
				$arr_search[] = '${' . $i .'}';
				$arr_replace[] = $args[$i];
			}
			return str_replace($arr_search, $arr_replace, $string);
		} else {
			return $string;
		}
	}
	
	function get($string) {
		$dest_string = $string;
		$fncargs = func_get_args();

		if (isset($_COOKIE['language']) && isset($this->translations[$_COOKIE['language']]) && isset($this->translations[$_COOKIE['language']][$string])) {
			$dest_string = $this->translations[$_COOKIE['language']][$string];
		} elseif (isset($this->translations[$this->defaultLanguage]) && isset($this->translations[$this->defaultLanguage][$string])) {
			$dest_string = $this->translations[$this->defaultLanguage][$string];
		} else{
			if (!isset($this->translations['English'])) $this->addTranslation('English');			
			if ($this->translations['English'] && isset($this->translations['English'][$string])) {
				$dest_string = $this->translations['English'][$string];
			}
		}
		return $this->replace_arguments($dest_string, $fncargs);
	}
	
	function getAvaibleLanguages() {
		$directory = dir($this->getLanguageDir());
		$languages = array();
		while (false !== ($entry = $directory->read())) {
			if (preg_match('/(.*?)\.lang\.js$/', $entry, $match)) {
				$languages[] = array($match[1]);
			}
		}
		return $languages;
	}
}
?>
