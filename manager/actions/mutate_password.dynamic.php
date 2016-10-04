<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('change_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

global $_style, $_lang;

// INITIALIZE TEMPLATE-ENGINE
$tpe =& $modx->manager->tpe;
$tpe->setActionTemplate('mutate_password.dynamic')
->setPlaceholder('title', $_lang['change_password'])

// @todo: ADD FOR ERROR MESSAGES  
// ->alert('This is an example alert / error-msg.', 'warning')

// ADD ACTION-BUTTONS
->setElement('action.button', 'actions.Button1', array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.userform.save.click();', 'class'=>'transition'))
->setElement('action.button', 'actions.Button5', array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"],'href'=>'javascript:void(0)','onclick'=>"documentDirty=false; document.location.href='index.php?a=2';", 'class'=>'transition'))

// SET FORM 
->setElement('form', 	'userform', 				array('name'=>'userform', 'action'=>'index.php?a=34', 'method'=>'post'))
->setElement('input', 	'userform.id',              array('name'=>'id', 'type'=>'hidden', 'value'=>$_GET['id']))
->setElement('section', 'userform.section1',        array('label'=>$_lang['change_password']))
->setElement('input', 	'userform.section1.pass1',  array('name'=>'pass1', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'userform.section1.pass2',  array('name'=>'pass2', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->setElement('input', 	'userform.save',            array('name'=>'save', 'type'=>'submit'))
 
;             

// NOW RENDER ELEMENTS-MATRIX
echo $tpe->renderAction();
?>