<?php
/**
 * Template files handling
 */
class App_Template {

	/**
	 * Load template content and replace template tags with parameter values
	 *
	 * @param string $template_name
	 * @param object $params
	 * @return string
	 */
	function loadTemplateContent($template_name, $params) {
		if ($filename = App_Template::getTemplatePath($template_name)) {
			if (substr($filename, -4, 4) == '.php') {
				//TODO spravit to include a ob_content
				$content = file_get_contents($filename);
			} else {
				$content = file_get_contents($filename);
			}
			return App_Template::evaluateTemplate($params, $content);
		} else {
			return false;
		}
	}
	
    /**
     * Returns full path to template in case it exists, otherwise false
     *
     * @param string $templateName
     */
    function getTemplatePath($templateName) {
    	if (!strlen($templateName)) {
    		return false;
    	}
    	
		$fileName = SERVER_PATH . 'templates/' . $templateName;
		if (!file_exists($fileName)) {
			return false;
		}
    	return $fileName;
    }
	
	function identifyTemplateTags($content) {
        if (preg_match_all('/\${([A-Za-z0-9_\-:\.]+?)}/im', $content, $match)) {
            return $match[1];
        }
        return array();
    }
    
    /**
     * Evaluate template - replace template tags with values 
     */
	function evaluateTemplate($params, $template_text, $urlEncode = false) {
			global $state;
			$tags = App_Template::identifyTemplateTags($template_text);
			$template_text = App_Template::replaceTagsWithValues($params, $template_text, $tags, $urlEncode);

			//if any variable evaluates to next variable, evaluate it again
			$tags = App_Template::identifyTemplateTags($template_text);
			if (!empty($tags)) {
				$template_text = App_Template::replaceTagsWithValues($params, $template_text, $tags, $urlEncode);
			}
			
			return $template_text;
	}
	
	function prepareValue($value, $urlEncode) {
		if ($urlEncode) {
			return str_replace(' ', '+', urlencode($value));
		}
		return $value;
	}
	
	function replaceTagsWithValues($params, $template_text, $tags, $urlEncode = false) {
		global $state;
		foreach ($tags as $tag) {
			if (isset($state) && isset($state->lang) && preg_match('/app\.i18n\.([^\.\}]+)/', $tag, $match)) {
				$template_text = str_replace('${' . $tag . '}', App_Template::prepareValue($state->lang->get($match[1]), $urlEncode), $template_text);
			} else if (is_object($params) && ($value = $params->get($tag))) {
				$template_text = str_replace('${' . $tag . '}', App_Template::prepareValue($value, $urlEncode), $template_text);
			} else if (is_array($params) && isset($params[$tag])) {
				$template_text = str_replace('${' . $tag . '}', App_Template::prepareValue($params[$tag], $urlEncode), $template_text);
			} else {
				$template_text = str_replace('${' . $tag . '}', '', $template_text);
			}
		}
		return $template_text;
	}
}
?>