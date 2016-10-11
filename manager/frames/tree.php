<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
$modx->config['mgr_jquery_path'] = 'media/script/jquery/jquery.min.js';

    $modx_textdir = isset($modx_textdir) ? $modx_textdir : null;
    function constructLink($action, $img, $text, $allowed) {
        if($allowed==1) { ?>
            <div class="menuLink" onclick="menuHandler(<?php echo $action ; ?>); hideMenu();">
        <?php } else { ?>
            <div class="menuLinkDisabled">
        <?php } ?>
                <i class="<?php echo $img; ?>"></i> <?php echo $text; ?>
            </div>
        <?php
    }
    $mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';
        
    // Template engine
    $tpe->setBodyTemplate('body.frame'); // Frames have their own body-tpl
    $tpe->setPlaceholder('body_onclick', 'hideMenu(1);');
    
    $tpe->registerHeadScriptSrc('modx_jq', NULL); // REMOVE SCRIPT
    
    // Prepare openedArray
    $openedArray = "var openedArray = new Array();\n";
    if (isset($_SESSION['openedArray'])) {
        $opened = array_filter(array_map('intval', explode('|', $_SESSION['openedArray'])));
        foreach ($opened as $item) {
	        $openedArray .= sprintf("openedArray[%d] = 1;\n", $item);
        }
    }
        
    // Prepare Javascript-Object for transferring parameters
    $modx_params = json_encode(array(
        'lang'=>array(
	        'loading_doc_tree'=>$_lang["loading_doc_tree"],
	        'confirm_empty_trash'=>$_lang["confirm_empty_trash"],
	        'unable_set_parent'=>$_lang["unable_set_parent"],
	        'unable_set_link'=>$_lang["unable_set_link"],
	        'empty_recycle_bin'=>addslashes($_lang["unable_set_link"]),
	        'empty_recycle_bin_empty'=>$_lang["empty_recycle_bin_empty"],
        ),
        'style'=>array(
	        'tree_page'=>$_style["tree_page"],
	        'tree_globe'=>isset($_style["tree_globe"]) ? $_style["tree_globe"] : $_style["tree_page"],
	        'tree_minusnode'=>$_style["tree_minusnode"],
	        'tree_plusnode'=>$_style["tree_plusnode"],
	        'tree_folderopen'=>isset($_style["tree_folderopen"]) ? $_style["tree_folderopen"] : $_style["tree_page"],
	        'tree_folderopen_secure'=>$_style["tree_folderopen_secure"],
	        'tree_folder'=>isset($_style["tree_folder"]) ? $_style["tree_folder"] : $_style["tree_page"],
	        'empty_recycle_bin'=>$_style["empty_recycle_bin"],
	        'empty_recycle_bin_empty'=>$_style["empty_recycle_bin_empty"],
        ),
        'param'=>array(
	        'openedArray'=>$openedArray,
	        'contextOffset'=>$modx_textdir ? '-190' : '',
	        'tree_page_click'=>(!empty($modx->config['tree_page_click']) ? $modx->config['tree_page_click'] : '27'),
        )
    ));
    $tpe->registerHeadScriptFromFile('tree', 'media/script/tree.js', array('modx_params'=>$modx_params, 'openedArray'=>$openedArray));

    // Prepare "last user settings"
	$lastSettingsKeys = array('tree_sortby','tree_sortdir','tree_nodename');
	foreach($lastSettingsKeys as $param) {
		if(isset($_REQUEST[$param])) {
			// Set new keys
			$modx->manager->saveLastUserSetting($param, $_REQUEST[$param]);
			$_SESSION[$param] = $_REQUEST[$param];
		} else if(!isset($_SESSION[$param])) {
			// Load keys into session
			$_SESSION[$param] = $modx->manager->getLastUserSetting($param);
		}
	}
        
    // invoke OnTreePrerender event
    $evtOut = $modx->invokeEvent('OnManagerTreeInit',$_REQUEST);
    if (is_array($evtOut)) $tpe->setPlaceholder('OnManagerTreeInit', implode("\n", $evtOut));
        
    // Prepare treemenu-buttons
	$tpe->setElement('root', 'treemenu', array(), array());

    $tpe->setElement('treemenu.button', 'treemenu.Button1', array('title'=>$_lang["expand_tree"], 'label'=>$_style['expand_tree'], 'onclick'=>'expandTree();', 'href'=>'#'), array('class'=>'treeButton'));
    $tpe->setElement('treemenu.button', 'treemenu.Button2', array('title'=>$_lang["collapse_tree"], 'label'=>$_style['collapse_tree'], 'onclick'=>'collapseTree();', 'href'=>'#'), array('class'=>'treeButton'));
	if ($modx->hasPermission('new_document')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button3a', array('title'=>$_lang["add_resource"], 'label'=>$_style['add_doc_tree'], 'onclick'=>"top.main.document.location.href='index.php?a=4';", 'href'=>'#'), array('class'=>'treeButton'));
		$tpe->setElement('treemenu.button', 'treemenu.Button3c', array('title'=>$_lang["add_weblink"], 'label'=>$_style['add_weblink_tree'], 'onclick'=>"top.main.document.location.href='index.php?a=72';", 'href'=>'#'), array('class'=>'treeButton'));
	}
	$tpe->setElement('treemenu.button', 'treemenu.Button4', array('title'=>$_lang["refresh_tree"], 'label'=>$_style['refresh_tree'], 'onclick'=>'top.mainMenu.reloadtree();', 'href'=>'#'), array('class'=>'treeButton'));
	$tpe->setElement('treemenu.button', 'treemenu.Button5', array('title'=>$_lang["sort_tree"], 'label'=>$_style['sort_tree'], 'onclick'=>'showSorter();', 'href'=>'#'), array('class'=>'treeButton'));
	if ($modx->hasPermission('edit_document')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button11', array('title'=>$_lang["sort_menuindex"], 'label'=>$_style['sort_menuindex'], 'onclick'=>"top.main.document.location.href='index.php?a=56&id=0';", 'href'=>'#'), array('class'=>'treeButton'));
	}
	if ($use_browser && $modx->hasPermission('assets_images')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button13', array('title'=>$_lang["images_management"]."\n".$_lang['em_button_shift'], 'label'=>$_style['images_management'], 'onclick'=>"", 'href'=>'#'), array('class'=>'treeButton'));
		$tpe->registerFooterScript('Button13', "
			jQuery('#Button13').click(function(e) {
				e.preventDefault();
				var randomNum = 'gener';
				if (e.shiftKey) {
					randomNum = Math.floor((Math.random()*999999)+1);
				}
				window.open('media/browser/[(which_browser)]/browse.php?&type=images',randomNum,'width=800,height=700,top='+((screen.height-700)/2)+',left='+((screen.width-800)/2)+',toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no')
			});
		");
	}
	if ($use_browser && $modx->hasPermission('assets_files')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button14', array('title'=>$_lang["files_management"]."\n".$_lang['em_button_shift'], 'label'=>$_style['files_management'], 'onclick'=>"", 'href'=>'#'), array('class'=>'treeButton'));
		$tpe->registerFooterScript('Button14', "
			jQuery('#Button14').click(function(e) {
				e.preventDefault();
				var randomNum = 'gener';
				if (e.shiftKey) {
					randomNum = Math.floor((Math.random()*999999)+1);
				}
				window.open('media/browser/[(which_browser)]/browse.php?&type=files',randomNum,'width=800,height=700,top='+((screen.height-700)/2)+',left='+((screen.width-800)/2)+',toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no')
			});
		");
	}
	if ($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button12', array('title'=>$_lang["element_management"]."\n".$_lang['em_button_shift'], 'label'=>$_style['element_management'], 'onclick'=>"", 'href'=>'#'), array('class'=>'treeButton'));
		$tpe->registerFooterScript('Button12', "
			jQuery('#Button12').click(function(e) {
				e.preventDefault();
				var randomNum = 'gener';
				if (e.shiftKey) {
					randomNum = Math.floor((Math.random()*999999)+1);
				}
				window.open('index.php?a=76',randomNum,'width=800,height=600,top='+((screen.height-600)/2)+',left='+((screen.width-800)/2)+',toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no')
			});
		");
	}
	if ($modx->hasPermission('empty_trash')) {
		$tpe->setElement('treemenu.button', 'treemenu.Button10', array('title'=>$_lang["empty_recycle_bin_empty"], 'label'=>$_style['empty_recycle_bin_empty'], 'onclick'=>"", 'href'=>'#'), array('class'=>'treeButtonDisabled'));
	}
	
    $resource_tree = $this->fetchPhpTpl('frames/tree.resources.php');
	$tpe->setPlaceholder('resource_tree', $resource_tree);

?>