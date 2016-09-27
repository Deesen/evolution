<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $tpeOptions = array(
		'html_comments'=>true,
		'show_elements'=>false,
		'echo_arrays'=>false,
	);
	var $actionTpl = '';
	var $actionTplHtml = '';
	var $dom = array();
	var $placeholders = array();
	var $tplCache = array();
	var $typeDefaults = array();
	var $debugMsg = array();

	function __construct()
	{
		global $modx, $modx_manager_charset, $_lang;

		// Prepare DOM-array
		$this->dom['head'] = array();		// Everything related to <head>
		$this->dom['elements'] = array();	// All elements to be rendered in DOM
		$this->dom['buttons'] = array();	// All action-buttons organized exactly like elements
		$this->dom['footer'] = array();		// Everything related to new Footer-Bar
		
		// Prepare Header-Attributes
		$modx->config['modx_lang_attribute'] = isset($modx->config['modx_lang_attribute']) ? $modx->config['modx_lang_attribute'] : 'en';
		if(isset($modx->config['modx_textdir']) && $modx->config['modx_textdir'] === 'rtl') {
			$this->setPlaceholder('modx_textdir','rtl');
			$this->setPlaceholder('modx_textdir_class',' class="rtl"');
		} else {
			$this->setPlaceholder('modx_textdir','ltr');
		}

		$this->setPlaceholder('modx_manager_charset',$modx_manager_charset);

		// Load template styles
		require MODX_MANAGER_PATH.'media/style/'.$modx->config['manager_theme'].'/style.php';
		
		if(!isset($modx->config['mgr_jquery_path']))  $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
		if(!isset($modx->config['mgr_date_picker_path'])) $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';
		
		// Load template-engine defaults
		require MODX_MANAGER_PATH.'media/style/common/engine.php';
		
		// Load custom engine
		$customSetupFile = MODX_MANAGER_PATH.'media/style/'.$modx->config['manager_theme'].'/engine.php';
		if(is_readable($customSetupFile)) require $customSetupFile;
		
		// Get output of plugin-event "OnManagerMainFrameHeaderHTMLBlock"
		$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
		$this->dom['head']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';
	}

	function setButton($elType, $target='', $attr=array(), $tpe=array()) {
		return $this->setDomElement($elType, $target, $attr, $tpe, 'buttons');
	}

	// Target example: "userform.tab2.section2"
	function setElement($elType, $target='', $attr=array(), $tpe=array())
	{
		return $this->setDomElement($elType, $target, $attr, $tpe, 'elements');
	}
	
	function setDomElement($elType, $target='', $attr=array(), $tpe=array(), $category)
	{
		// @todo: replace by $this->getDomIndex()
		$dom =& $this->dom[$category];
		$domTarget = explode( '.', $target );
		$elId = array_pop($domTarget);

		if(empty($domTarget)) {
			$dom = array($elId=>$this->dom[$category][$elId]);
		} else if($target != '' && strtolower($target) != 'body') {
			$dom =& $this->dom[$category];
			foreach ($domTarget as $key) {
				if (isset($dom[$key])) {
					$dom =& $dom[$key]['childs'];
				}
				else {
					$this->debugMsg[] = sprintf('setDomElement(%s) : Key "%s" not found for target "%s"', $category, $key, $elId);
					return null;
				}
			}
		}
		
		$tpe = array_merge(
			$this->getTypeDefaults($elType, $attr),
			array(
				'order'=>count($dom)
			),
			$tpe
		);
		
		$dom[$elId] = array(
			'type'=>$elType,
			'id'=>$elId,
			'target'=>$target == '' ? 'body' : $target,
			'attr'=>$attr,
			'tpe'=>$tpe,
			'childs'=>array()
		);
		
		return $this;
	}
	
	// Example: 'userform.section1.pass2' to 'userform.section2'
	function moveButton($sourceEl, $targetEl)
	{
		return $this->moveDomElement($sourceEl, $targetEl, 'buttons');
	}
	
	function moveElement($sourceEl, $targetEl)
	{
		return $this->moveDomElement($sourceEl, $targetEl, 'elements');
	}
	
	function moveDomElement($sourceEl, $targetEl, $category)
	{
		// @todo: replace by $this->getDomIndex() ?
		$domSource = explode('.', $sourceEl); 
		$sourceElId = array_pop($domSource);
		
		$dom       =& $this->dom[$category];
		foreach ($domSource as $key) {
			if (isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			}
			else {
				$this->debugMsg[] = sprintf('moveDomElement(%s): Key "%s" not found of source "%s"', $category, $key, $sourceEl);
				return $this;
			}
		}

		$src = $dom[$sourceElId];
		unset($dom[$sourceElId]);
		
		$this->addElement($src['type'], $src['id'], $targetEl, $src['attr'], $src['tpe']);
		
		return $this;
	}

	function addButtonTpe($target, $param, $value)
	{
		return $this->addDomElementTpe($target, $param, $value, 'buttons');
	}

	function addElementTpe($target, $param, $value)
	{
		return $this->addDomElementTpe($target, $param, $value, 'elements');
	}

	function addDomElementTpe($target, $param, $value, $category)
	{
		$domTarget = explode( '.', $target );
		$elementId = array_pop($domTarget);
		
		$dom =& $this->dom[$category];
		foreach( $domTarget as $key ) {
			if(isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			} else {
				$this->debugMsg[] = sprintf('addDomElementTpe(%s) : Key "%s" not found for target "%s"', $category, $key, $target);
				return $this;
			}
		}
		
		$dom[$elementId]['tpe'][$param] = $value;
		
		return $this;
	}

	//////////////////////////////////
	// @todo: Doesn´t work! How to do?
	/*
	function getDomIndex($target, &$dom)
	{
		$domTarget = explode( '.', $target );
		foreach( $domTarget as $key ) {
			if(isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			} else {
				$this->debugMsg[] = sprintf('Key "%s" not found for target "%s"', $key, $target);
				return NULL;
			}
		}
		return $dom;
	}
	*/

	function renderFullDom()
	{
		global $modx, $manager_theme;
		
		// Load default or custom action-template before rendering body, parsing snippets etc
		$actionTpl = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/actions/' . $this->actionTpl . '.php';
		if (!is_readable($actionTpl)) $actionTpl = MODX_MANAGER_PATH . 'media/style/common/tpl/actions/' . $this->actionTpl . '.php';
		if (is_readable($actionTpl)) {
			ob_start();
			$tpe =& $this;
			require($actionTpl);
			$this->actionTplHtml = ob_get_contents();
			ob_end_clean();
		} else {
			return 'Action-Template not found: '.$this->actionTpl;
		}

		$source = $this->fetchTpl('body');
		return $modx->parseManagerDocumentSource($source);  // Render snippets etc
	}

	function mergeElementsList($element, $depth, $outerTpl, $rowTpl, $cssFirst='', $cssLast='')
	{
		$output = '';
		
		// @todo: replace by $this->getDomIndex()
		$domTarget = explode( '.', $element );
		$dom =& $this->dom['elements'];
		foreach( $domTarget as $key ) {
			if(isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			} else {
				$this->debugMsg[] = sprintf('mergeElementsList(elements) : Key "%s" not found for target "%s"', $key, $element);
				return NULL;
			}
		}
		
		// @todo: Use "depth"-param
		$iteration = 1;
		$total = count($dom);
		foreach($dom as $elId=>$el) {
			$tpe = array();
			if($iteration == $total) $tpe = array('cssFirst'=>'','cssLast'=>$cssLast);
			if($iteration == 1) $tpe = array('cssFirst'=>$cssFirst, 'cssLast'=>'');
			$phs = $this->prepareElementPlaceholders($el, '', $tpe);
			$output .= $this->fetchTpl($rowTpl, $phs);
			$iteration++;
		}
		
		return $this->fetchTpl($outerTpl, array('childs'=>$output));
	}
	
	function prepareElementPlaceholders($el, $attr=array(), $tpe=array())
	{
		return array(
			'id'=>$el['id'],
			'target'=>$el['target'],
			'attr'=>is_array($attr) ? array_merge($el['attr'], $attr) : $el['attr'],
			'tpe'=>is_array($tpe) ? array_merge($el['tpe'], $tpe) : $el['tpe'],
		);
	}

	function mergeElement($element, $category='elements')
	{
		if($element =='body') $dom =& $this->dom[$category];
		else {
			// @todo: replace by $this->getDomIndex()
			$domTarget = explode( '.', $element );
			$elId = array_pop($domTarget);
			if(empty($domTarget)) {
				$dom = array($elId=>$this->dom[$category][$elId]);
			} else {
				$dom =& $this->dom[$category];
				foreach ($domTarget as $key) {
					if (isset($dom[$key])) {
						$dom =& $dom[$key]['childs'];
					}
					else {
						$this->debugMsg[] = sprintf('mergeElement(%s) : Key "%s" not found for target "%s"', $category, $key, $element);
						return null;
					}
				}
			}
		}
		
		$body = $this->renderRecursive($dom);
		return join("\n", $body);
	}
	
	function getTypeDefaults($elType, $attr)
	{
		$key = $elType;
		if(isset($attr['type']) && !empty($attr['type'])) {
			$key2 = $elType.'.'.$attr['type'];
			if(isset($this->typeDefaults[$key2])) return $this->typeDefaults[$key2];
		}
		if(isset($this->typeDefaults[$key])) return $this->typeDefaults[$key];
		return array();
	}

	function setTypeDefaults($type, $defaults) {
		$this->typeDefaults[$type] = $defaults;
		return $this;
	}
	
	function setActionTemplate($tpl) {
		$this->actionTpl = $tpl;
		return $this;
	}
	
	function alert($message, $class='info') {
		$this->dom['alerts'][$class][] = $message;
		return $this;
	}

	function registerCssSrc($id, $src) {
		// if file_exists()
		$this->dom['head']['css']['src'][$id] = $this->parsePlaceholders($src);
		return $this;
	}

	function registerScriptSrc($id, $src, $version=NULL) {
		// if file_exists()
		$this->dom['head']['js'][$id] = array(
			'src'=>$this->parsePlaceholders($src),
			'version'=>$version
		);
		return $this;
	}

	function registerScriptFromFile($id, $file, $placeholder=array()) {
		// if file_exists()
		$script = file_get_contents(MODX_MANAGER_PATH.$file);
		$script = $this->parsePlaceholders($script, $placeholder);
		
		$this->dom['head']['js'][$id] = array(
			'script'=>$script,
			'file'=>$file
		);
		return $this;
	}

	function setPlaceholder($key, $value) {
		$this->placeholders[$key] = $value;
		return $this;
	}
	
	function getPlaceholder($key, $fallback=NULL) {
		return $this->placeholders[$key] === '' & !is_null($fallback) ? $fallback : $this->placeholders[$key];
	}

	function mergeDomCss()
	{
		$output = '';
		foreach($this->dom['head']['css']['src'] as $id=>$src) {
			$output .= '	<link rel="stylesheet" type="text/css" href="'.$src.'" />'."\n";
		};
		return $output;
	}

	function mergeDomJs()
	{
		$output = '';
		foreach($this->dom['head']['js'] as $id=>$js) {
			if(isset($js['src'])) 		$output .= '	<script src="'.$js['src'].'" type="text/javascript"></script>'."\n"; 
			if(isset($js['script']))	$output .= '	<script type="text/javascript">'.$js['script'].'</script>'."\n";
		};
		
		$output .= $this->dom['head']['OnManagerMainFrameHeaderHTMLBlock'];
		
		return $output;
	}

	function mergeDomActionButtons($category)
	{
		return $this->mergeElement($category, 'buttons');
	}

	function mergeDomAlerts($outerTpl, $rowTpl)
	{
		$outerTpl = !empty($outerTpl) ? $outerTpl : 'alerts';
		$rowTpl = !empty($rowTpl) ? $rowTpl : 'alert';

		$output = '';
		if(!empty($this->dom['alerts'])) {
			foreach($this->dom['alerts'] as $class=>$alertsArr) {
				$alerts = '';
				foreach($alertsArr as $alert) {
					$alerts .= $this->fetchTpl($rowTpl, array('alert'=>$alert));
				};
				$output .= $this->fetchTpl($outerTpl, array('alerts'=>$alerts,'class'=>$class)); 
			};
		};

		return $output;
	}

	function mergeDomBody()
	{
		
		return $this->parsePlaceholders($this->actionTplHtml, array());
	}
	
	function mergeDebugMsg()
	{
		$debug = '';
		if($this->tpeOptions['echo_arrays'] || $this->tpeOptions['html_comments']) {
			$debug = '<h2>dom</h2><pre style="font-size:12px;">'.print_r($this->dom,true).'</pre>'."\n";
			$debug .= '<h2>typeDefaults</h2><pre style="font-size:12px;">'.print_r($this->typeDefaults,true).'</pre>'."\n";
			$debug .= '<h2>debugMsg</h2><pre style="font-size:12px;">'.print_r($this->debugMsg,true).'</pre>'."\n";
		}
		if($this->tpeOptions['echo_arrays']) { echo $debug; exit; }
		return $debug;
	}

	function renderRecursive($dom)
	{
		$output = array();

		$iteration = 1;
		$total = count($dom);
		foreach($dom as $elId=>$el) {

			// Set first / last css-class
			$tpe = array();
			if($iteration === $total) $tpe = array('cssFirst'=>'','cssLast'=>isset($el['tpe']['cssLast']) ? $el['tpe']['cssLast'] : '');
			if($iteration === 1) $tpe = array('cssFirst'=>isset($el['tpe']['cssFirst']) ? $el['tpe']['cssFirst'] : '','cssLast'=>'');

			$phs = $this->prepareElementPlaceholders($el, '', $tpe);
			$pos = isset($el['tpe']['pos']) ? $el['tpe']['pos'] : 'childs';

			// Recursive part
			if(!empty($el['childs'])) {
				$recursive = array_merge($phs, $this->renderRecursive($el['childs']));

				// Handle blockTpl for Grids
				if(isset($el['tpe']['blockTpl'])) {
					foreach($el['tpe']['blockTpl'] as $block=>$blockTpls) {
						if(isset($recursive[$block]) && isset($blockTpls['outerTpl'])) $recursive[$block] = $this->fetchTpl($blockTpls['outerTpl'], array_merge($phs, array('childs'=>$recursive[$block])));
					}
				}

				if(isset($el['tpe']['innerTpl'])) {
					$phs['childs'] = $this->fetchTpl($el['tpe']['innerTpl'], $recursive);
				} else {
					$phs = $recursive;
				}
			}

			// Prepare show_elements-mode
			$fetch = $this->tpeOptions['show_elements'] ? '<div style="font-size:10px;font-family:monospace;font-weight: bold;background-color:#">'.$el['target'].'.'.$elId.'</div>' : '';

			$elementTpl = $el['tpe']['tpl'];
			$fetch .= $this->fetchTpl($elementTpl, $phs);

			if(isset($el['tpe']['outerTpl'])) {
				$output[$pos] .= $this->fetchTpl($el['tpe']['outerTpl'], array_merge($phs, array('childs'=>$fetch))) . "\n";
			} else {
				$output[$pos] .= $fetch . "\n";
			}
			$iteration++;
		}

		return $output;
	}
	
	function fetchTpl($tpl, $placeholders=array(), $noParse=false)
	{
		global $modx, $manager_theme;
		
		if(isset($this->tplCache[$tpl])) {
			$template = $this->tplCache[$tpl]['html'];
		} else {
			$tplFile = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/' . $tpl . '.html';
			if (!is_readable($tplFile)) $tplFile = MODX_MANAGER_PATH . 'media/style/common/tpl/' . $tpl . '.html';
			if (is_readable($tplFile)) {
				$template             = file_get_contents($tplFile);
				$tags = $modx->getTagsFromContent($template);
				$this->tplCache[$tpl]['html'] = $template;
				$this->tplCache[$tpl]['tags'] = $tags[1];
			} else {
				return 'Template not found: '.$tpl;
			}
		}
		if($noParse) return $template;
		return $this->parsePlaceholders($template, $placeholders);
	}

	function parsePlaceholders($source, $placeholder=array())
	{
		global $modx, $_lang, $_style;

		$placeholder = array_merge($this->placeholders, $placeholder);
		$source = $modx->mergeSettingsContent($source);
		$tags = $modx->getTagsFromContent($source);

		if(!empty($tags)) {
			foreach($tags[1] as $key=>$tag) {
				$value = '';
				if (substr($tag, 0, 5) == 'lang.') {
					// Replace language
					$langKey = substr($tag, 5);
					$value   = isset($_lang[$langKey]) ? $_lang[$langKey] : '';
				} else if (substr($tag, 0, 6) == 'style.') {
					// Replace style
					$styleKey = substr($tag, 6);
					$value    = isset($_style[$styleKey]) ? $_style[$styleKey] : '';
				} else if (substr($tag, 0, 5) == 'attr.') {
					// Replace attributes
					$attrKey = substr($tag, 5);
					$value = isset($placeholder['attr'][$attrKey]) ? $placeholder['attr'][$attrKey] : '';
				} else if (substr($tag, 0, 4) == 'tpe.') {
					// Replace template-engine params
					$tpeKey = substr($tag, 4);
					$value = isset($placeholder['tpe'][$tpeKey]) ? $placeholder['tpe'][$tpeKey] : '';
				} else if(isset($placeholder[$tag])){
					// Parse normal placeholders
					$value = $placeholder[$tag];
				} else {
					// Merge elements like [+userform+], [+userform.section+]
					// $value = $this->mergeElement($tag);
				}
				$source = str_replace($tags[0][$key], $value, $source);
			}
		}
		return $source;
	}
}