<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

class ManagerTemplateEngine {

	var $dom = array();
	var $placeholders = array();
	var $actionTpl = ''; 
	var $templates = array();

	function __construct()
	{
		global $modx, $modx_manager_charset;

		// Prepare DOM-array
		$this->dom['buttons'] = array();
		$this->dom['forms'] = array();
		$this->dom['header'] = array();
		// $this->dom['body'] = array();
		// $this->dom['body']['content'] = array();
		$this->dom['footer'] = array();
		$this->dom['title'] = 'No title';

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

	function setActionTemplate($tpl) {
		$this->actionTpl = $tpl;
		return $this;
	}

	function setTitle($title) {
		$this->dom['title'] = $title;
		return $this;
	}

	function addForm($id, $name, $action, $method) {
		$this->dom['forms'][$id] = array(
			'name'=>$name,
			'id'=>$id,
			'action'=>$action,
			'method'=>$method
		);
		return $this;
	}

	function addSection($formid, $section, $label, $grid='1column', $position='default') {
		$this->dom['forms'][$formid]['sections'][$section] = array(
			'label'=>$label,
			'grid'=>$grid,
			'position'=>$position
		);
		return $this;
	}

	function addFormField($formid, $name, $type, $value=NULL, $label='', $params=array()) {
		$this->dom['forms'][$formid]['inputs'][$name] = array(
			'name'=>$name,
			'type'=>$type,
			'value'=>$value,
			'label'=>$label,
			'params'=>$params,
		);
		return $this;
	}
	
	function alert($message, $class='info') {
		$this->dom['alerts'][$class][] = $message;
		return $this;
	}

	function addTab($formId, $tabId, $label, $grid) {
		$this->dom['forms'][$formId]['tabs'][$tabId] = array(
			'label'=>$label,
			'grid'=>$grid
		);
		return $this;
	}

	function setActionButtons($arr) {
		$this->dom['buttons'] = array_merge($this->dom['buttons'], $arr);
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

	function addBody($content) {
		$this->dom['body']['content'] = $this->dom['body']['content'] + $content;
		return $this;
	}

	function setPlaceholder($key, $value) {
		$this->placeholders[$key] = $value;
		return $this;
	}

	function renderFullDom()
	{
		global $modx;

		// Prepare placeholders
		$placeholders = array(
			'title'=>$this->dom['title']
		);

		// load custom-action to allow customizing dom-array

		$source = $this->fetchTpl('body', $placeholders);
		return $modx->parseManagerDocumentSource($source);
	}
	
	function renderActionTemplate()
	{
		global $modx;
		
		// Prepare placeholders
		$placeholders = array(
			'title'=>$this->dom['title']
		);
		
		// load custom-action to allow customizing dom-array
		
		$source = $this->fetchTpl('body', $placeholders);
		return $modx->parseManagerDocumentSource($source);
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

	function mergeDomActionButtons($outerTpl, $rowTpl)
	{
		$outerTpl = !empty($outerTpl) ? $outerTpl : 'actionButtons';
		$rowTpl = !empty($rowTpl) ? $rowTpl : 'actionButton';

		$output = '';
		if(!empty($this->dom['buttons'])) {
			foreach($this->dom['buttons'] as $id=>$placeholders) {
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
		/*
		$positions = array();
		foreach($this->dom['body']['content'] as $id=>$el) {
			$pos = isset($el['position']) ? $el['position'] : 'default';
			$type = isset($el['type']) ? $el['type'] : 'html'; 
			switch($type) {
				case 'form':
					$positions[$pos] .= $this->renderForm($id, $el);
					break;
				case 'message':
					$positions[$pos] .= $this->fetchTpl('body.message', $el);
					break;
				case 'html':
					// $blocks = $el['html']."\n";
					break;
			}
		};
		*/
		
		return $this->fetchTpl('actions/'.$this->actionTpl, array());
	}
	
	function mergeFormInputs($formId, $outerTpl, $rowTpl, $useTabs=true, $useSections=true, $filter='', $sortBy='order')
	{
		if($useTabs && empty($this->dom['forms'][$formId]['tabs'])) $useTabs = false;
		if($useSections && empty($this->dom['forms'][$formId]['sections'])) $useTabs = false;

		$form = $this->dom['forms'][$formId];
		$inputs = $this->dom['forms'][$formId]['inputs'];
		$tabs = $useTabs ? $this->dom['forms'][$formId]['tabs'] : false;
		$sections = $useSections ? $this->dom['forms'][$formId]['sections'] : false;

		$output = '';
		
		// HANDLE TABS
		if($tabs) {
			// BUILD ARRAY WITH ALL INPUTS PER TAB
			foreach($inputs as $id=>$el) {
				$elTab = isset($el['params']['tab']) ? $el['params']['tab'] : false;
				if($elTab && isset($tabs[$elTab])) {
					$tabs[$elTab]['content'][$id] = $el; 
				}
			}
			
			// BUILD ARRAY WITH ALL INPUTS PER SECTION
			foreach($tabs as $tabId=>$tab) {
				$tabSections = array();
				if(!empty($tab['content'])) {
					foreach ($tab['content'] as $id => $el) {
						$elSection = isset($el['params']['section']) ? $el['params']['section'] : 'none';
						if ($elSection == 'none' || isset($sections[$elSection])) {
							$tabSections[$elSection][$id] = $el;
						}
					}
					$tabs[$tabId]['sections'] = $tabSections;
				}
			}
			
			// RENDER TABS WITH SECTIONS
			foreach($tabs as $tabId=>$tab) {
				$sectionContent = array();
				if(!empty($tab['sections'])) {
					foreach ($tab['sections'] as $sectionId => $content) {

						// if(!isset($sectionContent[$sectionId])) $sectionContent[$sectionId] = '';
						$grid        = isset($sections[$sectionId]['grid']) ? $sections[$sectionId]['grid'] : NULL;
						$grid        = is_null($grid) ? $tab['grid'] : $grid;
						$contentArr  = $this->renderContent($content);
						$sectionGrid = $this->fetchTpl('grid.' . $grid, $contentArr);

						$pos = isset($sections[$sectionId]['position']) ? $sections[$sectionId]['position'] : 'none';

						if ($pos == 'none') {
							$sectionContent['default'] .= $sectionGrid;
						}
						else {
							$sectionContent[$pos] .= $this->fetchTpl('form.section', array('title' => $sections[$sectionId]['label'], 'content' => $sectionGrid));
						}
					}
				}
				
				$grid = isset($tabs[$tabId]['grid']) ? $tabs[$tabId]['grid'] : '1column';
				$tabContent = $this->fetchTpl('grid.'.$grid, $sectionContent);
				$output .= $this->fetchTpl('tab.tab', array(
					'id'=>$tabId,
					'label'=>$tabs[$tabId]['label'],
					'content'=>$tabContent
				));
			}
			
			$output = $this->fetchTpl('tab.container', array('content'=>$output));
			
		////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// NO TABS, HANDLE ONLY SECTIONS
		} else if($sections) {

			// BUILD ARRAY WITH ALL INPUTS PER TAB
			foreach($inputs as $id=>$el) {
				$elSection = isset($el['params']['section']) ? $el['params']['section'] : false;
				if($elSection && isset($sections[$elSection])) {
					$sections[$elSection]['content'][$id] = $el;
				} else {
					$sections['none']['content'][$id] = $el;
				}
			}

			// RENDER SECTIONS
			foreach($sections as $sectionId=>$section) {
				$sectionContent = array();
				if(!empty($section['content'])) {

					$contentArr  = $this->renderContent($section['content']);

					$grid        = isset($sections[$sectionId]['grid']) ? $sections[$sectionId]['grid'] : '1column';
					$sectionContent = $this->fetchTpl('grid.' . $grid, $contentArr);

					if ($sectionId == 'none') {
						$output .= $sectionContent;
					}
					else {
						$output .= $this->fetchTpl('form.section', array('title' => $sections[$sectionId]['label'], 'content' => $sectionContent));
					}
				}

			}
			
		// NO TABS, NO SECTIONS, HANDLE ONLY INPUTS
		} else {
			$output = join('', $this->renderContent($inputs));
		}

		return $output;
	}
	
	function renderContent($content) {
		$positions = array();
		foreach($content as $id=>$el) {

			$pos = isset($el['params']['position']) ? $el['params']['position'] : 'default';

			$el = array_merge($el, array(
				'id'=>$id,
				// 'formId'=>$formId
			));

			$positions[$pos] .= $this->renderFormField($el);
			$positions[$pos] .= "\n";
		}
		
		foreach($positions as $pos=>$content) {
			$positions[$pos] = $this->fetchTpl('form.table', array(
				'content'=>$content
			));
		}
		
		return $positions;
	}
	
	function renderFormField($el, $noOuterTpl=false)
	{
		$input = 'Type not found:'.print_r($el,true);
		
		switch($el['type']) {
			case 'password':
				$input = $this->fetchTpl('form.input.password', $el);
				break;
			case 'message':
				$input = $this->fetchTpl('form.message', $el);
				break;
			case 'hidden':
				return '	<input type="hidden" name="'.$el['name'].'" value="'.$el['value'].'" />';
			case 'submit':
				$displayNone = ' style="display:none"';
				return '	<input type="submit" name="'.$el['name'].'"'.$displayNone.'>';
		}
		
		if($noOuterTpl) return $input;
		
		return $this->fetchTpl('form.table.row', array(
			'label'=>$el['label'],
			'input'=>$input
		));
	}

	function fetchTpl($tpl, $placeholders, $noParse=false)
	{
		global $manager_theme;
		
		if(isset($this->templates[$tpl])) {
			$template = $this->templates[$tpl];
		} else {
			$tplFile = MODX_MANAGER_PATH . 'media/style/' . $manager_theme . '/tpl/' . $tpl . '.html';
			if (!file_exists($tplFile)) $tplFile = MODX_MANAGER_PATH . 'media/style/common/tpl/' . $tpl . '.html';
			if (file_exists($tplFile)) {
				$template              = file_get_contents($tplFile);
				$this->templates[$tpl] = $template;
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