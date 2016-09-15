<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $dom = array();
	var $placeholders = array();
	var $templates;

	function __construct()
	{
		global $modx, $modx_manager_charset;

		// Prepare DOM-array
		$this->dom['title'] = 'No title';
		$this->dom['actionButtons'] = array();
		$this->dom['header'] = array();
		$this->dom['body'] = array();
		$this->dom['body']['content'] = array();
		$this->dom['footer'] = array();

		// Prepare Header-Attributes
		$modx->config['modx_lang_attribute'] = isset($modx->config['modx_lang_attribute']) ? $modx->config['modx_lang_attribute'] : 'en';
		if(isset($modx->config['modx_textdir']) && $modx->config['modx_textdir'] === 'rtl') {
			$this->setPlaceholder('modx_textdir','rtl');
			$this->setPlaceholder('modx_textdir_class',' class="rtl"');
		} else {
			$this->setPlaceholder('modx_textdir','ltr');
		}

		$this->setPlaceholder('modx_manager_charset',$modx_manager_charset);
		
		if(!isset($modx->config['mgr_jquery_path']))  $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
		if(!isset($modx->config['mgr_date_picker_path'])) $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';

		// Load template-engine defaults
		require MODX_MANAGER_PATH.'media/style/common/engine.php';

		$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
		$this->dom['header']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';
		
	}

	function setBodyGrid($grid) {
		$this->dom['body']['grid'] = $grid;
	}

	function setTitle($title) {
		$this->dom['title'] = $title;
	}

	function setActionButtons($arr) {
		$this->dom['actionButtons'] = array_merge($this->dom['actionButtons'], $arr);
	}

	function registerCssSrc($id, $src) {
		// if file_exists()
		$this->dom['header']['css']['src'][$id] = $this->parsePlaceholders($src);
	}

	function registerScriptSrc($id, $src, $version=NULL) {
		// if file_exists()
		$this->dom['header']['js'][$id] = array(
			'src'=>$this->parsePlaceholders($src),
			'version'=>$version
		);
	}

	function registerScriptFromFile($id, $file, $placeholder=array()) {
		// if file_exists()
		$script = file_get_contents(MODX_MANAGER_PATH.$file);
		$script = $this->parsePlaceholders($script, $placeholder);
		
		$this->dom['header']['js'][$id] = array(
			'script'=>$script,
			'file'=>$file
		);

	}

	function parsePlaceholders($source, $placeholder=array())
	{
		global $modx, $_lang, $_style;

		$placeholder = array_merge($this->placeholders, $placeholder);
		
		$source = $modx->mergeSettingsContent($source);
		
		// Get list of all existing [+placeholders+]
		$tags = $modx->getTagsFromContent($source);
		
		if(!empty($tags)) {
			foreach($tags[1] as $key=>$tag) {
				// Replace language
				if (substr($tag, 0, 5) == 'lang.') {
					$langKey = substr($tag, 5);
					$value   = isset($_lang[$langKey]) ? $_lang[$langKey] : '';
				} else if (substr($tag, 0, 6) == 'style.') {
					$styleKey = substr($tag, 6);
					$value = isset($_style[$styleKey]) ? $_style[$styleKey] : '';
				// Replace normal placeholders
				} else {
					$value = isset($placeholder[$tag]) ? $placeholder[$tag] : '';
				}
				$source = str_replace($tags[0][$key], $value, $source);
			}
		}
		
		return $source;
	}
	
	function addBody($content) {
		$this->dom['body']['content'] = $this->dom['body']['content'] + $content;
	}

	function setPlaceholder($key, $value) {
		$this->placeholders[$key] = $value;
	}

	function renderFullDom()
	{
		// Prepare placeholders
		$placeholders = array(
			'title'=>$this->dom['title'],
			'css'=>$this->mergeDomCss(),
			'javascript'=>$this->mergeDomJs(),
			'OnManagerMainFrameHeaderHTMLBlock'=>$this->dom['header']['OnManagerMainFrameHeaderHTMLBlock'],
			'actionButtons'=>$this->mergeDomActionButtons(),
			'body'=>$this->mergeDomBody(),
		);
		
		return $this->fetchTpl('body', $placeholders);
	}

	function mergeDomCss()
	{
		$output = '';
		foreach($this->dom['header']['css']['src'] as $id=>$src) {
			$output .= '	<link rel="stylesheet" type="text/css" href="'.$src.'" />'."\n";
		};
		return $output;
	}

	function mergeDomJs()
	{
		$output = '';
		foreach($this->dom['header']['js'] as $id=>$js) {
			if(isset($js['src'])) 		$output .= '	<script src="'.$js['src'].'" type="text/javascript"></script>'."\n"; 
			if(isset($js['script']))	$output .= '	<script type="text/javascript">'.$js['script'].'</script>'."\n";
		};
		return $output;
	}

	function mergeDomActionButtons()
	{
		$output = '';
		foreach($this->dom['actionButtons'] as $id=>$placeholders) {
			$output .= $this->fetchTpl('actionButton', $placeholders);
		};
		
		return $this->fetchTpl('actionButtons', array('actionButtons'=>$output));
	}

	function mergeDomBody()
	{
		$positions = array();
		foreach($this->dom['body']['content'] as $id=>$el) {
			$pos = isset($el['position']) ? $el['position'] : 'default';
			$type = isset($el['type']) ? $el['type'] : 'html'; 
			switch($type) {
				case 'form':
					$positions[$pos] .= $this->renderForm($id, $el);
					break;
				case 'html':
					// $blocks = $el['html']."\n";
					break;
			}
		};

		return $this->fetchTpl('grid.'.$this->dom['body']['grid'], $positions);
	}

	function fetchTpl($tpl, $placeholders, $noParse=false)
	{
		global $manager_theme;
		
		if(isset($this->templates[$tpl])) {
			$template = $this->templates[$tpl];
		} else {
			$tplFile = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/' . $tpl . '.html';
			if (!file_exists($tplFile)) $tplFile = MODX_MANAGER_PATH . 'media/style/common/tpl/' . $tpl . '.html';
			$template = file_get_contents($tplFile);
			$this->templates[$tpl] = $template;
		}
		if($noParse) return $template;
		return $this->parsePlaceholders($template, $placeholders);
	}

	function renderForm($formId, $form)
	{
		$positions = array();
		foreach($form['content'] as $id=>$el) {
			
			$pos = isset($el['position']) ? $el['position'] : 'default';
			
			$placeholders = array_merge($el, array(
				'id'=>$id,
				'formId'=>$formId
			));
			
			switch($el['type']) {
				case 'hidden':
					// @todo: Filter out and append to end of form?
					$positions[$pos] .= '	<input type="hidden" name="'.$id.'" value="'.$el['value'].'" />';
					break;
				case 'password':
					$positions[$pos] .= $this->fetchTpl('form.input.password', $placeholders); 
					break;
				case 'message':
					$positions[$pos] .= $this->fetchTpl('form.message', $placeholders);
					break;
				case 'submit':
					$displayNone = $el['displayNone'] == true ? ' style="display:none"' : '';
					$positions[$pos] .= '	<input type="submit" value="'.$el['label'].'" name="save"'.$displayNone.'>';
					break;
			}
			$positions[$pos] .= "\n";
		}
		
		$placeholder = array(
			'formId'=>$formId,
			'action'=>$form['action'],
			'method'=>isset($form['method']) ? $form['method'] : 'post',
			'content'=>$this->fetchTpl('grid.'.$form['grid'], $positions)
		);

		return $this->fetchTpl('form', $placeholder);
	}
}