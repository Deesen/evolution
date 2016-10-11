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
	
	[+resource_tree+]
</div>
