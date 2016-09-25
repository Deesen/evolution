<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $debug = false;
	var $debugMsg = array();
	var $dom = array();
	var $placeholders = array();
	var $actionTpl = ''; 
	var $tplCache = array();
	var $typeDefaults = array();

	function __construct()
	{
		global $modx, $modx_manager_charset;

		// Prepare DOM-array
		$this->dom['header'] = array();		// Everything related to <head>
		$this->dom['buttons'] = array();	// All action-buttons in categories with "main" as top-bar 
		$this->dom['elements'] = array();	// All elements to be rendered in DOM
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
		
		if(!isset($modx->config['mgr_jquery_path']))  $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
		if(!isset($modx->config['mgr_date_picker_path'])) $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';

		// Load template-engine defaults
		require MODX_MANAGER_PATH.'media/style/common/engine.php';
		
		$customSetupFile = MODX_MANAGER_PATH.'media/style/'.$modx->config['manager_theme'].'/engine.php';
		if(is_readable($customSetupFile)) require $customSetupFile;

		$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
		$this->dom['header']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';
	}

	function createBodyElement($elType, $elId, $attr=array(), $tpe=array())
	{
		$tpe = array_merge($this->getTypeDefaults($elType, $attr), array(
			'order'=>count($this->dom['elements'])
		), $tpe);
		
		$this->dom['elements'][$elId] = array(
			'type'=>$elType,
			'id'=>$elId,
			'attr'=>$attr,
			'tpe'=>$tpe,
			'childs'=>array()
		);
		return $this;
	}

	function addElement($elType, $elId, $target, $attr=array(), $tpe=array())
	{
		// Target example: "userform.tab2.section2"
		// @todo: replace by $this->getDomIndex()
		$domTarget = explode( '.', $target );
		$dom =& $this->dom['elements'];
		foreach( $domTarget as $key ) {
			if(isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			} else {
				$this->debugMsg[] = sprintf('Key "%s" not found for target "%s"', $key, $target);
				return $this;
			}
		}
		
		$tpe = array_merge($this->getTypeDefaults($elType, $attr), array(
			'order'=>count($dom)
		), $tpe);
		
		$dom[$elId] = array(
			'type'=>$elType,
			'id'=>$elId,
			'target'=>$target,
			'attr'=>$attr,
			'tpe'=>$tpe,
			'childs'=>array()
		);
		
		return $this;
	}
	
	// Example: 'userform.section1.pass2' to 'userform.section2'
	function moveElement($sourceEl, $targetEl)
	{
		// @todo: replace by $this->getDomIndex() ?
		$domSource = explode('.', $sourceEl); 
		$sourceElId = array_pop($domSource);
		
		$dom       =& $this->dom['elements'];
		foreach ($domSource as $key) {
			if (isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			}
			else {
				$this->debugMsg[] = sprintf('moveElement(): Key "%s" not found of source "%s"', $key, $sourceEl);
				return $this;
			}
		}

		$src = $dom[$sourceElId];
		unset($dom[$sourceElId]);
		
		$this->addElement($src['type'], $src['id'], $targetEl, $src['attr'], $src['tpe']);
		
		return $this;
	}

	function addElementTpe($target, $param, $value)
	{
		$domTarget = explode( '.', $target );
		$elementId = array_pop($domTarget);
		
		$dom =& $this->dom['elements'];
		foreach( $domTarget as $key ) {
			if(isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			} else {
				$this->debugMsg[] = sprintf('Key "%s" not found for target "%s"', $key, $target);
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
		if($this->debug) {
			echo '<h2>dom</h2><pre style="font-size:12px;">'.print_r($this->dom,true).'</pre>';
			echo '<h2>typeDefaults</h2><pre style="font-size:12px;">'.print_r($this->typeDefaults,true).'</pre>';
			echo '<h2>debugMsg</h2><pre style="font-size:12px;">'.print_r($this->debugMsg,true).'</pre>';
			exit;
		}
		
		global $modx;

		// Prepare placeholders
		$placeholders = array(
			
		);

		// @todo: load custom-action to allow customizing dom-array

		$source = $this->fetchTpl('body', $placeholders);
		return $modx->parseManagerDocumentSource($source);
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
				$this->debugMsg[] = sprintf('mergeElementsList() : Key "%s" not found for target "%s"', $key, $element);
				return NULL;
			}
		}
		
		// @todo: Use "depth"-param
		$iteration = 1;
		$total = count($dom);
		foreach($dom as $elId=>$el) {
			$additional = array();
			if($iteration == $total) $additional = array('cssFirst'=>'','cssLast'=>$cssLast);
			if($iteration == 1) $additional = array('cssFirst'=>$cssFirst, 'cssLast'=>'');
			$phs = $this->prepareElementPlaceholders($el, $additional);
			$output .= $this->fetchTpl($rowTpl, $phs);
			$iteration++;
		}
		
		return $this->fetchTpl($outerTpl, array('childs'=>$output));
	}
	
	function prepareElementPlaceholders($el, $additional=array())
	{
		return array_merge($el['attr'], $el['tpe'], array('id'=>$el['id'],'target'=>$el['target']), $additional);
	}

	function mergeElement($element)
	{
		if($element =='body') $dom =& $this->dom['elements'];
		else {
			// @todo: replace by $this->getDomIndex()
			$domTarget = explode( '.', $element );
			$dom =& $this->dom['elements'];
			foreach( $domTarget as $key ) {
				if(isset($dom[$key])) {
					$dom =& $dom[$key]['childs'];
				} else {
					$this->debugMsg[] = sprintf('Key "%s" not found for target "%s"', $key, $element);
					return NULL;
				}
			}
		}
		
		$body = $this->renderRecursive($dom);
		return $body['childs'];	// join("\n", $body)
	}

	function renderRecursive($dom)
	{
		$output = array();
		
		$iteration = 1;
		$total = count($dom);
		foreach($dom as $elId=>$el) {
			$additional = array();
			if($iteration == $total) $additional = array('cssFirst'=>'','cssLast'=>isset($el['tpe']['cssLast']) ? $el['tpe']['cssLast'] : '');
			if($iteration == 1) $additional = array('cssFirst'=>isset($el['tpe']['cssFirst']) ? $el['tpe']['cssFirst'] : '','cssLast'=>'');
			$phs = $this->prepareElementPlaceholders($el, $additional);
			$pos = isset($el['tpe']['pos']) ? $el['tpe']['pos'] : 'childs';
			
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
			
			$elementTpl = $el['tpe']['tpl'];
			$fetch = $this->fetchTpl($elementTpl, $phs);
			if(isset($el['tpe']['outerTpl'])) {
				$output[$pos] .= $this->fetchTpl($el['tpe']['outerTpl'], array_merge($phs, array('childs'=>$fetch))) . "\n";
			} else {
				$output[$pos] .= $fetch . "\n";
			}
			$iteration++;
		}
		
		return $output;
	}
	
	function getTypeDefaults($elType, $attr)
	{
		$key = $elType;
		if(isset($attr['type']) && !empty($attr['type'])) $key = $elType.'.'.$attr['type'];
		return $this->typeDefaults[$key];
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

	function setButton($category, $id, $params) {
		$this->dom['buttons'][$category][$id] = $params;
		return $this;
	}

	function addButtonParam($category, $id, $param, $value) {
		$this->dom['buttons'][$category][$id][$param] .= $value;
		return $this;
	}

	function setButtonParam($category, $id, $param, $value) {
		$this->dom['buttons'][$category][$id][$param] = $value;
		return $this;
	}

	function registerCssSrc($id, $src) {
		// if file_exists()
		$this->dom['header']['css']['src'][$id] = $this->parsePlaceholders($src);
		return $this;
	}

	function registerScriptSrc($id, $src, $version=NULL) {
		// if file_exists()
		$this->dom['header']['js'][$id] = array(
			'src'=>$this->parsePlaceholders($src),
			'version'=>$version
		);
		return $this;
	}

	function registerScriptFromFile($id, $file, $placeholder=array()) {
		// if file_exists()
		$script = file_get_contents(MODX_MANAGER_PATH.$file);
		$script = $this->parsePlaceholders($script, $placeholder);
		
		$this->dom['header']['js'][$id] = array(
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
		
		$output .= $this->dom['header']['OnManagerMainFrameHeaderHTMLBlock'];
		
		return $output;
	}

	function mergeDomActionButtons($category, $outerTpl, $rowTpl)
	{
		$category = !empty($category) ? $category : 'main';
		$outerTpl = !empty($outerTpl) ? $outerTpl : 'actionButtons';
		$rowTpl = !empty($rowTpl) ? $rowTpl : 'actionButton';

		$output = '';
		if(!empty($this->dom['buttons'][$category])) {
			foreach($this->dom['buttons'][$category] as $id=>$placeholders) {
				$output .= $this->fetchTpl($rowTpl, $placeholders);
			};
		};

		return $this->fetchTpl($outerTpl, array('buttons'=>$output));
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
		return $this->fetchTpl('actions/'.$this->actionTpl, array());
	}
	
	function fetchTpl($tpl, $placeholders, $noParse=false)
	{
		global $modx, $manager_theme;
		
		if(isset($this->tplCache[$tpl])) {
			$template = $this->tplCache[$tpl]['html'];
		} else {
			$tplFile = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/' . $tpl . '.html';
			if (!file_exists($tplFile)) $tplFile = MODX_MANAGER_PATH . 'media/style/common/tpl/' . $tpl . '.html';
			if (file_exists($tplFile)) {
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

		// Get list of all existing [+placeholders+]
		$tags = $modx->getTagsFromContent($source);

		if(!empty($tags)) {
			foreach($tags[1] as $key=>$tag) {
				if (substr($tag, 0, 5) == 'lang.') {
					// Replace language
					$langKey = substr($tag, 5);
					$value   = isset($_lang[$langKey]) ? $_lang[$langKey] : '';
				} else if (substr($tag, 0, 6) == 'style.') {
					// Replace style
					$styleKey = substr($tag, 6);
					$value    = isset($_style[$styleKey]) ? $_style[$styleKey] : '';
				} else if (strpos($tag, '.') !== false) {
						// Replace multi-placeholders
						$exp = explode('.', $tag);

						// Form-placeholders
						if(isset($this->dom['forms'][ $exp[0] ])) {
							if($exp[1] == 'inputs') {
								switch($exp[2]) {
									case 'hidden':
										$value = '';
										foreach($this->dom['forms'][ $exp[0] ]['inputs'] as $id=>$el) {
											if($el['type'] == 'hidden') {
												$value .= $this->renderFormField($el);
											}
										}
										break;
								}
							}else if($exp[1] == 'input') {
								$value = isset($this->dom['forms'][ $exp[0] ]['inputs'][ $exp[2] ]) ? $this->renderFormField($this->dom['forms'][ $exp[0] ]['inputs'][ $exp[2] ]) : '';
							} else {
								$value = isset($this->dom['forms'][ $exp[0] ][ $exp[1] ]) ? $this->dom['forms'][ $exp[0] ][ $exp[1] ] : '';
							}
						}
						
				} else {
					// Parse normal placeholders
					$value = isset($placeholder[$tag]) ? $placeholder[$tag] : '';
				}
				$source = str_replace($tags[0][$key], $value, $source);
			}
		}

		return $source;
	}

}