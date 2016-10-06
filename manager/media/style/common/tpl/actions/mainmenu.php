<?php

?>
<div id="tocText" [+tocTextRTL+]></div>
<div id="topbar">
	<div id="topbar-container">

		<div id="statusbar">
			<span id="buildText"></span>
			<span id="workText"></span>
		</div>

		<div id="supplementalNav">
			<span class="username">[+username+]</span>' [+change_password+]
			
			<a href="index.php?a=8" target="_top">[+lang.logout+]</a>
			[+systemversion+]
		</div>

	</div>
</div>

<form name="menuForm" action="l4mnu.php" class="clear">
	<input type="hidden" name="sessToken" id="sessTokenInput" value="[+sessTokenInput+]" />
	<div id="Navcontainer">
		<div id="divNav">
			[[mgrTpl?
				&get=`elements`
				&element=`mainmenu`
			]]
		</div>
	</div>
</form>