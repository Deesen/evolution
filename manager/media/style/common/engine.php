<?php
/* Default Manager TemplateEngine Setup */

// Default CSS
$this->registerCssSrc('main', 'media/style/[(manager_theme)]/style.css')

// Allow easy global change
->setPlaceholder('icon_tooltip', $_style["icons_tooltip_over"])

// Default Javascript-Files
->registerScriptSrc('jquery', 		'[(mgr_jquery_path)]', '3.1.0')
->registerScriptSrc('mootools',		'media/script/mootools/mootools.js', '1.12')
->registerScriptSrc('moodx', 		'media/script/mootools/moodx.js')
->registerScriptSrc('tabs', 		'media/script/tabpane.js')

// Default injected Javascript - allows use of MODX-placeholders
->registerScriptFromFile('modx_jq','media/script/manager.js')

->setTypeDefaults('action.buttons',	array('tpl'=>'action.buttons'))
->setTypeDefaults('action.button',	array('tpl'=>'action.button'))
->setTypeDefaults('action.select',	array('tpl'=>'action.select'))
->setTypeDefaults('form', 			array('tpl'=>'form'))
->setTypeDefaults('input.hidden', 	array('tpl'=>'form.input.hidden'))
->setTypeDefaults('input.password', array('tpl'=>'form.input.password', 'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.text',     array('tpl'=>'form.input.password', 'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.submit', 	array('tpl'=>'form.input.submit'))
->setTypeDefaults('select.option', 	array('tpl'=>'form.select.option'))
->setTypeDefaults('message', 		array('tpl'=>'message'))
->setTypeDefaults('section', 		array('tpl'=>'form.section', 'innerTpl'=>'form.table'))
->setTypeDefaults('section.blank',	array('tpl'=>'form.section.blank', 'innerTpl'=>'form.table'))
->setTypeDefaults('tabpane', 		array('tpl'=>'tab.container', 'remember_last_tab'=>$modx->config['remember_last_tab'] == 1 ? 'true' : 'false'))
->setTypeDefaults('tab',	 		array('tpl'=>'tab.tab', 'cssFirst'=>'in active'))
->setTypeDefaults('grid',	 		array('tpl'=>'grid.1column'))
->setTypeDefaults('raw',	 		array('tpl'=>'raw'))

// Prepare main-buttons
->setButton('action.buttons', 'main', '');
;  

if(isset($_REQUEST['r'])) $this->setPlaceholder('doRefresh', 'doRefresh("'. $_REQUEST['r'] .'");');
