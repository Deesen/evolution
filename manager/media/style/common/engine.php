<?php
/* Default Manager TemplateEngine Setup */

// Default CSS
$this->registerCssSrc('main', 'media/style/[(manager_theme)]/style.css');

// Default Javascript
$this->registerScriptSrc('jquery', '[(mgr_jquery_path)]', '3.1.0');
$this->registerScriptSrc('mootools', 'media/script/mootools/mootools.js', '1.12');
$this->registerScriptSrc('moodx', 'media/script/mootools/moodx.js');

$this->registerScriptFromFile('modx_jq', 'media/script/manager.js');

if(isset($_REQUEST['r'])) $this->setPlaceholder('doRefresh', 'doRefresh("'. $_REQUEST['r'] .'");');
