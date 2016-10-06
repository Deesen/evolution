<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if (!array_key_exists('mail_check_timeperiod', $modx->config) || !is_numeric($modx->config['mail_check_timeperiod'])) {
	$modx->config['mail_check_timeperiod'] = 5;
}

// Prepare template-engine
$tpe =& $modx->manager->tpe;

$tpe->setActionTemplate('mainmenu')
->setPlaceholder('tocTextRTL', $modx_textdir ? ' class="tocTextRTL"' : '')
->setPlaceholder('username', $modx->getLoginUserName())
->setPlaceholder('change_password', ($modx->hasPermission('change_password') ? ' <a onclick="this.blur();" href="index.php?a=28" target="main">'.$_lang['change_password'].'</a>'."\n" : "\n"))
->setPlaceholder('body_id', 'topMenu');

$style = $modx->config['settings_version']!=$modx->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
$tpe->setPlaceholder('systemversion', sprintf('<span onclick="top.main.document.location.href=\'index.php?a=9#version_notices\'" style="cursor:pointer" class="systemversion" title="%s &ndash; %s" %s>%s</span>&nbsp;',$site_name,$modx->getVersionData('full_appname'),$style,$modx->config['settings_version']))
->setPlaceholder('sessTokenInput', md5(session_id()))

;

// REMOVE UNNESSECARY DEFAULT STUFF SET IN engine.php
$tpe->registerHeadScriptSrc('tabs', NULL);
$tpe->registerHeadScriptFromFile('modx_jq', NULL);
$tpe->setPlaceholder('preloader', ''); // Without 'modx_jq' not working 

// Add required Javascript
$tpe->registerHeadScriptSrc('session', 'media/script/session.js');

// Prepare Javascript-Object for transferring parameters
$modx_params = json_encode(array(
	// modx.lang.confirm_remove_locks
	'lang'=>array(
		'show_tree'=>$_lang["show_tree"],
		'loading_doc_tree'=>$_lang["loading_doc_tree"],
		'loading_menu'=>$_lang["loading_menu"],
		'working'=>$_lang["working"],
		'confirm_remove_locks'=>$_lang["confirm_remove_locks"],
	),
	// modx.style.icons_working
	'style'=>array(
		'show_tree'=>$_style["show_tree"],
		'icons_loading_doc_tree'=>$_style["icons_loading_doc_tree"],
		'icons_working'=>$_style["icons_working"],
	),
	// modx.param.MGR_DIR
	'param'=>array(
		'defaultFrameWidth'=>!$modx_textdir ? '260,*' : '*,260',
		'userDefinedFrameWidth'=>!$modx_textdir ? '260,*' : '*,260',
		'mail_check_timeperiod'=>$modx->config['mail_check_timeperiod'] * 1000,
		'MGR_DIR'=>MGR_DIR,
	)
));

$reloadmenu = $manager_layout == 0 ? '
	var elm = $("buildText");
            if (elm) {
            elm.innerHTML = "&nbsp;&nbsp;<img src=\'"+ modx.style.icons_working +"\' width=\'16\' height=\'16\' />&nbsp;"+modx.lang.loading_menu;
            elm.style.display = \'block\';
        }
            parent.mainMenu.location.reload();
' : '';

$tpe->registerHeadScriptFromFile('mainmenu', 'media/script/mainmenu.js', array('modx_params'=>$modx_params, 'reloadmenu'=>$reloadmenu));

// Now build mainmenu
include('mainmenu.php');

echo $modx->manager->tpe->renderAction();
?>