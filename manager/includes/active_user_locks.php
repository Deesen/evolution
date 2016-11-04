<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly."); ?>

// Lock element in session.js
function keepMeLocked() {
	var id = <?php echo $lockElementId ? intval($lockElementId) : '"new"'; ?>;
	var type = <?php echo $lockElementType ? intval($lockElementType) : '0'; ?>;
	if(window.opener) {
		// "Edit resource" in pop-up with original opener existing
		window.opener.frames['mainMenu'].lockResource(type, id);
	} else if(parent.frames['mainMenu']) {
		// "Edit resource" in main-frame
		parent.frames['mainMenu'].lockResource(type, id);
	} else {
		// "Edit resource" in pop-up with lost opener (opener-window reloaded etc)
		clearInterval(keepMeLockedInterval);
		alert('This resource (ID '+id+') is not locked anymore.');
	}
}
var keepMeLockedInterval = window.setInterval(keepMeLocked, 1000 * 1);

jQuery(window).bind('beforeunload', function(){
	var id = <?php echo $lockElementId ? intval($lockElementId) : '"new"'; ?>;
	var type = <?php echo $lockElementType ? intval($lockElementType) : '0'; ?>;
	top.mainMenu.unlockResource(type, id, true);
});