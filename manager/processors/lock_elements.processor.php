<?php

if($_REQUEST['tok'] == md5(session_id())) {
	$ok             = true;
	$lockedResource = $modx->isJson($_POST['lockedResources'], true);

	if (is_array($lockedResource)) {
		foreach ($lockedResource as $type => $resPerType) {
			foreach ($resPerType as $id => $time) {
				if(is_null($time)) $modx->unlockElement($type, $id);
				else $modx->lockElement($type, $id);
			}
		}
	}
}

header('Content-type: application/json');
if($ok) {
	echo '{status:"ok"}';
} else {
	echo '{status:"null"}';
}