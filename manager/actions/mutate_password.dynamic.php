<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('change_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

global $_style, $_lang;

$tpe =& $modx->manager->tpl;

$tpe->setActionTemplate('mutate_password.dynamic')
	->setTitle($_lang['change_password'])
	
	// ->alert('test')
	// ->alert('test222', 'error')

	->setActionButtons(array(
		'Button1'=>array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.userform.save.click();', 'class'=>'transition'),
		'Button5'=>array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"],'href'=>'javascript:void(0)','onclick'=>"documentDirty=false; document.location.href='index.php?a=2'", 'class'=>'transition'),
	))

	->addForm('userform', 'index.php?a=34', 'post')
	
	->addTab('userform', 'general', 'general label', '2columns')
	->addTab('userform', 'secondary', 'secondary label', '1column')
	
	->addSection('userform', 'first', $_lang['change_password'],       '1column')
	->addSection('userform', 'second', $_lang['change_password'].' 2', '2columns')
	
	->addFormField('userform','id',		'hidden',	$_GET['id'])
	->addFormField('userform','save',	'submit')
	
	->addFormField('userform','pass1',	'password',	'', $_lang['change_password_new'],     array('order'=>1, 'tab'=>'general','section'=>'general','position'=>'block1'))
	->addFormField('userform','pass2',	'password',	'', $_lang['change_password_confirm'], array('order'=>2, 'tab'=>'general','section'=>'general','position'=>'block1'))
	
	// TEST
	->addFormField('userform','pass3',	'password',	'', $_lang['change_password_new'],     array('order'=>1, 'tab'=>'general','section'=>'general','position'=>'block1'))
	->addFormField('userform','pass4',	'password',	'', $_lang['change_password_confirm'], array('order'=>2, 'tab'=>'general','section'=>'general','position'=>'block1'))
	->addFormField('userform','pass5',	'password',	'', $_lang['change_password_new'],     array('order'=>3, 'tab'=>'general','section'=>'general','position'=>'block2'))
	->addFormField('userform','pass6',	'password',	'', $_lang['change_password_confirm'], array('order'=>4, 'tab'=>'general','section'=>'general','position'=>'block2'))

;

echo $tpe->renderFullDom();
?>

<!--
<hr/>

<h1><?php echo $_lang['change_password']?></h1>
<div id="actions">
	<ul class="actionButtons">
		<li class="transition"><a href="javascript:void(0)" onclick="documentDirty=false; document.userform.save.click();"><img src="<?php echo $_style["icons_save"]?>" /> <?php echo $_lang['save']?></a></li>
		<li id="Button5" class="transition"><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=2';"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']?></a></li>
	</ul>
</div>
<div class="section">
<div class="sectionHeader"><?php echo $_lang['change_password']?></div>
<div class="sectionBody">
	<form action="index.php?a=34" method="post" name="userform">
	<input type="hidden" name="id" value="<?php echo $_GET['id']?>" />

	<p><?php echo $_lang['change_password_message']?></p>

	<table border="0" cellspacing="0" cellpadding="4">
	<tr>
		<td><?php echo $_lang['change_password_new']?>:</td>
		<td>&nbsp;</td>
		<td><input type="password" name="pass1" class="inputBox" style="width:150px" value=""></td>
	</tr><tr>
		<td><?php echo $_lang['change_password_confirm']?>:</td>
		<td>&nbsp;</td>
		<td><input type="password" name="pass2" class="inputBox" style="width:150px" value=""></td>
	</tr>
	</table>

	<input type="submit" name="save" style="display:none">
</form>
</div>
</div>
-->