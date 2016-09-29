<?php
/* Default Manager TemplateEngine Setup */

// Default CSS
$this->registerCssSrc('main', 'media/style/[(manager_theme)]/style.css')

// Default Javascript-Files
->registerScriptSrc('jquery', 		'[(mgr_jquery_path)]', '3.1.0')
->registerScriptSrc('mootools',		'media/script/mootools/mootools.js', '1.12')
->registerScriptSrc('moodx', 		'media/script/mootools/moodx.js')
->registerScriptSrc('tabs', 		'media/script/tabpane.js')

// Default injected Javascript - allows use of MODX-placeholders
->registerScriptFromFile('modx_jq','media/script/manager.js')

->setTypeDefaults('action.buttons',	    array('tpl'=>'action.buttons'))
->setTypeDefaults('action.button',	    array('tpl'=>'action.button'))
->setTypeDefaults('action.select',	    array('tpl'=>'action.select'))
->setTypeDefaults('form', 			    array('tpl'=>'form'))
->setTypeDefaults('form.splitter',	    array('tpl'=>'form.splitter',           'outerTpl'=>'form.table.row.colspan'))
->setTypeDefaults('form.message',	    array('tpl'=>'form.message',            'outerTpl'=>'form.table.row.colspan'))
->setTypeDefaults('form.raw',	 		array('tpl'=>'raw',                     'outerTpl'=>'form.table.row.colspan'))
->setTypeDefaults('input.hidden', 	    array('tpl'=>'form.input.hidden'))
->setTypeDefaults('input.submit', 	    array('tpl'=>'form.input.submit'))
->setTypeDefaults('input.button',       array('tpl'=>'form.input.button',       'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.password',     array('tpl'=>'form.input.password',     'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.text',         array('tpl'=>'form.input.text',         'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.number',       array('tpl'=>'form.input.number',       'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.textarea',     array('tpl'=>'form.input.textarea',     'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.select',       array('tpl'=>'form.input.select',       'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.checkbox',     array('tpl'=>'form.input.checkbox',     'outerTpl'=>'form.table.row', 'class'=>'checkbox'))
->setTypeDefaults('input.date',         array('tpl'=>'form.input.date',         'outerTpl'=>'form.table.row'))
->setTypeDefaults('input.templatevar',  array('tpl'=>'form.input.templatevar',  'outerTpl'=>'form.table.row.templatevar'))
->setTypeDefaults('select.option', 	    array('tpl'=>'form.select.option'))
->setTypeDefaults('select.optgroup',    array('tpl'=>'form.select.optgroup'))
->setTypeDefaults('message', 		    array('tpl'=>'message'))
->setTypeDefaults('section', 		    array('tpl'=>'form.section', 'innerTpl'=>'form.table'))
->setTypeDefaults('section.blank',	    array('tpl'=>'form.section.blank', 'innerTpl'=>'form.table'))
->setTypeDefaults('tabpane', 		    array('tpl'=>'tab.container', 'remember_last_tab'=>$modx->config['remember_last_tab'] == 1 ? 'true' : 'false'))
->setTypeDefaults('tab',	 		    array('tpl'=>'tab.tab', 'cssFirst'=>'in active'))
->setTypeDefaults('grid.1col', 		    array('tpl'=>'grid.1column'))
->setTypeDefaults('grid.2col', 		    array('tpl'=>'grid.2columns'))
->setTypeDefaults('grid.3col', 		    array('tpl'=>'grid.3columns'))
->setTypeDefaults('raw',	 		    array('tpl'=>'raw'))

// Prepare main-buttons
->setButton('action.buttons', 'main', '');
;  

if(isset($_REQUEST['r'])) $this->setPlaceholder('doRefresh', 'doRefresh("'. $_REQUEST['r'] .'");');
