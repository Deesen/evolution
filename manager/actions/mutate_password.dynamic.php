<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('change_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

global $_style, $_lang;

$tpl =& $modx->manager->tpl;

$tpl->setBodyGrid('fullWidth');
// $tpl->setTabs(null);
$tpl->setTitle($_lang['change_password']);

$tpl->setActionButtons(array(
	'ButtonX'=>array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.userform.save.click();', 'class'=>'transition'),
	'Button5'=>array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"],'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.location.href=\'index.php?a=2\'', 'class'=>'transition'),
));

$tpl->addBody(array(
	'message'=>array(
		'position'=>'message',
		'type'=>'message',
		'label'=>$_lang['change_password_message']
	),
	'userform'=>array(
		'position'=>'block1',
		'grid'=>'2columns',
		'type'=>'form',
		'action'=>'index.php?a=34',
		'method'=>'post',
		'inputHidden'=>array(
			0=>array('value'=>$_GET['id'])
		),
		'content'=>array(
			'section1'=>array('type'=>'section', 'label'=>$_lang['change_password']),
			'pass1'=>array('type'=>'password', 'label'=>$_lang['change_password_new'],     'position'=>'block2', 'section'=>''),
			'pass2'=>array('type'=>'password', 'label'=>$_lang['change_password_confirm'], 'position'=>'block3'),
			'section2'=>array('type'=>'section', 'label'=>$_lang['change_password']),
			'pass3'=>array('type'=>'password', 'label'=>$_lang['change_password_new'],     'position'=>'block2', 'section'=>''),
			'pass4'=>array('type'=>'password', 'label'=>$_lang['change_password_confirm'], 'position'=>'block3'),
			'save'=>array('type'=>'submit', 'displayNone'=>true),
		)
	),
));

echo $tpl->renderFullDom();
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