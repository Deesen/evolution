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

	<div id="floater">
		<form name="sortFrm" id="sortFrm" action="menu.php">
			<input type="hidden" name="dt" value="<?php echo htmlspecialchars($_REQUEST['dt']); ?>" />
			<table width="100%"  border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="padding-left: 10px;padding-top: 1px;" colspan="2">
						<select name="sortby">
							<option value="isfolder" <?php echo $_SESSION['tree_sortby']=='isfolder' ? "selected='selected'" : "" ?>><?php echo $_lang['folder']; ?></option>
							<option value="pagetitle" <?php echo $_SESSION['tree_sortby']=='pagetitle' ? "selected='selected'" : "" ?>><?php echo $_lang['pagetitle']; ?></option>
							<option value="longtitle" <?php echo $_SESSION['tree_sortby']=='longtitle' ? "selected='selected'" : "" ?>><?php echo $_lang['long_title']; ?></option>
							<option value="id" <?php echo $_SESSION['tree_sortby']=='id' ? "selected='selected'" : "" ?>><?php echo $_lang['id']; ?></option>
							<option value="menuindex" <?php echo $_SESSION['tree_sortby']=='menuindex' ? "selected='selected'" : "" ?>><?php echo $_lang['resource_opt_menu_index'] ?></option>
							<option value="createdon" <?php echo $_SESSION['tree_sortby']=='createdon' ? "selected='selected'" : "" ?>><?php echo $_lang['createdon']; ?></option>
							<option value="editedon" <?php echo $_SESSION['tree_sortby']=='editedon' ? "selected='selected'" : "" ?>><?php echo $_lang['editedon']; ?></option>
							<option value="publishedon" <?php echo $_SESSION['tree_sortby']=='publishedon' ? "selected='selected'" : "" ?>><?php echo $_lang['page_data_publishdate']; ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td width="99%" style="padding-left: 10px;padding-top: 1px;">
						<select name="sortdir">
							<option value="DESC" <?php echo $_SESSION['tree_sortdir']=='DESC' ? "selected='selected'" : "" ?>><?php echo $_lang['sort_desc']; ?></option>
							<option value="ASC" <?php echo $_SESSION['tree_sortdir']=='ASC' ? "selected='selected'" : "" ?>><?php echo $_lang['sort_asc']; ?></option>
						</select>
					</td>
					<td width="1%"><ul class="actionButtons"><li><a href="#" class="treeButton" id="button7" style="text-align:right" onClick="updateTree();showSorter();" title="<?php echo $_lang['sort_tree']; ?>"><?php echo $_lang['sort_tree']; ?></a></li></ul></td>
				</tr>
				<tr>
					<td width="99%" style="padding-left: 10px;padding-top: 1px;" colspan="2">
						<br/>
						<?php echo $_lang["setting_resource_tree_node_name"] ?>
						<select name="nodename" style="margin-top:5px;">
							<option value="default" <?php echo $_SESSION['tree_nodename']=='default' ? "selected='selected'" : "" ?>><?php echo trim($_lang['default'], ':'); ?></option>
							<option value="pagetitle" <?php echo $_SESSION['tree_nodename']=='pagetitle' ? "selected='selected'" : "" ?>><?php echo $_lang['pagetitle']; ?></option>
							<option value="longtitle" <?php echo $_SESSION['tree_nodename']=='longtitle' ? "selected='selected'" : "" ?>><?php echo $_lang['long_title']; ?></option>
							<option value="menutitle" <?php echo $_SESSION['tree_nodename']=='menutitle' ? "selected='selected'" : "" ?>><?php echo $_lang['resource_opt_menu_title']; ?></option>
							<option value="alias" <?php echo $_SESSION['tree_nodename']=='alias' ? "selected='selected'" : "" ?>><?php echo $_lang['alias']; ?></option>
							<option value="createdon" <?php echo $_SESSION['tree_nodename']=='createdon' ? "selected='selected'" : "" ?>><?php echo $_lang['createdon']; ?></option>
							<option value="editedon" <?php echo $_SESSION['tree_nodename']=='editedon' ? "selected='selected'" : "" ?>><?php echo $_lang['editedon']; ?></option>
							<option value="publishedon" <?php echo $_SESSION['tree_nodename']=='publishedon' ? "selected='selected'" : "" ?>><?php echo $_lang['page_data_publishdate']; ?></option>
						</select>
					</td>
				</tr>
			</table>
		</form>
	</div>

	<div id="treeHolder">
		<?php
		// invoke OnTreeRender event
		$evtOut = $modx->invokeEvent('OnManagerTreePrerender', $modx->db->escape($_REQUEST));
		if (is_array($evtOut))
			echo implode("\n", $evtOut);
		?>
		<div><?php echo $_style['tree_showtree']; ?>&nbsp;<span class="rootNode" onClick="treeAction(0, '<?php $site_name = htmlspecialchars($site_name,ENT_QUOTES,$modx->config['modx_charset']); echo $site_name; ?>');"><b><?php echo $site_name; ?></b></span><div id="treeRoot"></div></div>
		<?php
		// invoke OnTreeRender event
		$evtOut = $modx->invokeEvent('OnManagerTreeRender', $modx->db->escape($_REQUEST));
		if (is_array($evtOut))
			echo implode("\n", $evtOut);
		?>
	</div>

	<script type="text/javascript">
		// Set 'treeNodeSelected' class on document node when editing via Context Menu
		function setActiveFromContextMenu( doc_id ){
			$$('.treeNodeSelected').removeClass('treeNodeSelected');
			$$('#node'+doc_id+' span')[0].className='treeNodeSelected';
		}

		// Context menu stuff
		function menuHandler(action) {
			switch (action) {
				case 1 : // view
					setActiveFromContextMenu( itemToChange );
					top.main.document.location.href="index.php?a=3&id=" + itemToChange;
					break;
				case 2 : // edit
					setActiveFromContextMenu( itemToChange );
					top.main.document.location.href="index.php?a=27&id=" + itemToChange;
					break;
				case 3 : // new Resource
					top.main.document.location.href="index.php?a=4&pid=" + itemToChange;
					break;
				case 4 : // delete
					if(selectedObjectDeleted==0) {
						if(confirm("'" + selectedObjectName + "'\n\n<?php echo $_lang['confirm_delete_resource']; ?>")==true) {
							top.main.document.location.href="index.php?a=6&id=" + itemToChange;
						}
					} else {
						alert("'" + selectedObjectName + "' <?php echo $_lang['already_deleted']; ?>");
					}
					break;
				case 5 : // move
					top.main.document.location.href="index.php?a=51&id=" + itemToChange;
					break;
				case 6 : // new Weblink
					top.main.document.location.href="index.php?a=72&pid=" + itemToChange;
					break;
				case 7 : // duplicate
					if(confirm("<?php echo $_lang['confirm_resource_duplicate'] ?>")==true) {
						top.main.document.location.href="index.php?a=94&id=" + itemToChange;
					}
					break;
				case 8 : // undelete
					if(selectedObjectDeleted==0) {
						alert("'" + selectedObjectName + "' <?php echo $_lang['not_deleted']; ?>");
					} else {
						if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_undelete']; ?>")==true) {
							top.main.document.location.href="index.php?a=63&id=" + itemToChange;
						}
					}
					break;
				case 9 : // publish
					if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_publish']; ?>")==true) {
						top.main.document.location.href="index.php?a=61&id=" + itemToChange;
					}
					break;
				case 10 : // unpublish
					if (itemToChange != <?php echo $modx->config['site_start']?>) {
						if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_unpublish']; ?>")==true) {
							top.main.document.location.href="index.php?a=62&id=" + itemToChange;
						}
					} else {
						alert('Document is linked to site_start variable and cannot be unpublished!');
					}
					break;
				case 11 : // sort menu index
					top.main.document.location.href="index.php?a=56&id=" + itemToChange;
					break;
				case 12 : // preview	
					window.open(selectedObjectUrl,'previeWin'); //re-use 'new' window
					break;

				default :
					alert('Unknown operation command.');
			}
		}

	</script>

	<!-- Contextual Menu Popup Code -->
	<div id="mx_contextmenu" onselectstart="return false;">
		<div id="nameHolder">&nbsp;</div>
		<?php
		constructLink(3, $_style["ctx_new_document"], $_lang["create_resource_here"], $modx->hasPermission('new_document')); // new Resource
		constructLink(2, $_style["ctx_edit_document"], $_lang["edit_resource"], $modx->hasPermission('edit_document')); // edit
		constructLink(5, $_style["ctx_move_document"] , $_lang["move_resource"], $modx->hasPermission('save_document')); // move
		constructLink(7, $_style["ctx_resource_duplicate"], $_lang["resource_duplicate"], $modx->hasPermission('new_document')); // duplicate
		constructLink(11,$_style["ctx_sort_menuindex"], $_lang["sort_menuindex"], $modx->hasPermission('edit_document')); // sort menu index
		?>
		<div class="seperator"></div>
		<?php
		constructLink(9, $_style["ctx_publish_document"], $_lang["publish_resource"], $modx->hasPermission('publish_document')); // publish
		constructLink(10, $_style["ctx_unpublish_resource"], $_lang["unpublish_resource"], $modx->hasPermission('publish_document')); // unpublish
		constructLink(4, $_style["ctx_delete"], $_lang["delete_resource"], $modx->hasPermission('delete_document')); // delete
		constructLink(8, $_style["ctx_undelete_resource"], $_lang["undelete_resource"], $modx->hasPermission('delete_document')); // undelete
		?>
		<div class="seperator"></div>
		<?php
		constructLink(6, $_style["ctx_weblink"], $_lang["create_weblink_here"], $modx->hasPermission('new_document')); // new Weblink
		?>
		<div class="seperator"></div>
		<?php
		constructLink(1, $_style["ctx_resource_overview"], $_lang["resource_overview"], $modx->hasPermission('view_document')); // view
		constructLink(12, $_style["ctx_preview_resource"], $_lang["preview_resource"], 1); // preview
		?>
	</div>
</div>
