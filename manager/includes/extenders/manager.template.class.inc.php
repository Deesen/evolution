<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $tpeOptions = array(
		'debug_info'=>false,     // show output + debug-info
		'show_elements'=>false, // echo element-ids
		'echo_arrays'=>false,   // echo only arrays
	);
	var $actionTpl = '';
	var $actionTplHtml = '';
	var $bodyTpl = 'body';
	var $dom = array();
	var $placeholders = array();
	var $tplCache = array();
	var $typeDefaults = array();
	var $debugMsg = array();
	var $debugSource = '';
	var $tpeActive = false;

	function __construct()
	{
		global $modx, $modx_manager_charset, $_lang, $_style;

		// Prepare DOM-array
		$this->dom['head'] = array();		// Everything related to <head>
		$this->dom['body'] = array();	    // All HTML-elements related to MODX action
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

	//////////////////////////////////////////////////////////////////
	// Template-Engine API
	//////////////////////////////////////////////////////////////////
	function isActive() {
		return $this->tpeActive;
	}
	
	// Target example: "userform.tab2.section2"
	function setElement($elType, $target='', $attr=array(), $tpe=array())
	{
		return $this->setDomElement($elType, $target, $attr, $tpe);
	}

	// Target example: "userform.tab2.section2"
	function setElementOrder($elTarget, $order)
	{
		if(!isset($this->dom['body']['elements'][$elTarget])) $this->debugMsg(sprintf('setElementOrder() : Element-ID "%s" not found', $elTarget));

		$this->dom['body']['elements'][$elTarget]['tpe']['order'] = $order;

		return $this;
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

	function setBodyTemplate($tpl) {
		$this->bodyTpl = $tpl;
		$this->tpeActive = true;
		return $this;
	}
	
	function setTpeOption($param, $value) {
		if(isset($this->tpeOptions[$param])) {
			$this->tpeOptions[$param] = $value;
		}
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

	function registerHeadScript($id, $script, $placeholder=NULL) {
		return $this->registerScript($id, $script, $placeholder, 'head');
	}

	function registerFooterScript($id, $script, $placeholder=NULL) {
		return $this->registerScript($id, $script, $placeholder, 'footer');
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

	function registerScriptFromFile($id, $file, $placeholder, $category)
	{
		if(is_null($file)) {
			$this->dom[$category]['js'][$id] = array(
				'script' => '',
				'file'   => NULL
			);
		} else {
			$scriptFile = MODX_MANAGER_PATH . $file;
			if ($file && is_readable($scriptFile)) {
				$script = file_get_contents($scriptFile);
				$script = $this->parsePlaceholders($script, $placeholder);

				$this->dom[$category]['js'][$id] = array(
					'script' => $script,
					'file'   => $file
				);
			}
			else {
				$this->debugMsg(sprintf('registerScriptFromFile() : File not found -&gt;', $file));
			}
		}
		return $this;
	}

	function registerScript($id, $script, $placeholder, $category)
	{
		if(is_null($script)) {
			$this->dom[$category]['js'][$id] = array(
				'script' => '',
				'placeholder' => NULL
			);
		} else {
			$script = $this->parsePlaceholders($script, $placeholder);

			$this->dom[$category]['js'][$id] = array(
				'script' => $script,
				'placeholder' => $placeholder
			);
		}
		return $this;
	}

	function resetRegistered($type='all') {
		switch($type) {
			case 'js':
				$this->dom['head']['js'] = array();
				$this->dom['footer']['js'] = array();
				break;
			case 'css':
				$this->dom['head']['css'] = array();
				break;
			case 'all':
				$this->dom['head']['js'] = array();
				$this->dom['footer']['js'] = array();
				$this->dom['head']['css'] = array();
				break;
		}
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
		return $this->moveDomElement($sourceEl, $targetEl, 'body');
	}

	// $param & $value = string, only $param = array
	function setElementTpe($target, $param, $value='')
	{
		return $this->setDomElementTpe($target, $param, $value, false);
	}

	// $param & $value = string, only $param = array
	function setElementChildsTpe($target, $param, $value='', $ignoreTypes='')
	{
		return $this->setDomElementTpe($target, $param, $value, true, $ignoreTypes);
	}

	// Change tpe-params for single-element, or all its children
	function setDomElementTpe($elementId, $param, $value, $allChildren, $ignoreTypes=false)
	{
		if(!isset($this->dom['body']['elements'][$elementId])) $this->debugMsg(sprintf('setDomElementTpe() : Element-ID "%s" not found', $elementId));
			
		if($allChildren) {
			$ignoreTypes = $ignoreTypes != '' ? explode(',', $ignoreTypes) : false;
			$childs = is_array($this->dom['body']['childs'][$elementId]) ? $this->dom['body']['childs'][$elementId] : array();
			foreach($childs as $elId=>$empty) {
				$el =& $this->dom['body']['elements'][$elId];
				if($ignoreTypes && in_array($el['type'], $ignoreTypes)) continue;
				if(is_array($param)) $el['tpe'] = array_merge($el['tpe'], $param);
				else $el['tpe'][$param] = $value;
			}
		} else {
			$el =& $this->dom['body']['elements'][$elementId];
			if(is_array($param)) {
				$el['tpe'] = array_merge($el['tpe'], $param);
			}
			else $el['tpe'][$param] = $value;
		}

		return $this;
	}
	
	// Move element and add it as child of another element
	function moveDomElement($sourceEl, $targetEl)
	{
		if(!isset($this->dom['body']['elements'][$sourceEl])) $this->debugMsg(sprintf('setDomElementTpe() : Source Element-ID "%s" not found', $sourceEl));
		if(!isset($this->dom['body']['elements'][$targetEl])) $this->debugMsg(sprintf('setDomElementTpe() : Target Element-ID "%s" not found', $targetEl));

		$sourceParent = $this->dom['body']['parents'][$sourceEl][0];

		unset($this->dom['body']['childs'][$sourceParent][$sourceEl]);
		$this->dom['body']['childs'][$targetEl][$sourceEl] = '';

		return $this;
	}
	
	// To be used by action-files in /manager/actions/*.*
	function renderAction()
	{
		global $modx, $manager_theme;

		// Load default or custom action-template before rendering body, parsing snippets etc
		$this->actionTplHtml = $this->fetchPhpTpl('media/style/[+manager_theme+]/tpl/actions/' . $this->actionTpl . '.php');

		$source = $this->actionTplHtml;
		$source = $modx->parseManagerDocumentSource($source);
		$source = $this->parsePlaceholders($source);
		return $source;
	}
	
	function fetchPhpTpl($filePath)
	{
		global $modx, $manager_theme;
		
		$phpTpl = str_replace('[+manager_theme+]', $manager_theme, $filePath);
		if (!is_readable(MODX_MANAGER_PATH.$phpTpl)) $phpTpl = str_replace('[+manager_theme+]', 'common', $filePath);
		if (is_readable(MODX_MANAGER_PATH.$phpTpl)) {
			$this->debugSource = $phpTpl;
			ob_start();
			$tpe =& $this;
			require(MODX_MANAGER_PATH.$phpTpl);
			$output = ob_get_contents();
			ob_end_clean();
			$this->debugSource = '';
		} else {
			return 'PHP-Template not found: '.$phpTpl;
		}
		
		return $output;
	}
	
	//////////////////////////////////////////////////////////////////
	// Internal Functions
	//////////////////////////////////////////////////////////////////
	function setDomElement($elType, $target='', $attr=array(), $tpe=array())
	{
		// Check attr and tpe
		if(!is_array($attr)) {
			$attr = array();
			$this->debugMsg(sprintf('Attr not passed as array for "%s"', $target));
		}
		if(!is_array($tpe)) {
			$tpe = array();
			$this->debugMsg(sprintf('Tpe not passed as array for "%s"', $target));
		}
		
		// Protect placeholders
		if(isset($tpe['protect_phs'])) {
			$pphs = explode(',', $tpe['protect_phs']);
			foreach($pphs as $i=>$ph) {
				if(isset($attr[$ph])) $attr[$ph] = $this->protectPlaceholders($attr[$ph]);
			}
		}
		
		// Determine parents and element-id
		$parentsExp = explode( '.', $target );
		$elementId = array_pop($parentsExp);   // Get last element as ID and remove it
		$parents = array();
		foreach($parentsExp as $i=>$parent) {
			$parents[$parent] = '';
		}
		
		$dom =& $this->dom['body'];
		
		// Check if all parents exist
		$parent = 'body';
		if(!empty($parents)) {
			foreach ($parents as $parent=>$empty) {
				if (!isset($dom['parents'][$parent])) {
					$this->debugMsg(sprintf('setDomElement() : Parent "%s" not found in target "%s"', $parent, $target));
					return $this;
				}
			}
		}
		
		if(isset($dom['elements'][$elementId])) {
			$this->debugMsg(sprintf('setDomElement() : Element with ID "%s" already exists', $elementId));
		}
		
		// All parents found, now set parents of new element
		$dom['parents'][$elementId] = array_reverse($parents);
		
		// Set childs of element
		if(!isset($dom['childs'][$parent])) $dom['childs'][$parent] = array();
		if(!in_array($elementId, $dom['childs'][$parent])) $dom['childs'][$parent][$elementId] = '';
		
		// Prepare html-attributes
		if(!isset($attr['name'])) $attr['name'] = $elementId;   // Element-ID is default attribute: name="[+id+]"
		
		// Prepare template-engine params
		$tpe = array_merge(
			$this->getElementTypeDefaults($elType, $attr),  // Types are set in engine.php
			// Add internal params
			array(
				'order'=>count($dom['childs'][$parent]),
			),
			$tpe
		);
		
		// Finally set element with prepared values
		$dom['elements'][$elementId] = array(
			'type'=>$elType,
			'id'=>$elementId,
			'target'=>empty($parents) ? 'body' : $target,
			'attr'=>$attr,
			'tpe'=>$tpe
		);
		
		return $this;
	}

	function getElementTypeDefaults($elType, $attr)
	{
		$key = $elType;
		// Allow easy sub-types via .
		if(isset($attr['type']) && !empty($attr['type'])) {
			$key2 = $elType.'.'.$attr['type'];
			if(isset($this->typeDefaults[$key2])) return $this->typeDefaults[$key2];
		}
		if(isset($this->typeDefaults[$key])) return $this->typeDefaults[$key];
		return array();
	}

	// Used in /manager/index.php
	function renderFrame($name)
	{
		global $modx, $_style, $_lang, $site_name, $use_browser, $which_browser, $manager_theme;

		$tpe =& $this;
		
		// Load default frame setup
		require(MODX_MANAGER_PATH."frames/{$name}.php");

		$modx->invokeEvent('OnManagerPreFrameLoader',array('action'=>$modx->manager->action));
		
		// Load frame template
		$phpTpl = "media/style/{$manager_theme}/tpl/frames/{$name}.php";
		if (!is_readable(MODX_MANAGER_PATH.$phpTpl)) $phpTpl = "media/style/common/tpl/frames/{$name}.php";
		if (is_readable(MODX_MANAGER_PATH.$phpTpl)) {
			$this->debugSource = $phpTpl;
			ob_start();
			require(MODX_MANAGER_PATH.$phpTpl);
			$output = ob_get_contents();
			ob_end_clean();
			$this->debugSource = '';
		} else {
			return 'PHP-Template not found: '.$phpTpl;
		}

		$modx->invokeEvent('OnManagerFrameLoader',array('action'=>$modx->manager->action));
		
		return $output;
	}

	// Used in /manager/index.php
	function renderFullDom($actionHtml, $renderMainFrame=true)
	{
		global $modx, $manager_theme, $SystemAlertMsgQueque;
		
		$tpeFooter = '';
		
		if($renderMainFrame) {
			// display system alert window if messages are available
			if (count($SystemAlertMsgQueque) > 0) {
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

			if (in_array($modx->manager->action, array(85, 27, 4, 72, 13, 11, 12, 87, 88)))
				$tpeFooter .= $modx->manager->loadDatePicker($modx->config['mgr_date_picker_path']);

			// Get output of plugin-event "OnManagerMainFrameHeaderHTMLBlock"
			$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
			$this->dom['head']['OnManagerMainFrameHeaderHTMLBlock'] = is_array($evtOut) ? implode("\n", $evtOut) : '';
		}
		
		$tpeFooter .= $this->mergeDomJs('footer');

		$placeholders = array();
		$placeholders['tpe'] = array(
			'head.css'=>$this->mergeDomCss(),
			'head.javascript'=>$this->mergeDomJs('head'),
			'body'=>$actionHtml, // Get output i.e. from actions/mutate_content.dynamic.php
			'debug'=>$this->mergeDebugMsg(),
			'footer'=>$tpeFooter
		);
		
		$source = $this->fetchTpl($this->bodyTpl);
		$source = $this->parsePlaceholders($source, $placeholders);
		$source = $modx->parseManagerDocumentSource($source);
		$source = $this->unprotectPlaceholders($source); // Codemirror-Script contains [[]] etc that have to be protected
		
		return $source;
	}

	function mergeElementsList($elementId, $depth, $outerTpl, $rowTpl, $cssFirst='', $cssLast='')
	{
		$output = '';
		
		if(isset($this->dom['body']['childs'][$elementId])) {
			$childs = $this->dom['body']['childs'][$elementId];

			// @todo: Use "depth"-param

			$iteration = 1;
			$total     = count($childs);
			foreach ($childs as $elId => $empty) {
				$el  = $this->dom['body']['elements'][$elId];
				$tpe = array();
				if ($iteration == $total) $tpe = array('cssFirst' => '', 'cssLast' => $cssLast);
				else if ($iteration == 1) $tpe = array('cssFirst' => $cssFirst, 'cssLast' => '');
				else $tpe = array('cssFirst' => '', 'cssLast' => '');
				$phs = $this->prepareElementPlaceholders($el, '', $tpe);
				$output .= $this->parseTpl($rowTpl, $phs, $el);
				$iteration++;
			}
		}
		return $this->parseTpl($outerTpl, array('childs' =>$output), $this->dom['body']['elements'][$elementId]);
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

	function mergeDomCss()
	{
		$output = '';
		if(isset($this->dom['head']['css']['src'])) {
			foreach ($this->dom['head']['css']['src'] as $id => $src) {
				$output .= '	<link rel="stylesheet" type="text/css" href="' . $src . '" />' . "\n";
			};
		}
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

	

	function renderBodyElementsRecursive($elementId, $renderRecursive=true, $returnString=false, $noChildsReturnEmpty=false)
	{
		global $modx;
		
		if(!isset($this->dom['body']['elements'][$elementId])) return sprintf('renderBodyElementsRecursive() : Element "%s" not found', $elementId);
		
		$output = array();
		$childs = $this->dom['body']['childs'][$elementId];
		$parents = $this->dom['body']['parents'][$elementId];
		
		$iteration = 1;
		$totalChilds = count($childs);
		
		if($childs && $renderRecursive) {
			// Sort-function to sort childs by 'tpe.order'
			foreach ($childs as $key => $empty) {
				$childs[$key] = array('order' => $this->dom['body']['elements'][$key]['tpe']['order']);
			}
			if (!function_exists('cmp')) {
				function cmp($a, $b) {
					if ($a['order'] == $b['order']) {
						return 0;
					}

					return ($a['order'] < $b['order']) ? -1 : 1;
				}
			}
			uasort($childs, "cmp");

			foreach ($childs as $childId => $empty) {

				$el = $this->dom['body']['elements'][$childId];

				// Set first / last css-class
				if ($iteration === $totalChilds) $tpe = array('cssFirst' => '', 'cssLast' => isset($el['tpe']['cssLast']) ? $el['tpe']['cssLast'] : '');
				else if ($iteration === 1) $tpe = array('cssFirst' => isset($el['tpe']['cssFirst']) ? $el['tpe']['cssFirst'] : '', 'cssLast' => '');
				else $tpe = array('cssFirst' => '', 'cssLast' => '');

				$phs = $this->prepareElementPlaceholders($el, '', $tpe);
				$pos = isset($el['tpe']['pos']) ? $el['tpe']['pos'] : 'childs';

				// Recursive part
				if (!empty($this->dom['body']['childs'][$childId])) {

					$recursive = array_merge($phs, $this->renderBodyElementsRecursive($childId));

					// Handle blockTpl for Grids
					if (isset($el['tpe']['blockTpl'])) {
						foreach ($el['tpe']['blockTpl'] as $block => $blockTpls) {
							if (isset($recursive[$block]) && isset($blockTpls['outerTpl'])) $recursive[$block] = $this->parseTpl($blockTpls['outerTpl'], array_merge($phs, array('childs' => $recursive[$block])), $el);
						}
					}

					if (isset($el['tpe']['innerTpl'])) {
						$phs['childs'] = $this->parseTpl($el['tpe']['innerTpl'], $recursive, $el);
					}
					else {
						$phs = $recursive;
					}
				}
				// Recursive part END

				$elementTpl = $el['tpe']['tpl'];

				// Prepare show_elements-mode
				$fetch = '';
				if ($this->tpeOptions['show_elements']) {
					$this->fetchTpl($elementTpl); // Prepare tags-array in $this->tplCache
					// If [+debug+] does not exist in element-template, simply prepend element-info 
					if (!in_array('debug', $this->tplCache[$elementTpl]['tags'])) {
						$fetch .= $this->renderElementsDebugInfo($el);
					}
					else {
						$phs['debug']     = $this->renderElementsDebugInfo($el); // Provide info using template "debug.element"
						$phs['debug_raw'] = $this->renderElementsDebugInfo($el, true); // Provide info without template
					}
				} else {
					// Just add info as HTML-comment
					$fetch .= '<!-- '.$el['target'].' -->'. "\n";
				};

				// Handle prepend-parameter
				if (isset($el['tpe']['prepend'])) $fetch .= $el['tpe']['prepend'] . "\n";

				// No more recursion / Render and return deepest child
				$source = $this->parseTpl($elementTpl, $phs, $el);
				$source = $modx->parseManagerDocumentSource($source);
				$fetch .= $source . "\n";

				// Handle apppend-parameter
				if (isset($el['tpe']['append'])) $fetch .= $el['tpe']['append'] . "\n";

				// Wrap element inside an outerTpl
				if (isset($el['tpe']['outerTpl'])) {
					$output[$pos] .= $this->parseTpl($el['tpe']['outerTpl'], array_merge($phs, array('childs' => $fetch)), $el) . "\n";
				}
				else {
					$output[$pos] .= $fetch;
				}
				$iteration++;
			}
		} else if($noChildsReturnEmpty) {
			return '';
		}
		
		// Render grand parent
		if($returnString) {
			$el = $this->dom['body']['elements'][$elementId];
			$phs = $this->prepareElementPlaceholders($el);
			// $pos = isset($el['tpe']['pos']) ? $el['tpe']['pos'] : 'childs';
			return $this->parseTpl($el['tpe']['tpl'], array_merge($phs, $output), $el) . "\n";
		}

		return $output;
	}
	
	function parseTpl($tpl, $placeholders=array(), $el=array())
	{
		$tplHtml = $this->fetchTpl($tpl);
		return $this->parsePlaceholders($tplHtml, $placeholders, $el);
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
				$this->debugMsg($msg);
				return $msg;
			}
		}
		return $template;
	}

	function parsePlaceholders($source, $placeholder=array(), $el=array(), $pastTags=array())
	{
		global $modx, $_lang, $_style;
		
		if(!is_array($placeholder)) $placeholder = array();
		$thisPlaceholder = array_merge($this->placeholders, $placeholder);
		$source = $modx->mergeSettingsContent($source);
		$tags = $modx->getTagsFromContent($source);

		if(!empty($tags)) {
			foreach($tags[1] as $key=>$tag) {
				$value = '';

				if (substr($tag, 0, 7) == 'parent.') {
					$dt = $this->getParentTagAndPlaceholder($tag, $el);
					$tag = $dt['tag'];
					$placeholder = array_merge($this->placeholders, $dt['placeholder']);
				}
				else $placeholder = $thisPlaceholder;
				
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
					if($attrKey == 'value') $value = $this->protectPlaceholders($value);
				} else if (substr($tag, 0, 4) == 'tpe.') {
					// Replace template-engine params
					$tpeKey = substr($tag, 4);
					$value = isset($placeholder['tpe'][$tpeKey]) ? $placeholder['tpe'][$tpeKey] : '';
				} else if (substr($tag, 0, 3) == 'el.') {
					// Replace template-engine params
					$element = substr($tag, 3);
					$value = 'el. not ready';
				} else if(isset($placeholder[$tag])){
					// Parse normal placeholders
					/*
					if($tag == 'id' && isset($placeholder['attr']['id'])) {
						$value = $placeholder['attr']['id'];
					}
					else
					*/
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
		else if(!empty($tags)) $source = $this->parsePlaceholders($source, $placeholder, $el, $tags);

		return $source;
	}
	
	function getParentTagAndPlaceholder($tag, $el)
	{
		$elementId = $el['id'];
		
		// Loop down parent-tree like [+parent.parent.parent.id+]
		while(substr($tag, 0, 7) == 'parent.') {
			$tag = substr($tag, 7); // Remove first "parent."
			
			// Break loop to get first parent!
			foreach($this->dom['body']['parents'][$elementId] as $elId=>$empty) { 
				$elementId = $elId;
				break; 
			} 
		}
		
		$el = $this->dom['body']['elements'][$elementId];
		$phs = $this->prepareElementPlaceholders($el);
		
		return array(
			'tag'=>$tag,
			'placeholder'=>$phs
		);
	}

	function protectPlaceholders($value)
	{
		return str_replace(
			array('[[',         ']]',       '[+',       '+]'),
			array('<!--[-[',    ']-]-->',   '<!--[-+',   '+-]-->'),
			$value
		);
	}

	function unprotectPlaceholders($value)
	{
		return str_replace(
			array('<!--[-[',    ']-]-->',   '<!--[-+',   '+-]-->'),
			array('[[',         ']]',       '[+',       '+]'),
			$value
		);
	}
	
	function debugMsg($msg)
	{
		if(!in_array($msg, $this->debugMsg)) $this->debugMsg[] = $this->debugSource.' - '.$msg;
	}

	function mergeDebugMsg()
	{
		$debug = '';
		if($this->tpeOptions['debug_info']) {
			$debug .= '<hr/>';
			$debug .= '<h2>debugMsg</h2>'."\n";
			$debug .= !empty($this->debugMsg) ? '<pre style="font-size:12px;">'.print_r($this->debugMsg,true).'</pre>' : 'No errors found.';
			$debug .= '<h2>Elements-Matrix</h2>'."\n";
			$debug .= $this->renderDebugElementsMatrixRecursive($this->dom['body']['childs']['body']);
		}
		if($this->tpeOptions['echo_arrays']) {
			$debug .= '<h2>dom</h2><pre style="font-size:12px;">'.print_r($this->dom,true).'</pre>'."\n";
			$debug .= '<h2>typeDefaults</h2><pre style="font-size:12px;">'.print_r($this->typeDefaults,true).'</pre>'."\n";
		}
		if($this->tpeOptions['echo_arrays']) { echo $debug; exit; }
		return $debug ? '<div class="debug">'. $debug. '</div>' : '';
	}
	
	function renderDebugElementsMatrixRecursive($childs)
	{
		$output = '<ul>';
		foreach($childs as $childId=>$empty) {
			$output .= '<li>';
			$el = $this->dom['body']['elements'][$childId];
			$output .= $this->renderElementsDebugInfo($el);
			$output .= isset($this->dom['body']['childs'][$childId]) ? $this->renderDebugElementsMatrixRecursive($this->dom['body']['childs'][$childId]) : '';
			$output .= '</li>';
		}
		$output .= '</ul>';
		return $output;
	}
	
	function renderElementsDebugInfo($el, $returnRaw=false)
	{
		$title  = 'id = '.$el['id'] ."\n";
		$title .= 'type = '.$el['type'] ."\n\n";
		if(isset($el['attr']) && !empty($el['attr'])) {
			foreach ($el['attr'] as $param => $value) {
				if(is_array($value)) {
					$value = print_r($value, true);
				}
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
		
		$title = htmlentities($title);
		$target = $el['target'] == 'body' ? $el['id'] : $el['target'];
		
		if($returnRaw) return $target."\n".$title;
		
		$debugPhs = array(
			'target'=>$target,
			'title'=>$title
		);
		
		return $this->parseTpl('debug.element', $debugPhs);
	}
	
	function setMainMenu()
	{
		global $modx, $_lang, $_style;
		
		//mainMenu
		$sitemenu['site'] = array('site', 'main', $_lang['site'], '#site', $_lang['site'], 'new NavToggle(this); return false;', '', '', 0, 10, 'active');

		if ($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin') || $modx->hasPermission('file_manager')) {
			$sitemenu['elements'] = array('elements', 'main', $_lang['elements'], '#elements', $_lang['elements'], 'new NavToggle(this); return false;', '', '', 0, 20, '');
		}

		if ($modx->hasPermission('exec_module')) {
			$sitemenu['modules'] = array('modules', 'main', $_lang['modules'], '#modules', $_lang['modules'], 'new NavToggle(this); return false;', '', '', 0, 30, '');
		}

		if ($modx->hasPermission('edit_user') || $modx->hasPermission('edit_web_user') || $modx->hasPermission('edit_role') || $modx->hasPermission('access_permissions') || $modx->hasPermission('web_access_permissions')) {
			$sitemenu['users'] = array('users', 'main', $_lang['users'], '#users', $_lang['users'], 'new NavToggle(this); return false;', 'edit_user', '', 0, 40, '');
		}

		if ($modx->hasPermission('bk_manager') || $modx->hasPermission('remove_locks') || $modx->hasPermission('import_static') || $modx->hasPermission('export_static') || $modx->hasPermission('settings')) {
			$sitemenu['tools'] = array('tools', 'main', $_lang['tools'], '#tools', $_lang['tools'], 'new NavToggle(this); return false;', '', '', 0, 50, '');
		}

		if ($modx->hasPermission('view_eventlog') || $modx->hasPermission('logs')) {
			$sitemenu['reports'] = array('reports', 'main', $_lang['reports'], '#reports', $_lang['reports'], 'new NavToggle(this); return false;', '', '', 0, 60, '');
		}

// Site Menu
		$sitemenu['home'] = array('home', 'site', $_lang['home'], 'index.php?a=2', $_lang['home'], 'this.blur();', '', 'main', 0, 10, '');
		$sitemenu['preview'] = array('preview', 'site', $_lang['preview'], '../', $_lang['preview'], 'this.blur();', '', '_blank', 0, 20, '');
		$sitemenu['refresh_site'] = array('refresh_site', 'site', $_lang['refresh_site'], 'index.php?a=26', $_lang['refresh_site'], 'this.blur();', '', 'main', 0, 30, '');
		$sitemenu['search'] = array('search', 'site', $_lang['search'], 'index.php?a=71', $_lang['search'], 'this.blur();', '', 'main', 1, 40, '');
		if ($modx->hasPermission('edit_document')) {
			$sitemenu['add_resource'] = array('add_resource', 'site', $_lang['add_resource'], 'index.php?a=4', $_lang['add_resource'], 'this.blur();', 'new_document', 'main', 0, 50, '');
			$sitemenu['add_weblink'] = array('add_weblink', 'site', $_lang['add_weblink'], 'index.php?a=72', $_lang['add_weblink'], 'this.blur();', 'new_document', 'main', 0, 60, '');
		}

// Elements Menu
		if ($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin')) {
			$sitemenu['element_management'] = array('element_templates', 'elements', $_lang['element_management'], 'index.php?a=76', $_lang['element_management'], 'this.blur();', 'new_template,edit_template,new_snippet,edit_snippet,new_chunk,edit_chunk,new_plugin,edit_plugin', 'main', 0, 10, '');
		}

//$sitemenu['element_templates']     = array('element_templates','elements',$_lang['manage_templates'],'index.php?a=76&tab=0',$_lang['manage_templates'],'this.blur();','new_template,edit_template','main',0,10,'');
//$sitemenu['element_tplvars']     = array('element_tplvars','elements',$_lang['tmplvars'],'index.php?a=76&tab=1',$_lang['tmplvars'],'this.blur();','new_template,edit_template','main',0,20,'');
//$sitemenu['element_htmlsnippets']     = array('element_htmlsnippets','elements',$_lang['manage_htmlsnippets'],'index.php?a=76&tab=2',$_lang['manage_htmlsnippets'],'this.blur();','new_chunk,edit_chunk','main',0,30,'');
//$sitemenu['element_snippets']     = array('element_snippets','elements',$_lang['manage_snippets'],'index.php?a=76&tab=3',$_lang['manage_snippets'],'this.blur();','new_snippet,edit_snippet','main',0,40,'');
//$sitemenu['element_plugins']     = array('element_plugins','elements',$_lang['manage_plugins'],'index.php?a=76&tab=4',$_lang['manage_plugins'],'this.blur();','new_plugin,edit_plugin','main',0,50,'');
//$sitemenu['element_categories']     = array('element_categories','elements',$_lang['element_categories'],'index.php?a=76&tab=5',$_lang['element_categories'],'this.blur();','new_template,edit_template,new_snippet,edit_snippet,new_chunk,edit_chunk,new_plugin,edit_plugin','main',1,60,'');

		if ($modx->hasPermission('file_manager')) {
			$sitemenu['manage_files'] = array('manage_files', 'elements', $_lang['manage_files'], 'index.php?a=31', $_lang['manage_files'], 'this.blur();', 'file_manager', 'main', 0, 70, '');
		}
// Modules Menu Items
		if ($modx->hasPermission('new_module')) {
			$sitemenu['new_module'] = array('new_module', 'modules', $_lang['module_management'], 'index.php?a=106', $_lang['module_management'], 'this.blur();', 'new_module,edit_module', 'main', 1, 0, '');
		}

		if ($modx->hasPermission('exec_module')) {
			if ($_SESSION['mgrRole'] != 1) {
				$rs = $modx->db->query('SELECT DISTINCT sm.id, sm.name, mg.member
				FROM ' . $modx->getFullTableName('site_modules') . ' AS sm
				LEFT JOIN ' . $modx->getFullTableName('site_module_access') . ' AS sma ON sma.module = sm.id
				LEFT JOIN ' . $modx->getFullTableName('member_groups') . ' AS mg ON sma.usergroup = mg.user_group
                WHERE (mg.member IS NULL OR mg.member = ' . $modx->getLoginUserID() . ') AND sm.disabled != 1 AND sm.locked != 1');
			} else {
				$rs = $modx->db->select('*', $modx->getFullTableName('site_modules'), 'disabled != 1');
			}
			$i = 10;
			while ($content = $modx->db->getRow($rs)) {
				$sitemenu['module' . $content['id']] = array('module' . $content['id'], 'modules', $content['name'], 'index.php?a=112&id=' . $content['id'], $content['name'], 'this.blur();', '', 'main', 0, 0, $i + 10, '');
				$i = $i + 10;
			}
		}

// security menu items (users)

		if ($modx->hasPermission('edit_user')) {
			$sitemenu['user_management_title'] = array('user_management_title', 'users', $_lang['user_management_title'], 'index.php?a=75', $_lang['user_management_title'], 'this.blur();', 'edit_user', 'main', 0, 10, '');
		}

		if ($modx->hasPermission('edit_web_user')) {
			$sitemenu['web_user_management_title'] = array('web_user_management_title', 'users', $_lang['web_user_management_title'], 'index.php?a=99', $_lang['web_user_management_title'], 'this.blur();', 'edit_web_user', 'main', 0, 20, '');
		}

		if ($modx->hasPermission('edit_role')) {
			$sitemenu['role_management_title'] = array('role_management_title', 'users', $_lang['role_management_title'], 'index.php?a=86', $_lang['role_management_title'], 'this.blur();', 'new_role,edit_role,delete_role', 'main', 0, 30, '');
		}

		if ($modx->hasPermission('access_permissions')) {
			$sitemenu['manager_permissions'] = array('manager_permissions', 'users', $_lang['manager_permissions'], 'index.php?a=40', $_lang['manager_permissions'], 'this.blur();', 'access_permissions', 'main', 0, 40, '');
		}

		if ($modx->hasPermission('web_access_permissions')) {
			$sitemenu['web_permissions'] = array('web_permissions', 'users', $_lang['web_permissions'], 'index.php?a=91', $_lang['web_permissions'], 'this.blur();', 'web_access_permissions', 'main', 0, 50, '');
		}


// Tools Menu

		if ($modx->hasPermission('bk_manager')) {
			$sitemenu['bk_manager'] = array('bk_manager', 'tools', $_lang['bk_manager'], 'index.php?a=93', $_lang['bk_manager'], 'this.blur();', 'bk_manager', 'main', 0, 10, '');
		}

		if ($modx->hasPermission('remove_locks')) {
			$sitemenu['remove_locks'] = array('remove_locks', 'tools', $_lang['remove_locks'], 'javascript:removeLocks();', $_lang['remove_locks'], 'this.blur();', 'remove_locks', '', 0, 20, '');
		}

		if ($modx->hasPermission('import_static')) {
			$sitemenu['import_site'] = array('import_site', 'tools', $_lang['import_site'], 'index.php?a=95', $_lang['import_site'], 'this.blur();', 'import_static', 'main', 0, 30, '');
		}

		if ($modx->hasPermission('export_static')) {
			$sitemenu['export_site'] = array('export_site', 'tools', $_lang['export_site'], 'index.php?a=83', $_lang['export_site'], 'this.blur();', 'export_static', 'main', 1, 40, '');
		}

		if ($modx->hasPermission('settings')) {
			$sitemenu['edit_settings'] = array('edit_settings', 'tools', $_lang['edit_settings'], 'index.php?a=17', $_lang['edit_settings'], 'this.blur();', 'settings', 'main', 0, 50, '');
		}

// Reports Menu

		if ($modx->hasPermission('view_eventlog')) {
			$sitemenu['site_schedule'] = array('site_schedule', 'reports', $_lang['site_schedule'], 'index.php?a=70', $_lang['site_schedule'], 'this.blur();', '', 'main', 0, 10, '');
		}

		if ($modx->hasPermission('view_eventlog')) {
			$sitemenu['eventlog_viewer'] = array('eventlog_viewer', 'reports', $_lang['eventlog_viewer'], 'index.php?a=114', $_lang['eventlog_viewer'], 'this.blur();', 'view_eventlog', 'main', 0, 20, '');
		}

		if ($modx->hasPermission('logs')) {
			$sitemenu['view_logging'] = array('view_logging', 'reports', $_lang['view_logging'], 'index.php?a=13', $_lang['view_logging'], 'this.blur();', 'logs', 'main', 0, 30, '');
			$sitemenu['view_sysinfo'] = array('view_sysinfo', 'reports', $_lang['view_sysinfo'], 'index.php?a=53', $_lang['view_sysinfo'], 'this.blur();', 'logs', 'main', 0, 40, '');
		}

		$menu = $modx->invokeEvent("OnManagerMenuPrerender", array('menu' => $sitemenu));
		$menu = unserialize($menu[0]);
		if (is_array($menu)) $sitemenu = $menu;

		include_once(MODX_MANAGER_PATH . 'includes/menu.class.inc.php');
		$menu = new EVOmenu();
		$menu->Build($sitemenu,array(),true);
	}
}