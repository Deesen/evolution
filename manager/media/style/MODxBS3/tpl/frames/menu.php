<?php

// Debug-Messages true/false
// $tpe->setTpeOption('debug_info', false);

// Set separate body-template
$tpe->setBodyTemplate('body.frame');

// Change template of mainmenu to tab.container
$tpe->setElementTpe('mainmenu', 'tpl', 'tab.container');

// Add extra CSS for mainmenu
$tpe->registerCssSrc('mainmenu', 'media/style/MODxBS3/mainmenu.css');
$tpe->registerCssSrc('main', NULL); // Remove main-css

?>
<form name="menuForm" action="l4mnu.php" class="clear">
	<input type="hidden" name="sessToken" id="sessTokenInput" value="[+sessTokenInput+]" />
	
	[[mgrTpl?
	&get=`elements`
	&element=`mainmenu`
	]]
	
</form>