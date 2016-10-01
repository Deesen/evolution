<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $tpeOptions = array(
		'html_comments'=>false, // show output + debug-info
		'show_elements'=>false, // echo element-ids
		'echo_arrays'=>false,   // echo only arrays
	);
	var $actionTpl = '';
	var $actionTplHtml = '';
	var $actionHtml = '';
	var $dom = array();
	var $placeholders = array();
	var $tplCache = array();
	var $typeDefaults = array();
	var $debugMsg = array();
	var $tpeActive = false;

	function __construct()
	{
		global $modx, $modx_manager_charset, $_lang, $_style;

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

		if(!isset($modx->config['mgr_jquery_path']))  $modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';
		if(!isset($modx->config['mgr_date_picker_path'])) $modx->config['mgr_date_picker_path'] = 'media/script/air-datepicker/datepicker.inc.php';
		
		// Load template-engine defaults
		require MODX_MANAGER_PATH.'media/style/common/engine.php';
		
		// Load custom engine
		$customSetupFile = MODX_MANAGER_PATH.'media/style/'.$modx->config['manager_theme'].'/engine.php';
		if(is_readable($customSetupFile)) require $customSetupFile;
	}
	
	function isActive() {
		return $this->tpeActive;
	}
	
	function setButton($elType, $target='', $attr=array(), $tpe=array()) {
		return $this->setDomElement($elType, $target, $attr, $tpe, 'buttons');
	}

	// Target example: "userform.tab2.section2"
	function setElement($elType, $target='', $attr=array(), $tpe=array())
	{
		return $this->setDomElement($elType, $target, $attr, $tpe, 'elements');
	}
	
	function setDomElement($elType, $target='', $attr=array(), $tpe=array(), $category, $childs=array())
	{
		if(!is_array($attr)) $attr = array();
		if(!is_array($tpe)) $tpe = array();
		
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
				'order'=>count($dom),
				'unique'=>md5($target),
			),
			$tpe
		);
		
		$dom[$elId] = array(
			'type'=>$elType,
			'id'=>$elId,
			'target'=>$target == '' ? 'body' : implode('.',$domTarget),
			'attr'=>$attr,
			'tpe'=>$tpe,
			'childs'=>$childs
		);
		
		return $this;
	}

	// Target example: "userform.tab2.section2"
	function setElementOrder($elTarget, $order)
	{
		$domSource = explode('.', $elTarget);
		$sourceElId = array_pop($domSource);

		$dom       =& $this->dom['elements'];
		foreach ($domSource as $key) {
			if (isset($dom[$key])) {
				$dom =& $dom[$key]['childs'];
			}
			else {
				$this->debugMsg[] = sprintf('moveDomElement(%s): Key "%s" not found of source "%s"', 'elements', $key, $elTarget);
				return $this;
			}
		}
		$dom[$sourceElId]['tpe']['order'] = $order;
		
		return $this;
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
		$this->tpeActive = true;
		return $this;
	}

	function alert($message, $class='info') {
		$this->dom['alerts'][$class][] = $message;
		return $this;
	}

	function registerCssSrc($id, $src) {
		$this->dom['head']['css']['src'][$id] = $this->parsePlaceholders($src);
		return $this;
	}

	function registerHeadScriptSrc($id, $src, $version=NULL) {
		return $this->registerScriptSrc($id, $src, $version, 'head');
	}

	function registerFooterScriptSrc($id, $src, $version=NULL) {
		return $this->registerScriptSrc($id, $src, $version, 'footer');
	}

	function registerScriptSrc($id, $src, $version, $category) {
		$this->dom[$category]['js'][$id] = array(
			'src'=>$this->parsePlaceholders($src),
			'version'=>$version
		);
		return $this;
	}

	function registerHeadScriptFromFile($id, $file, $placeholder=array()) {
		return $this->registerScriptFromFile($id, $file, $placeholder, 'head');
	}

	function registerFooterScriptFromFile($id, $file, $placeholder=array()) {
		return $this->registerScriptFromFile($id, $file, $placeholder, 'footer');
	}

	function registerScriptFromFile($id, $file, $placeholder, $category) {
		// if file_exists()
		$script = file_get_contents(MODX_MANAGER_PATH.$file);
		$script = $this->parsePlaceholders($script, $placeholder);

		$this->dom[$category]['js'][$id] = array(
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
		
		$this->setDomElement($src['type'], $targetEl.'.'.$sourceElId, $src['attr'], $src['tpe'], $category, $src['childs']);
		
		return $this;
	}
	
	// $param & $value = string, only $param = array 
	function setButtonTpe($target, $param, $value='')
	{
		return $this->setDomElementTpe($target, $param, $value, 'buttons', false);
	}

	// $param & $value = string, only $param = array
	function setElementTpe($target, $param, $value='')
	{
		return $this->setDomElementTpe($target, $param, $value, 'elements', false);
	}

	// $param & $value = string, only $param = array
	function setElementChildsTpe($target, $param, $value='', $ignoreTypes='')
	{
		return $this->setDomElementTpe($target, $param, $value, 'elements', true, $ignoreTypes);
	}
	
	function setDomElementTpe($target, $param, $value, $category, $allChildren, $ignoreTypes=false)
	{
		$domTarget = explode( '.', $target );
		$elementId = array_pop($domTarget);
		
		$dom =& $this->dom[$category];
		if(!empty($domTarget)) {
			foreach ($domTarget as $key) {
				if (isset($dom[$key])) {
					$dom =& $dom[$key]['childs'];
				}
				else {
					$this->debugMsg[] = sprintf('setDomElementTpe(%s) : Key "%s" not found for target "%s"', $category, $key, $target);

					return $this;
				}
			}
		}
		
		if($allChildren) {
			$ignoreTypes = $ignoreTypes != '' ? explode(',', $ignoreTypes) : false;
			$dom =& $dom[$elementId]['childs'];
			foreach($dom as $elId=>$el) {
				if($ignoreTypes && in_array($el['type'], $ignoreTypes)) continue;
				if(is_array($param)) $dom[$elId]['tpe'] = array_merge($dom[$elId]['tpe'], $param);
				else $dom[$elId]['tpe'][$param] = $value;
			}
		} else {
			if(is_array($param)) $dom[$elementId]['tpe'] = array_merge($dom[$elementId]['tpe'], $param);
			else $dom[$elementId]['tpe'][$param] = $value;
		}
		
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
	
	function renderFullDom($actionHtml)
	{
		global $modx, $manager_theme, $SystemAlertMsgQueque;
		
		$tpeFooter = '';

		// display system alert window if messages are available
		if (count($SystemAlertMsgQueque)>0) {
			ob_start();
			include "sysalert.display.inc.php";
			$tpeFooter .= ob_get_contents();
			ob_end_clean();
		}
		
		$tpeFooter .= "
		<script type='text/javascript'>
			document.body.addEventListener('keydown', function (e) {
				if ((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey)) {
					document.getElementById( 'Button1' ).getElementsByTagName( 'a' )[0].click();
					e.preventDefault();
				}
			});
		</script>";
		
		if(in_array($modx->manager->action,array(85,27,4,72,13,11,12,87,88)))
			$tpeFooter .= $modx->manager->loadDatePicker($modx->config['mgr_date_picker_path']);
		
		$tpeFooter .= $this->mergeDomJs('footer');
		
		// Get output of plugin-event "OnManagerMainFrameHeaderHTMLBlock"
		$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
		$this->dom['head']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';

		$placeholders = array(
			'tpe'=>array(
				'head.css'=>$this->mergeDomCss(),
				'head.javascript'=>$this->mergeDomJs('head'),
				'body'=>$actionHtml, // Get output i.e. from actions/mutate_content.dynamic.php
				'debug'=>$this->mergeDebugMsg(),
				'footer'=>$tpeFooter
			));
		
		$source = $this->fetchTpl('body');
		$source = $modx->parseManagerDocumentSource($source);   // Render snippets before replacing [+tpe.content+] which can contain "[[...]]" (Codemirror Javascript)
		$source = $this->parsePlaceholders($source, $placeholders);
		
		return $source;
	}

	function renderAction()
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
		
		$source = $this->actionTplHtml;
		$source = $modx->parseManagerDocumentSource($source);
		$source = $this->parsePlaceholders($source);
		return $source;
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
		
		$body = $this->renderElementsRecursive($dom);
		return join("\n", $body);
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
			else if($iteration == 1) $tpe = array('cssFirst'=>$cssFirst, 'cssLast'=>'');
			else $tpe = array('cssFirst'=>'', 'cssLast'=>'');
			$phs = $this->prepareElementPlaceholders($el, '', $tpe);
			$output .= $this->parseTpl($rowTpl, $phs);
			$iteration++;
		}

		return $this->parseTpl($outerTpl, array('childs' =>$output));
	}

	function prepareElementPlaceholders($el, $attr=array(), $tpe=array())
	{
		return array(
			'id'=>$el['id'],
			'target'=>$el['target'],
			'unique'=>$el['unique'],
			'attr'=>is_array($attr) ? array_merge($el['attr'], $attr) : $el['attr'],
			'tpe'=>is_array($tpe) ? array_merge($el['tpe'], $tpe) : $el['tpe'],
		);
	}

	function mergeDomCss()
	{
		$output = '';
		foreach($this->dom['head']['css']['src'] as $id=>$src) {
			$output .= '	<link rel="stylesheet" type="text/css" href="'.$src.'" />'."\n";
		};
		return $output;
	}

	function mergeDomJs($category)
	{
		$output = '';
		if(isset($this->dom[$category]['js'])) {
			foreach ($this->dom[$category]['js'] as $id => $js) {
				if (isset($js['src'])) $output .= '	<script src="' . $js['src'] . '" type="text/javascript"></script>' . "\n";
				if (isset($js['script'])) $output .= '	<script type="text/javascript">' . $js['script'] . '</script>' . "\n";
			};
		}
		
		if($category == 'head')
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
					$alerts .= $this->parseTpl($rowTpl, array('alert' =>$alert));
				};
				$output .= $this->parseTpl($outerTpl, array('alerts' =>$alerts, 'class' =>$class)); 
			};
		};

		return $output;
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

	function renderElementsRecursive($dom)
	{
		global $modx;
		
		$output = array();

		$iteration = 1;
		$total = count($dom);

		// Sort-function to sort by 'order'
		if(!function_exists('cmp')) {
			function cmp($a, $b) {
				if ($a['tpe']['order'] == $b['tpe']['order']) {
					return 0;
				}

				return ($a['tpe']['order'] < $b['tpe']['order']) ? -1 : 1;
			}
		}
		uasort($dom,"cmp");
		
		foreach($dom as $elId=>$el) {

			// Set first / last css-class
			$tpe = array();
			if($iteration === $total) $tpe = array('cssFirst'=>'','cssLast'=>isset($el['tpe']['cssLast']) ? $el['tpe']['cssLast'] : '');
			else if($iteration === 1) $tpe = array('cssFirst'=>isset($el['tpe']['cssFirst']) ? $el['tpe']['cssFirst'] : '','cssLast'=>'');
			else $tpe = array('cssFirst'=>'', 'cssLast'=>'');
			
			$phs = $this->prepareElementPlaceholders($el, '', $tpe);
			$pos = isset($el['tpe']['pos']) ? $el['tpe']['pos'] : 'childs';

			// Recursive part
			if(!empty($el['childs'])) {
				$recursive = array_merge($phs, $this->renderElementsRecursive($el['childs']));

				// Handle blockTpl for Grids
				if(isset($el['tpe']['blockTpl'])) {
					foreach($el['tpe']['blockTpl'] as $block=>$blockTpls) {
						if(isset($recursive[$block]) && isset($blockTpls['outerTpl'])) $recursive[$block] = $this->parseTpl($blockTpls['outerTpl'], array_merge($phs, array('childs' =>$recursive[$block])));
					}
				}

				if(isset($el['tpe']['innerTpl'])) {
					$phs['childs'] = $this->parseTpl($el['tpe']['innerTpl'], $recursive);
				} else {
					$phs = $recursive;
				}
			}
			// Recursive part END

			// Prepare show_elements-mode
			$fetch = '';
			if($this->tpeOptions['show_elements']) {
				$title  = 'id = '.$el['id'] ."\n";
				$title .= 'type = '.$el['type'] ."\n\n";
				if(isset($el['attr']) && !empty($el['attr'])) {
					foreach ($el['attr'] as $param => $value) {
						$truncated = (strlen($value) > 50) ? substr($value, 0, 50) . '...' : $value;
						$title .= 'attr.' . $param . ' = ' . $truncated . "\n";
					}
					$title .= "\n";
				} else {
					$title .= 'No attributes found' . "\n\n";
				}
				if(isset($el['tpe']) && !empty($el['tpe'])) {
					foreach ($el['tpe'] as $param => $value) {
						$truncated = (strlen($value) > 50) ? substr($value, 0, 50) . '...' : $value;
						$title .= 'tpe.' . $param . ' = ' . $truncated . "\n";
					}
				} else {
					$title .= 'No tpe-options found';
				}
				$debugPhs = array(
					'target'=>$el['target'].'.'.$elId,
					'title'=>htmlentities($title)
				);
				$fetch .= $this->parseTpl('debug.element', $debugPhs); 
			};

			// Handle prepend-parameter
			if(isset($el['tpe']['prepend'])) $fetch .= $el['tpe']['prepend'] . "\n";
			
			// No more recursion / Render and return deepest child
			$source = $this->parseTpl($el['tpe']['tpl'], $phs);
			$source = $modx->parseManagerDocumentSource($source);
			$fetch .= $source . "\n";
			
			// Handle apppend-parameter
			if(isset($el['tpe']['append'])) $fetch .= $el['tpe']['append'] . "\n";
			
			// Wrap element inside an outerTpl
			if(isset($el['tpe']['outerTpl'])) {
				$output[$pos] .= $this->parseTpl($el['tpe']['outerTpl'], array_merge($phs, array('childs' =>$fetch))) . "\n";
			} else {
				$output[$pos] .= $fetch;
			}
			$iteration++;
		}

		return $output;
	}
	
	function parseTpl($tpl, $placeholders=array())
	{
		// Allow using snippets like [[mgrTpl]] in templates
		// $source = $modx->parseManagerDocumentSource($source);
		
		$tplHtml = $this->fetchTpl($tpl);
		return $this->parsePlaceholders($tplHtml, $placeholders);
	}

	function fetchTpl($tpl)
	{
		global $modx, $manager_theme;

		if(isset($this->tplCache[$tpl])) {
			$template = $this->tplCache[$tpl]['html'];
		} else {
			$tplFile = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/' . $tpl . '.html';
			if (!is_readable($tplFile)) $tplFile = MODX_MANAGER_PATH . 'media/style/common/tpl/' . $tpl . '.html';
			if (is_readable($tplFile)) {
				$template = file_get_contents($tplFile);
				$tags = $modx->getTagsFromContent($template);
				$this->tplCache[$tpl]['html'] = $template;
				$this->tplCache[$tpl]['tags'] = $tags[1];
			} else {
				$target = isset($placeholders['target']) ? $placeholders['target'] : '?';
				$id = isset($placeholders['id']) ? $placeholders['id'] : '?';
				if(empty($tpl)) $msg = sprintf('No template set for "%s.%s"', $target, $id);
				else $msg = sprintf('Template-File %s not found for "%s.%s"', $tpl, $target, $id);
				$this->debugMsg[] = $msg;
				return $msg;
			}
		}
		return $template;
	}

	function parsePlaceholders($source, $placeholder=array(), $pastTags=array())
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
		
		// Recursive parsing
		$tags = $modx->getTagsFromContent($source);
		if(!empty($pastTags) && $tags === $pastTags) $source = 'parsePlaceholders(): Loop prevented with tags '.print_r($pastTags, true).$source;
		else if(!empty($tags)) $source = $this->parsePlaceholders($source, $placeholder, $tags);

		return $source;
	}
}