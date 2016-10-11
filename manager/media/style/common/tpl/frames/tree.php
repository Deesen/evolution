<?php
if(IN_MANAGER_MODE!='true') die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.');

?>
[+OnManagerTreeInit+]

<div class="treeframebody">
	<div id="treeSplitter"></div>

	<table id="treeMenu" width="100%"  border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" border="0">
					<tr>
						[[mgrTpl?
						&get=`elements`
						&element=`treemenu`
						]]
					</tr>
				</table>
			</td>
		</tr>
	</table>

	[+resource_tree+]
</div>
