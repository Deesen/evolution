<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('change_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

global $_style, $_lang;

$tpe =& $modx->manager->tpe;
$tpe->setActionTemplate('mutate_password.dynamic')
->setPlaceholder('title', $_lang['change_password'])
// ->alert('This is an example alert / error-msg.', 'warning')
 
->setButton('action.button', 'main.Button1', array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.userform.save.click();', 'class'=>'transition'))
->setButton('action.button', 'main.Button5', array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"],'href'=>'javascript:void(0)','onclick'=>"documentDirty=false; document.location.href='index.php?a=2';", 'class'=>'transition'))

	/*
///////////////////////////////////////////////
// Configure body, can be modified via custom action-templates

/////////////////////////////////////////////////////////////////////////////
////////////// EXAMPLE WITH TABS
->setElement('form', 'userform', 	'', 									array('name'=>'userform', 'action'=>'index.php?a=34', 'method'=>'post'))
	
->setElement('message', 'msg1',     'userform',   							array('message'=>'Just some example text'))	
	
->setElement('input', 	'id',    	'userform', 		  					array('name'=>'id', 'type'=>'hidden', 'value'=>$_GET['id']))

->setElement('tabpane',	'pane1', 	'userform', 	 	  					array('label'=>$_lang['change_password']))
->setElement('tab', 	'tab1', 	'userform.pane1', 	 	  				array('label'=>$_lang['change_password']))
->setElement('section', 'section1', 'userform.pane1.tab1',					array('label'=>$_lang['change_password']))
->setElement('input', 	'pass1', 	'userform.pane1.tab1.section1', 		array('name'=>'pass1', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'pass2', 	'userform.pane1.tab1.section1', 		array('name'=>'pass2', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))

->setElement('tab', 	'tab2', 	'userform.pane1', 	 	  				array('label'=>$_lang['change_password'].' 2'))
->setElement('grid',    'grid2_1',  'userform.pane1.tab2', 	  				array()									   	, array('tpl'=>'grid.2columns'))
->setElement('section', 'section2', 'userform.pane1.tab2.grid2_1',			array('label'=>$_lang['change_password']  )	, array('pos'=>'block1'))
->setElement('input', 	'pass3', 	'userform.pane1.tab2.grid2_1.section2',	array('name'=>'pass3', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'pass4', 	'userform.pane1.tab2.grid2_1.section2',	array('name'=>'pass4', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->setElement('section', 'section3', 'userform.pane1.tab2.grid2_1', 	  		array('label'=>$_lang['change_password'].' 2'  )	, array('pos'=>'block2'))
->setElement('input', 	'pass5', 	'userform.pane1.tab2.grid2_1.section3', array('name'=>'pass5', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'pass6', 	'userform.pane1.tab2.grid2_1.section3', array('name'=>'pass6', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->setElement('section', 'section4', 'userform.pane1.tab2.grid2_1', 	  		array('label'=>$_lang['change_password'].' 3'  )	, array('pos'=>'block2'))
->setElement('input', 	'pass7', 	'userform.pane1.tab2.grid2_1.section4', array('name'=>'pass7', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'pass8', 	'userform.pane1.tab2.grid2_1.section4', array('name'=>'pass8', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))

->setElement('input', 	'save', 	'userform', 							array('name'=>'save', 'type'=>'submit'))

// And some example text
->setElement('message', 'msg2',      'userform',   			array('message'=>'And again just some example text'))
/////////////////////////////////////////////////////////////////////////////
*/



///////////////////////////////////////////////////////////////////////////// 
////////////// ORIGINAL FORM 
                                             
->setActionTemplate('mutate_password.dynamic')
->setPlaceholder('title', $_lang['change_password'])

->setElement('form', 	'userform', 				array('name'=>'userform', 'action'=>'index.php?a=34', 'method'=>'post'))
->setElement('input', 	'userform.id',              array('name'=>'id', 'type'=>'hidden', 'value'=>$_GET['id']))
->setElement('section', 'userform.section1',        array('label'=>$_lang['change_password']))
->setElement('input', 	'userform.section1.pass1',  array('name'=>'pass1', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->setElement('input', 	'userform.section1.pass2',  array('name'=>'pass2', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->setElement('input', 	'userform.save',            array('name'=>'save', 'type'=>'submit'))
 
;             

echo $tpe->renderAction();
?>