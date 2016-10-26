<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('remove_locks')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

if(!isset($_GET['id'])) {
	// Remove all locks
	$modx->db->truncate($modx->getFullTableName('active_users'));

	$header = "Location: index.php?a=7";
	header($header);
} else {
	// Ajax: Handle single-ID unlock requests
	$id = intval($_GET['id']);
	if($id) {
		if($modx->db->delete($modx->getFullTableName('active_users'), "id='{$id}' AND action='27'")) echo '1';
		else echo 'Unknown error occured';
		exit;
	}
}
?>