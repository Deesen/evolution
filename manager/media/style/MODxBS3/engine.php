<?php
/* Check /media/style/common/engine.php for more details */
/* Bootstrap3 Manager TemplateEngine Setup */

// Bootstrap3 CSS

$this->registerCssSrc('main',           'media/style/common/bootstrap/css/bootstrap.min.css')
->registerCssSrc('font_awesome',        'media/style/common/font-awesome/css/font-awesome.min.css')
->registerCssSrc('awesome_checkbox',    'media/style/MODxBS3/css/awesome-bootstrap-checkbox.css')
->registerCssSrc('theme',               'media/style/MODxBS3/theme.css')

// Bootstrap3 Javascript-Files
->registerHeadScriptSrc('tabs', 		NULL) // Remove MODX Tabs for using Bootstrap Tabs
->registerFooterScriptSrc('bootstrap',	'media/script/bootstrap/js/bootstrap.min.js')
->registerFooterScriptSrc('theme',	    'media/style/MODxBS3/js/theme.js')

// Bootstrap3 injected Javascript - allows use of MODX-placeholders
// ->registerHeadScriptFromFile('modx_jq','media/script/manager.js')

->setTypeDefaults('section', 		    array('tpl'=>'form.section', 'innerTpl'=>'form.table', 'collapsed'=>'in'))
->setTypeDefaults('section.blank',	    array('tpl'=>'form.section', 'innerTpl'=>'form.table', 'collapsed'=>'in'))

;