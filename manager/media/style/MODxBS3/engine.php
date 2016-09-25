<?php
/* Check /media/style/common/engine.php for more details */
/* Bootstrap3 Manager TemplateEngine Setup */

// Bootstrap3 CSS
$this->registerCssSrc('bootstrap', 'media/style/common/bootstrap/css/bootstrap.min.css')

// Bootstrap3 Javascript-Files
->registerScriptSrc('tabs', 		NULL) // Remove MODX Tabs for using Bootstrap Tabs
->registerScriptSrc('bootstrap',	'media/script/bootstrap/js/bootstrap.min.js')

// Bootstrap3 injected Javascript - allows use of MODX-placeholders
// ->registerScriptFromFile('modx_jq','media/script/manager.js')

->setTypeDefaults('section', 		array('tpl'=>'form.section', 'innerTpl'=>'form.table', 'collapsed'=>'in'))
->setTypeDefaults('input.password', array('tpl'=>'form.input.password', 'outerTpl'=>'form.table.row'))

;