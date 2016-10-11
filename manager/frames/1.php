<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
header("X-XSS-Protection: 0");
$_SESSION['browser'] = (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 1')!==false) ? 'legacy_IE' : 'modern';
$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';
if(!isset($modx->config['manager_menu_height'])) $modx->config['manager_menu_height'] = '70';
if(!isset($modx->config['manager_tree_width']))  $modx->config['manager_tree_width']  = '320';

$tpe->resetRegistered('all'); // Reset all registered CSS & JS - Resources (originally set in engine.php)
$tpe->setBodyTemplate('body.frame'); // Frames have their own body-tpl

$tpe->setPlaceholder('title', $site_name.' - (MODX CMS Manager)');

// Prepare iFrames
$tpe->setPlaceholder('iframe_mainMenu', '<iframe name="mainMenu" src="index.php?a=1&amp;f=menu" scrolling="no" frameborder="0" noresize="noresize"></iframe>');
$tpe->setPlaceholder('iframe_tree', '<iframe name="tree" src="index.php?a=1&amp;f=tree" scrolling="no" frameborder="0" onresize="mainMenu.resizeTree();"></iframe>');
$tpe->setPlaceholder('iframe_main', '<iframe name="main" id="mainframe" src="index.php?a=2" scrolling="auto" frameborder="0" onload="if (mainMenu.stopWork()) mainMenu.stopWork(); scrollWork();"></iframe>');