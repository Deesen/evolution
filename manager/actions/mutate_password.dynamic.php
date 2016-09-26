<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('change_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

global $_style, $_lang;

$tpe =& $modx->manager->tpl;


///////////////////////////////////////////////
// Required settings for Template-engine
$tpe->setActionTemplate('mutate_password.dynamic')

///////////////////////////////////////////////
// Custom placeholders
->setPlaceholder('title', $_lang['change_password'])

///////////////////////////////////////////////
// Set alerts
->alert('This is an example alert / error-msg.', 'warning')

///////////////////////////////////////////////
// Set Action Buttons in category main 
->setButton('main', 'Button1', array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.userform.save.click();', 'class'=>'transition'))
->setButton('main', 'Button5', array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"],'href'=>'javascript:void(0)','onclick'=>"documentDirty=false; document.location.href='index.php?a=2';"))
// Modify Action Buttons (also possible via custom-templates)
->addButtonParam('main', 'Button5', 'onclick', "alert('true')")	// Add
->setButtonParam('main', 'Button5', 'class', 'transition')	// Set

///////////////////////////////////////////////
// Configure body, can be modified via templates



/////////////////////////////////////////////////////////////////////////////
////////////// EXAMPLE WITH TABS
->createBodyElement('form', 'userform', array('name'=>'userform', 'action'=>'index.php?a=34', 'method'=>'post'))
	
->addElement('message', 'msg1',     'userform',   							array('message'=>'Just some example text'))	
	
->addElement('input', 	'id',    	'userform', 		  					array('name'=>'id', 'type'=>'hidden', 'value'=>$_GET['id']))

->addElement('tabpane',	'pane1', 	'userform', 	 	  					array('label'=>$_lang['change_password']))
->addElement('tab', 	'tab1', 	'userform.pane1', 	 	  				array('label'=>$_lang['change_password']))
->addElement('section', 'section1', 'userform.pane1.tab1',					array('label'=>$_lang['change_password']))
->addElement('input', 	'pass1', 	'userform.pane1.tab1.section1', 		array('name'=>'pass1', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->addElement('input', 	'pass2', 	'userform.pane1.tab1.section1', 		array('name'=>'pass2', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))

->addElement('tab', 	'tab2', 	'userform.pane1', 	 	  				array('label'=>$_lang['change_password'].' 2'))
->addElement('grid',    'grid2_1',  'userform.pane1.tab2', 	  				array()									   	, array('tpl'=>'grid.2columns'))
->addElement('section', 'section2', 'userform.pane1.tab2.grid2_1',			array('label'=>$_lang['change_password']  )	, array('pos'=>'block1'))
->addElement('input', 	'pass3', 	'userform.pane1.tab2.grid2_1.section2',	array('name'=>'pass3', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->addElement('input', 	'pass4', 	'userform.pane1.tab2.grid2_1.section2',	array('name'=>'pass4', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->addElement('section', 'section3', 'userform.pane1.tab2.grid2_1', 	  		array('label'=>$_lang['change_password'].' 2'  )	, array('pos'=>'block2'))
->addElement('input', 	'pass5', 	'userform.pane1.tab2.grid2_1.section3', array('name'=>'pass5', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->addElement('input', 	'pass6', 	'userform.pane1.tab2.grid2_1.section3', array('name'=>'pass6', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
->addElement('section', 'section4', 'userform.pane1.tab2.grid2_1', 	  		array('label'=>$_lang['change_password'].' 3'  )	, array('pos'=>'block2'))
->addElement('input', 	'pass7', 	'userform.pane1.tab2.grid2_1.section4', array('name'=>'pass7', 'type'=>'password', 'label'=>$_lang['change_password_new']))
->addElement('input', 	'pass8', 	'userform.pane1.tab2.grid2_1.section4', array('name'=>'pass8', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))

->addElement('input', 	'save', 	'userform', 							array('name'=>'save', 'type'=>'submit'))

// And some example text
->addElement('message', 'msg2',      'userform',   			array('message'=>'And again just some example text'))
/////////////////////////////////////////////////////////////////////////////



/*
///////////////////////////////////////////////////////////////////////////// 
////////////// ORIGINAL FORM 
                                             
	->setActionTemplate('mutate_password.dynamic')
	->setPlaceholder('title', $_lang['change_password'])

	->createBodyElement('form', 'userform', array('name'=>'userform', 'action'=>'index.php?a=34', 'method'=>'post'))

	->addElement('input', 	'id',    	'userform', 		  	array('name'=>'id', 'type'=>'hidden', 'value'=>$_GET['id']))
	->addElement('section', 'section1', 'userform',				array('label'=>$_lang['change_password']))
	->addElement('message', 'msg1',     'userform.section1',	array('message'=>$_lang['change_password_message']))
	->addElement('input', 	'pass1', 	'userform.section1', 	array('name'=>'pass1', 'type'=>'password', 'label'=>$_lang['change_password_new']))
	->addElement('input', 	'pass2', 	'userform.section1', 	array('name'=>'pass2', 'type'=>'password', 'label'=>$_lang['change_password_confirm']))
	->addElement('input', 	'save', 	'userform', 			array('name'=>'save', 'type'=>'submit'))
*/
 
;             

echo $tpe->renderFullDom();
?>