<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

global $_style, $_lang;

// Prepare template-engine
$tpe =& $modx->manager->tpe;
$tpe->setActionTemplate('mutate_content.dynamic')
	->setPlaceholder('title', $_REQUEST['id'] ? $_lang['edit_resource_title'] . ' <small>('. $_REQUEST['id'].')</small>' : $_lang['create_resource_title'])
	->setPlaceholder('action_icon', 'fa fa-pencil-square-o');

/********************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;
/*******************/

// check permissions
switch ($_REQUEST['a']) {
    case 27:
        if (!$modx->hasPermission('edit_document')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 85:
    case 72:
    case 4:
        if (!$modx->hasPermission('new_document')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        } elseif(isset($_REQUEST['pid']) && $_REQUEST['pid'] != '0') {
            // check user has permissions for parent
            include_once(MODX_MANAGER_PATH.'processors/user_documents_permissions.class.php');
            $udperms = new udperms();
            $udperms->user = $modx->getLoginUserID();
            $udperms->document = empty($_REQUEST['pid']) ? 0 : $_REQUEST['pid'];
            $udperms->role = $_SESSION['mgrRole'];
            if (!$udperms->checkPermissions()) {
                $modx->webAlertAndQuit($_lang["access_permission_denied"]);
            }
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}


$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// Get table names (alphabetical)
$tbl_active_users               = $modx->getFullTableName('active_users');
$tbl_categories                 = $modx->getFullTableName('categories');
$tbl_document_group_names       = $modx->getFullTableName('documentgroup_names');
$tbl_member_groups              = $modx->getFullTableName('member_groups');
$tbl_membergroup_access         = $modx->getFullTableName('membergroup_access');
$tbl_document_groups            = $modx->getFullTableName('document_groups');
$tbl_keyword_xref               = $modx->getFullTableName('keyword_xref');
$tbl_site_content               = $modx->getFullTableName('site_content');
$tbl_site_content_metatags      = $modx->getFullTableName('site_content_metatags');
$tbl_site_keywords              = $modx->getFullTableName('site_keywords');
$tbl_site_metatags              = $modx->getFullTableName('site_metatags');
$tbl_site_templates             = $modx->getFullTableName('site_templates');
$tbl_site_tmplvar_access        = $modx->getFullTableName('site_tmplvar_access');
$tbl_site_tmplvar_contentvalues = $modx->getFullTableName('site_tmplvar_contentvalues');
$tbl_site_tmplvar_templates     = $modx->getFullTableName('site_tmplvar_templates');
$tbl_site_tmplvars              = $modx->getFullTableName('site_tmplvars');

if ($modx->manager->action == 27) {
    //editing an existing document
    // check permissions on the document
    include_once(MODX_MANAGER_PATH.'processors/user_documents_permissions.class.php');
    $udperms = new udperms();
    $udperms->user = $modx->getLoginUserID();
    $udperms->document = $id;
    $udperms->role = $_SESSION['mgrRole'];

    if (!$udperms->checkPermissions()) {
        $modx->webAlertAndQuit($_lang["access_permission_denied"]);
    }
}

// Check to see the document isn't locked
$where = sprintf("action=27 AND id='%s' AND internalKey!='%s'", $id, $modx->getLoginUserID());
$rs = $modx->db->select('username', $tbl_active_users, $where);
if ($username = $modx->db->getValue($rs)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $username, 'document'));
}

// get document groups for current user
if ($_SESSION['mgrDocgroups']) {
    $docgrp = implode(',', $_SESSION['mgrDocgroups']);
}

if (!empty ($id)) {
    $access = sprintf("1='%s' OR sc.privatemgr=0", $_SESSION['mgrRole']);
    if($docgrp) $access .= " OR dg.document_group IN ({$docgrp})";
	$rs = $modx->db->select(
		'sc.*',
		"{$tbl_site_content} AS sc LEFT JOIN {$tbl_document_groups} AS dg ON dg.document=sc.id",
		"sc.id='{$id}' AND ({$access})"
		);
	$content = array();
    $content = $modx->db->getRow($rs);
    $modx->documentObject = &$content;
    if (!$content) {
        $modx->webAlertAndQuit($_lang["access_permission_denied"]);
    }
    $_SESSION['itemname'] = $content['pagetitle'];
} else {
    $content = array();
    
    if (isset($_REQUEST['newtemplate'])){
    	$content['template'] = $_REQUEST['newtemplate'];
    }else{
    	$content['template'] = getDefaultTemplate();
    }
    
    $_SESSION['itemname'] = $_lang["new_resource"];
}

// restore saved form
$formRestored = $modx->manager->loadFormValues();
if(isset($_REQUEST['newtemplate'])) $formRestored = true;

// retain form values if template was changed
// edited to convert pub_date and unpub_date
// sottwell 02-09-2006
if ($formRestored == true) {
    $content = array_merge($content, $_POST);
    $content['content'] = $_POST['ta'];
    if (empty ($content['pub_date'])) {
        unset ($content['pub_date']);
    } else {
        $content['pub_date'] = $modx->toTimeStamp($content['pub_date']);
    }
    if (empty ($content['unpub_date'])) {
        unset ($content['unpub_date']);
    } else {
        $content['unpub_date'] = $modx->toTimeStamp($content['unpub_date']);
    }
}

// increase menu index if this is a new document
if (!isset ($_REQUEST['id'])) {
    if (!isset ($modx->config['auto_menuindex'])) $modx->config['auto_menuindex'] = 1;
    if ($modx->config['auto_menuindex']) {
        $pid = intval($_REQUEST['pid']);
        $rs = $modx->db->select('count(*)', $tbl_site_content, "parent='{$pid}'");
        $content['menuindex'] = $modx->db->getValue($rs);
    } else {
        $content['menuindex'] = 0;
    }
}

if (isset ($_POST['which_editor'])) {
    $modx->config['which_editor'] = $_POST['which_editor'];
}

// Template engine - Prepare action-buttons
$tpe->setElement('action.button', 'actions.Button1', array('label'=>$_lang['save'], 'icon'=>$_style["icons_save"], 'href'=>'javascript:void(0)','onclick'=>'documentDirty=false; document.mutate.save.click();'), array('class'=>'primary'));
$tpe->setElement('action.select', 'actions.Button1.stay', array('name'=>'stay', 'manual'=>'form="mutate"', 'value'=>$_REQUEST['stay']));   // Hide from output
if ($modx->hasPermission('new_document')) {
	$tpe->setElement('select.option', 'actions.Button1.stay.stay1', array('value'=>'1', 'label'=>$_lang['stay_new'], 'selected'=>$_REQUEST['stay']=='1' ? ' selected="selected"' : ''));
}
$tpe->setElement('select.option', 'actions.Button1.stay.stay2', array('value'=>'2', 'label'=>$_lang['stay'], 'selected'=>$_REQUEST['stay']=='2' ? ' selected="selected"' : ''));
$tpe->setElement('select.option', 'actions.Button1.stay.stay3', array('value'=>'', 'label'=>$_lang['close'], 'selected'=>$_REQUEST['stay']=='3' ? ' selected="selected"' : ''));

if ($_REQUEST['a'] == '4' || $_REQUEST['a'] == '72') {
	$tpe->setElement('action.button', 'actions.Button6', array('label'=>$_lang['duplicate'], 'icon'=>$_style["icons_resource_duplicate"], 'href'=>'#','onclick'=>''), array('class'=>'disabled','disabled'=>true));
	$tpe->setElement('action.button', 'actions.Button3', array('label'=>$_lang['delete'], 'icon'=>$_style["icons_delete_document"], 'href'=>'#','onclick'=>''), array('class'=>'disabled','disabled'=>true));
} else {
	$tpe->setElement('action.button', 'actions.Button6', array('label'=>$_lang['duplicate'], 'icon'=>$_style["icons_resource_duplicate"], 'href'=>'#','onclick'=>'duplicatedocument();'));
	$tpe->setElement('action.button', 'actions.Button3', array('label'=>$_lang['delete'], 'icon'=>$_style["icons_delete_document"], 'href'=>'#','onclick'=>'deletedocument();'));
}

$onclick = $id==0 ? "document.location.href='index.php?a=2';" : "document.location.href='index.php?a=3&amp;id=$id".htmlspecialchars($add_path)."';";
$tpe->setElement('action.button', 'actions.Button4', array('label'=>$_lang['cancel'], 'icon'=>$_style["icons_cancel"], 'href'=>'#','onclick'=>'documentDirty=false;'.$onclick), array());

$onclick = "window.open('".$modx->makeUrl($id)."','previeWin');";
$tpe->setElement('action.button', 'actions.Button5', array('label'=>$_lang['preview'], 'icon'=>$_style["icons_preview_resource"], 'href'=>'#','onclick'=>'documentDirty=false;'.$onclick), array());

// Prepare Javascript-Object for transferring parameters
$modx_params = json_encode(array(
	'lang'=>array(
		'confirm_delete_resource'=>$_lang["confirm_delete_resource"],
		'confirm_resource_duplicate'=>$_lang["confirm_resource_duplicate"],
		'illegal_parent_self'=>$_lang["illegal_parent_self"],
		'illegal_parent_child'=>$_lang["illegal_parent_child"],
		'tmplvar_change_template_msg'=>$_lang["tmplvar_change_template_msg"],
		'parameter'=>$_lang["parameter"],
		'value'=>$_lang["value"],
	),
	'style'=>array(
		'icons_tooltip'=>$_style["icons_tooltip"],
		'tree_folder'=>$_style["tree_folder"],
		'icons_set_parent'=>$_style["icons_set_parent"],
	),
	'param'=>array(
		'add_path'=>$add_path,
		'id'=>$_REQUEST['id'],
		'action'=>$modx->manager->action,
	)
));
$tpe->registerHeadScriptFromFile('mutate_content.dynamic', 'media/script/actions/mutate_content.dynamic.js', array('modx_params'=>$modx_params));

// Create Form
$tpe->setPlaceholder('main_form', 'mutate');
$tpe->setElement('form', 'mutate', array('name'=>'mutate', 'action'=>'index.php', 'method'=>'post', 'enctype'=>'enctype="multipart/form-data"', 'onsubmit'=>'documentDirty=false;'), array('class'=>'content'));

$evtOut = $modx->invokeEvent('OnDocFormPrerender', array(
	'id' => $id,
	'template' => $content['template']
));
if(is_array($evtOut)) $evtOut = implode('', $evtOut);
if(!empty($evtOut)) $tpe->setElement('raw', 'mutate.OnDocFormPrerender', array('content'=>$evtOut));

/*************************/	
$dir=isset($_REQUEST['dir'])?$_REQUEST['dir']:'';
$sort=isset($_REQUEST['sort'])?$_REQUEST['sort']:'createdon';
$page=isset($_REQUEST['page'])?(int)$_REQUEST['page']:'';
/*************************/

$tpe->setElement('input', 'mutate.a',               array('name'=>'a', 'type'=>'hidden', 'value'=>5));
$tpe->setElement('input', 'mutate.id',              array('name'=>'id', 'type'=>'hidden', 'value'=>$content['id']));
$tpe->setElement('input', 'mutate.mode',            array('name'=>'mode', 'type'=>'hidden', 'value'=>(int) $_REQUEST['a']));
$tpe->setElement('input', 'mutate.MAX_FILE_SIZE',   array('name'=>'MAX_FILE_SIZE', 'type'=>'hidden', 'value'=>isset($modx->config['upload_maxsize']) ? $modx->config['upload_maxsize'] : 1048576));
$tpe->setElement('input', 'mutate.refresh_preview', array('name'=>'refresh_preview', 'type'=>'hidden', 'value'=>0));
$tpe->setElement('input', 'mutate.newtemplate',     array('name'=>'newtemplate', 'type'=>'hidden', 'value'=>''));
$tpe->setElement('input', 'mutate.dir',             array('name'=>'dir', 'type'=>'hidden', 'value'=>$dir));
$tpe->setElement('input', 'mutate.sort',            array('name'=>'sort', 'type'=>'hidden', 'value'=>$sort));
$tpe->setElement('input', 'mutate.page',            array('name'=>'page', 'type'=>'hidden', 'value'=>$page));

// breadcrumbs
if ($modx->config['use_breadcrumbs']) {
	$temp = array();
	$title = isset($content['pagetitle']) ? $content['pagetitle'] : $_lang['create_resource_title'];

	if (isset($_REQUEST['id']) && $content['parent'] != 0) {
		$bID = (int)$_REQUEST['id'];
		$temp = $modx->getParentIds($bID);
	} else if (isset($_REQUEST['pid'])) {
		$bID = (int)$_REQUEST['pid'];
		$temp = $modx->getParentIds($bID);
		array_unshift($temp, $bID);
	}

	if ($temp) {
		$parents = implode(',', $temp);

		if (!empty($parents)) {
			$where = "FIND_IN_SET(id,'{$parents}') DESC";
			$rs = $modx->db->select('id, pagetitle', $tbl_site_content, "id IN ({$parents})", $where);
			while ($row = $modx->db->getRow($rs)) {
				$out .= '<li class="breadcrumbs__li">
                                <a href="index.php?a=27&id=' . $row['id'] . '" class="breadcrumbs__a">' . htmlspecialchars($row['pagetitle'], ENT_QUOTES, $modx->config['modx_charset']) . '</a>
                                <span class="breadcrumbs__sep">&gt;</span>
                            </li>';
			}
		}
	}
	$out .= '<li class="breadcrumbs__li breadcrumbs__li_current">' . $title . '</li>';
	$breadcrumbs = '<ul class="breadcrumbs">' . $out . '</ul>';
	$tpe->setPlaceholder('breadcrumbs', $breadcrumbs);
}

$tpe->setElement('tabpane',	'mutate.documentPane', array('object_id'=>'tpSettings'));

$evtOut = $modx->invokeEvent('OnDocFormTemplateRender', array(
	'id' => $id
));

if (is_array($evtOut)) $tpe->setElement('raw', 'mutate.documentPane.OnDocFormTemplateRender', array('content'=>implode('', $evtOut)), array('protect_phs'=>'content'));
else {

$tpe->setElement('tab',           'mutate.documentPane.tabGeneral', 	                 array('label'=>$_lang['settings_general']));
$tpe->setElement('section.blank', 'mutate.documentPane.tabGeneral.tg_section1',             array('label'=>$_lang['settings_general']), array() );
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.pagetitle',   array('name'=>'pagetitle', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                              'label'=>$_lang['resource_title'], 
                                                                                              'help'=>$_lang['resource_title_help'], 
                                                                                              'value'=>$modx->htmlspecialchars(stripslashes($content['pagetitle']))),
	// Special tpe-param 'append' to append HTML-code after the rendered element (in this case after <input>)
	array('append'=>strpos($content['pagetitle'],'Duplicate of')!==false ? '<script>document.getElementsByName("pagetitle")[0].focus();</script>' : ''));
	
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.longtitle',   array('name'=>'longtitle', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                             'label'=>$_lang['long_title'],
                                                                                             'help'=>$_lang['resource_long_title_help'],
                                                                                             'value'=>$modx->htmlspecialchars(stripslashes($content['longtitle']))));
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.description', array('name'=>'description', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                             'label'=>$_lang['resource_description'],
                                                                                             'help'=>$_lang['resource_description_help'],
                                                                                             'value'=>$modx->htmlspecialchars(stripslashes($content['description']))));
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.alias',       array('name'=>'alias', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="100" spellcheck="true"',
                                                                                             'label'=>$_lang['resource_alias'],
                                                                                             'help'=>$_lang['resource_alias_help'],
                                                                                             'value'=>$modx->htmlspecialchars(stripslashes($content['alias']))));
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.link_attributes', array('name'=>'link_attributes', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                             'label'=>$_lang['link_attributes'],
                                                                                             'help'=>$_lang['link_attributes_help'],
                                                                                             'value'=>$modx->htmlspecialchars(stripslashes($content['link_attributes']))));
// Web Link specific
if ($content['type'] == 'reference' || $_REQUEST['a'] == '72') {
$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.ta',          array('name'=>'ta', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                               'label'=>$_lang['weblink'],
                                                                                               'outsideLabel'=>'<img name="llock" src="'.$_style["tree_folder"].'" alt="tree_folder" onclick="enableLinkSelection(!allowLinkSelection);" style="cursor:pointer;" />',
                                                                                               'help'=>$_lang['resource_weblink_help'],
                                                                                               'value'=>!empty($content['content']) ? stripslashes($content['content']) : 'http://'));
}

$tpe->setElement('input.textarea','mutate.documentPane.tabGeneral.tg_section1.introtext',   array('name'=>'introtext', 'onchange'=>'documentDirty=true;', 'rows'=>3, 'cols'=>'',
                                                                                               'label'=>$_lang['resource_summary'],
                                                                                               'help'=>$_lang['resource_summary_help'],
                                                                                               'value'=>$modx->htmlspecialchars(stripslashes($content['introtext']))));

// Template Select-Box
$tpe->setElement('input.select','mutate.documentPane.tabGeneral.tg_section1.template',      array('id'=>'template', 'name'=>'template', 'onchange'=>'templateWarning();',
	                                                                                           'label'=>$_lang['page_data_template'],
	                                                                                           'help'=>$_lang['page_data_template_help']));
// Set option "(blank)"
$tpe->setElement('select.option',  'mutate.documentPane.tabGeneral.tg_section1.template.template_opt0',array('label'=>'(blank)', 'value'=>'0'));

// Add Select-Options in optgroups
$field = "t.templatename, t.selectable, t.id, c.category";
$from  = "{$tbl_site_templates} AS t LEFT JOIN {$tbl_categories} AS c ON t.category = c.id";
$rs = $modx->db->select($field,$from,'','c.category, t.templatename ASC');
$currentCategory = '';
$groupIter = 0;
$optIter = 0;
while ($row = $modx->db->getRow($rs)) {
	// Skip if not selectable but show if selected!
	if($row['selectable'] != 1 && $row['id'] != $content['template']) { continue; };
	
	$thisCategory = $row['category'];
	if($thisCategory == null) {
		$thisCategory = $_lang["no_category"];
	}
	if($thisCategory != $currentCategory) {
		$groupIter++;
		$tpe->setElement('select.optgroup','mutate.documentPane.tabGeneral.tg_section1.template.optgroup'.$groupIter, array('label'=>$thisCategory));
	}

	$selected = ($row['id'] == $content['template']) ? ' selected="selected"' : '';
	
	$optIter++;
	$tpe->setElement('select.option',  'mutate.documentPane.tabGeneral.tg_section1.template.optgroup'.$groupIter.'.template_opt'.$optIter, array('label'=>$row['templatename'], 'value'=>$row['id'], 'selected'=>$selected));
	
	$currentCategory = $thisCategory;
}
// Template Select-Box END

$tpe->setElement('input.text',    'mutate.documentPane.tabGeneral.tg_section1.menutitle',       array('name'=>'menutitle', 'onchange'=>'documentDirty=true;', 'manual'=>'maxlength="255" spellcheck="true"',
                                                                                             'label'=>$_lang['resource_opt_menu_title'],
                                                                                             'help'=>$_lang['resource_opt_menu_title_help'],
                                                                                             'value'=>$modx->htmlspecialchars(stripslashes($content['menutitle']))));
$tpe->setElement('input.number',  'mutate.documentPane.tabGeneral.tg_section1.menuindex',       array('name'=>'menuindex', 'onchange'=>'documentDirty=true;', 'min'=>0, 'max'=>999999,
                                                                                             'label'=>$_lang['resource_opt_menu_index'],
                                                                                             'help'=>$_lang['resource_opt_menu_index_help'],
                                                                                             'value'=>$content['menuindex']));
$tpe->setElement('input.checkbox','mutate.documentPane.tabGeneral.tg_section1.hidemenucheck',   array('name'=>'hidemenucheck', 'onchange'=>'documentDirty=true;', 'onclick'=>'changestate(document.mutate.hidemenu);',
                                                                                             'label'=>$_lang['resource_opt_show_menu'],
                                                                                             'help'=>$_lang['resource_opt_show_menu_help'],
                                                                                             'value'=>$content['hidemenu'], 
                                                                                             'checked'=>$content['hidemenu']!=1 ? 'checked="checked"':''));
$tpe->setElement('input.hidden', 'mutate.hidemenu',                                          array('name'=>'hidemenu', 'type'=>'hidden', 'value'=>($content['hidemenu']==1) ? 1 : 0));

$tpe->setElement('form.splitter','mutate.documentPane.tabGeneral.tg_section1.tg_splitter1_1');

// Set parent-selector
$parentlookup = false;
if (isset ($_REQUEST['id'])) {
	if ($content['parent'] == 0) {
		$parentname = $site_name;
	} else {
		$parentlookup = $content['parent'];
	}
} elseif (isset ($_REQUEST['pid'])) {
	if ($_REQUEST['pid'] == 0) {
		$parentname = $site_name;
	} else {
		$parentlookup = $_REQUEST['pid'];
	}
} elseif (isset($_POST['parent'])) {
	if ($_POST['parent'] == 0) {
		$parentname = $site_name;
	} else {
		$parentlookup = $_POST['parent'];
	}
} else {
	$parentname = $site_name;
	$content['parent'] = 0;
}
if($parentlookup !== false && is_numeric($parentlookup)) {
	$rs = $modx->db->select('pagetitle', $tbl_site_content, "id='{$parentlookup}'");
	$parentname = $modx->db->getValue($rs);
	if (!$parentname) {
		$modx->webAlertAndQuit($_lang["error_no_parent"]);
	}
}

$tpe->setElement('raw', 'mutate.documentPane.tabGeneral.tg_section1.parent_select',                    array('label'=>$_lang['resource_parent'], 'help'=>$_lang['resource_parent_help'], 
	'content'=>'<img alt="tree_folder" name="plock" src="'.$_style['tree_folder'].'" onclick="enableParentSelection(!allowParentSelection);" style="cursor:pointer;" />
	<b><span id="parentName">'. (isset($_REQUEST['pid']) ? $_REQUEST['pid'] : $content['parent']) .' ('.$parentname.')</span></b>'
));
$tpe->setElement('input.hidden', 'mutate.parent',                                          array('name'=>'parent', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>isset($_REQUEST['pid']) ? $_REQUEST['pid'] : $content['parent']));

// Special function: Set an outerTpl for all childs (in this case inputs) of 'section1', ignore elements with type 'form.splitter'
$tpe->setElementChildsTpe('tg_section1', 'outerTpl', 'form.table.row.tooltip', 'form.splitter');

// Content / Show Richtexteditor
if ($content['type'] == 'document' || $_REQUEST['a'] == '4') {
	
	$tpe->setElement('section', 'mutate.documentPane.tabGeneral.tg_section2',             array('label'=>$_lang['resource_content']), array('innerTpl'=>NULL)); // innerTpl = remove table-wrapper etc

	if (($content['richtext'] == 1 || $_REQUEST['a'] == '4') && $use_editor == 1) {
		$htmlContent = $content['content'];

		$tpe->setElement('input.textarea','mutate.documentPane.tabGeneral.tg_section2.ta',   array('name'=>'ta','onchange'=>'documentDirty=true;', 'style'=>'width:100%; height: 400px;',
		                                                                                               // 'label'=>$_lang['resource_summary'],
		                                                                                               // 'help'=>$_lang['resource_summary_help'],
		                                                                                               'value'=>$modx->htmlspecialchars($htmlContent)),
			array('outerTpl'=>'form.tableless.row')); // outerTpl = remove table-rows etc;

		$tpe->setElement('form.spacer','mutate.documentPane.tabGeneral.tg_section2.spacer1');
		
		// Template Select-Box
		$tpe->setElement('input.select','mutate.documentPane.tabGeneral.tg_section2.which_editor', array('name'=>'which_editor', 'id'=>'which_editor', 'onchange'=>'changeRTE();',
		                                                                                               'label'=>$_lang['which_editor_title']),
			array('outerTpl'=>'form.tableless.row')); // outerTpl = remove table-rows etc;
		
		// Set option "none"
		// @todo: Add attr.selected
		$tpe->setElement('select.option',  'mutate.documentPane.tabGeneral.tg_section2.which_editor.opt_none', array('label'=>$_lang['none'], 'value'=>'none'));

		// Now invoke OnRichTextEditorRegister event
		$evtOut = $modx->invokeEvent("OnRichTextEditorRegister");
		if (is_array($evtOut)) {
			for ($i = 0; $i < count($evtOut); $i++) {
				$editor = $evtOut[$i];
				$selected = $modx->config['which_editor'] == $editor ? 'selected="selected"' : '';
				$tpe->setElement('select.option',  'mutate.documentPane.tabGeneral.tg_section2.which_editor.which_editor_opt'.$i, array('label'=>$editor, 'value'=>$editor, 'selected'=>$selected));
			}
		}
		
		// Richtext-[*content*]
		$richtexteditorIds = array();
		$richtexteditorOptions = array();
		$richtexteditorIds[$modx->config['which_editor']][] = 'ta';
		$richtexteditorOptions[$modx->config['which_editor']]['ta'] = '';
		
	} else {
		$tpe->setElement('input.textarea','mutate.documentPane.tabGeneral.tg_section2.ta',   array('name'=>'ta', 'onchange'=>'documentDirty=true;', 'style'=>'width:100%; height: 400px;',
			                                                                                    'value'=>$modx->htmlspecialchars($content['content']), array('class'=>'phptextarea')),
			array('outerTpl'=>NULL)); // innerTpl = remove table-rows etc
	}
}

// Template Variables
if (($content['type'] == 'document' || $_REQUEST['a'] == '4') || ($content['type'] == 'reference' || $_REQUEST['a'] == 72)) {
	
	$tpe->setElement('section', 'mutate.documentPane.tabGeneral.tg_section3', array('label'=>$_lang['settings_templvars']));

	$template = $default_template;
	if (isset ($_REQUEST['newtemplate'])) {
		$template = $_REQUEST['newtemplate'];
	} else {
		if (isset ($content['template']))
			$template = $content['template'];
	}

	$field = "DISTINCT tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value, tvtpl.rank as tvrank";
	$vs = array($tbl_site_tmplvars, $tbl_site_tmplvar_templates, $tbl_site_tmplvar_contentvalues, $id, $tbl_site_tmplvar_access);
	$from = vsprintf("%s AS tv INNER JOIN %s AS tvtpl ON tvtpl.tmplvarid = tv.id
                         LEFT JOIN %s AS tvc ON tvc.tmplvarid=tv.id AND tvc.contentid='%s'
                         LEFT JOIN %s AS tva ON tva.tmplvarid=tv.id", $vs);
	$dgs = $docgrp ? " OR tva.documentgroup IN ({$docgrp})" : '';
	$vs = array($template, $_SESSION['mgrRole'], $dgs);
	$where = vsprintf("tvtpl.templateid='%s' AND (1='%s' OR ISNULL(tva.documentgroup) %s)", $vs);
	$rs = $modx->db->select($field,$from,$where,'tvtpl.rank,tv.rank, tv.id');
	$limit = $modx->db->getRecordCount($rs);
	if ($limit > 0) {
		$tvsArray = $modx->db->makeArray($rs,'name');
		require_once(MODX_MANAGER_PATH.'includes/tmplvars.inc.php');
		require_once(MODX_MANAGER_PATH.'includes/tmplvars.commands.inc.php');
		$i = 0;
		foreach ($tvsArray as $row) {
			// Go through and display all Template Variables
			if ($row['type'] == 'richtext' || $row['type'] == 'htmlarea') {
				// determine TV-options
				$tvOptions = $modx->parseProperties($row['elements']);
				if(!empty($tvOptions)) {
					// Allow different Editor with TV-option {"editor":"CKEditor4"} or &editor=Editor;text;CKEditor4
					$editor = isset($tvOptions['editor']) ? $tvOptions['editor']: $modx->config['which_editor'];
				};
				// Add richtext editor to the list
				$richtexteditorIds[$editor][] = "tv".$row['id'];
				$richtexteditorOptions[$editor]["tv".$row['id']] = $tvOptions;
			}
			// splitter
			if ($i++ > 0)
				$tpe->setElement('form.splitter','mutate.documentPane.tabGeneral.tg_section3.splitter3_'.$i);

			// post back value
			if(array_key_exists('tv'.$row['id'], $_POST)) {
				if(is_array($_POST['tv'.$row['id']])) {
					$tvPBV = implode('||', $_POST['tv'.$row['id']]);
				} else {
					$tvPBV = $_POST['tv'.$row['id']];
				}
			} else {
				$tvPBV = $row['value'];
			}

			$tpe->setElement('input.templatevar', 'mutate.documentPane.tabGeneral.tg_section3.'.$row['name'], array(
				'caption'=>$row['caption'],
				'name'=>$modx->hasPermission('edit_template') ? '[*'.$row['name'].'*]' : '',
				'description'=>(!empty($row['description'])) ? $row['description'] : '',
				'inherited'=>(substr($tvPBV, 0, 8) == '@INHERIT') ? $_lang['tmplvars_inherited'] : '',
				'tv'=>renderFormElement($row['type'], $row['id'], $row['default_text'], $row['elements'], $tvPBV, '', $row, $tvsArray)
			));
		}
		
	} else {
		// There aren't any Template Variables
		$tpe->setElement('message', 'mutate.documentPane.tabGeneral.tg_section3.message', array('message'=>$_lang['tmplvars_novars']));
	}
}

// Tab "Settings"
$tpe->setElement('tab',           'mutate.documentPane.tabSettings', 	                 array('label'=>$_lang['settings_page_settings']));
$tpe->setElement('section.blank', 'mutate.documentPane.tabSettings.ts_section1',            array('label'=>$_lang['settings_page_settings']));

$mx_can_pub = $modx->hasPermission('publish_document') ? '' : 'disabled="disabled" ';

$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.publishedcheck', 
	array('name'=>'publishedcheck', 'onchange'=>'documentDirty=true;', 'onclick'=>'changestate(document.mutate.published);',
	     'label'=>$_lang['resource_opt_published'],
	     'help'=>$_lang['resource_opt_published_help'],
	     'checked'=>(isset($content['published']) && $content['published']==1) || (!isset($content['published']) && $publish_default==1) ? 'checked="checked"':'',
	     'manual'=>$mx_can_pub));
$tpe->setElement('input.hidden', 'mutate.published', array('name'=>'published', 'type'=>'hidden', 'value'=>(isset($content['published']) && $content['published']==1) || (!isset($content['published']) && $publish_default==1) ? 1 : '0'));

$tpe->setElement('input.date','mutate.documentPane.tabSettings.ts_section1.pub_date',
	array('name'=>'pub_date', 'onblur'=>'documentDirty=true;',
	      'label'=>$_lang['page_data_publishdate'],
	      'help'=>$_lang['page_data_publishdate_help'],
	      'value'=>$content['pub_date']=="0" || !isset($content['pub_date']) ? '' : $modx->toDateFormat($content['pub_date']),
	      'manual'=>$mx_can_pub));
	
$tpe->setElement('input.date','mutate.documentPane.tabSettings.ts_section1.unpub_date',
	array('name'=>'unpub_date', 'onblur'=>'documentDirty=true;',
	      'label'=>$_lang['page_data_unpublishdate'],
	      'help'=>$_lang['page_data_unpublishdate_help'],
	      'value'=>$content['unpub_date']=="0" || !isset($content['unpub_date']) ? '' : $modx->toDateFormat($content['unpub_date']),
	      'manual'=>$mx_can_pub));

$tpe->setElement('form.splitter','mutate.documentPane.tabSettings.ts_section1.splitter1_1');

if ($_SESSION['mgrRole'] == 1 || $_REQUEST['a'] != '27' || $_SESSION['mgrInternalKey'] == $content['createdby'] || $modx->hasPermission('change_resourcetype')) {
	// Resource-Type Select-Box
	$tpe->setElement('input.select','mutate.documentPane.tabSettings.ts_section1.type', array('name'=>'type', 'onchange'=>'documentDirty=true;',
	                                                                                       'help'=>$_lang['resource_type_message'],
	                                                                                       'label'=>$_lang['resource_type']));
	// Select-Options
	$tpe->setElement('select.option',  'mutate.documentPane.tabSettings.ts_section1.type.type_opt1', array('label'=>$_lang['resource_type_webpage'], 'value'=>'document',
		'selected'=>(($content['type'] == "document" || $_REQUEST['a'] == '85' || $_REQUEST['a'] == '4') ? ' selected="selected"' : "")));
	$tpe->setElement('select.option',  'mutate.documentPane.tabSettings.ts_section1.type.type_opt2', array('label'=>$_lang['resource_type_weblink'], 'value'=>'reference',
		'selected'=>(($content['type'] == "reference" || $_REQUEST['a'] == '72') ? ' selected="selected"' : "")));

	// Content-Type Select-Box
	$tpe->setElement('input.select','mutate.documentPane.tabSettings.ts_section1.contentType', array('name'=>'contentType', 'onchange'=>'documentDirty=true;',
	                                                                                              'help'=>$_lang['page_data_contentType_help'],
	                                                                                              'label'=>$_lang['page_data_contentType']));
	// Select-Options
	if (!$content['contentType'])
		$content['contentType'] = 'text/html';
	$custom_contenttype = (isset ($custom_contenttype) ? $custom_contenttype : "text/html,text/plain,text/xml");
	$ct = explode(",", $custom_contenttype);
	for ($i = 0; $i < count($ct); $i++) {
		$tpe->setElement('select.option',  'mutate.documentPane.tabSettings.ts_section1.contentType.contentType_opt'.($i+2), array('label'=>$ct[$i], 'value'=>$ct[$i],
		                                                                                               'selected'=>($content['contentType'] == $ct[$i] ? ' selected="selected"' : '')));
	}

	// Content-Type Select-Box
	$tpe->setElement('input.select','mutate.documentPane.tabSettings.ts_section1.content_dispo', array('name'=>'content_dispo', 'onchange'=>'documentDirty=true;', 'manual'=>'size="1"',
	                                                                                                'help'=>$_lang['resource_opt_contentdispo_help'],
	                                                                                                'label'=>$_lang['resource_opt_contentdispo']));
	// Select-Options
	$tpe->setElement('select.option',  'mutate.documentPane.tabSettings.ts_section1.content_dispo.content_dispo_opt1', array('label'=>$_lang['inline'], 'value'=>'0',
	                                                                                               'selected'=>(!$content['content_dispo'] ? ' selected="selected"':'')));
	$tpe->setElement('select.option',  'mutate.documentPane.tabSettings.ts_section1.content_dispo.content_dispo_opt2', array('label'=>$_lang['attachment'], 'value'=>'1',
                                                                                                        'selected'=>($content['content_dispo']==1 ? ' selected="selected"':'')));

	$tpe->setElement('form.splitter', 'mutate.documentPane.tabSettings.ts_section1.splitter1_2');
	
	
} else {
	if ($content['type'] != 'reference' && $_REQUEST['a'] != '72') {
		// non-admin managers creating or editing a document resource
		$tpe->setElement('input.hidden', 'mutate.contentType', array('name'=>'contentType', 'type'=>'hidden', 'value'=>isset($content['contentType']) ? $content['contentType'] : "text/html"));
		$tpe->setElement('input.hidden', 'mutate.type', array('name'=>'type', 'type'=>'hidden', 'value'=>'document'));
		$tpe->setElement('input.hidden', 'mutate.content_dispo', array('name'=>'content_dispo', 'type'=>'hidden', 'value'=>isset($content['content_dispo']) ? $content['content_dispo'] : '0'));
	} else {
		// non-admin managers creating or editing a reference (weblink) resource
		$tpe->setElement('input.hidden', 'mutate.type', array('name'=>'type', 'type'=>'hidden', 'value'=>'reference'));
		$tpe->setElement('input.hidden', 'mutate.contentType', array('name'=>'contentType', 'type'=>'hidden', 'value'=>'text/html'));
	}
}//if mgrRole

// Is folder / container
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.isfoldercheck', array('name'=>'isfoldercheck', 'onclick'=>'changestate(document.mutate.isfolder);',
                                                                                                   'label'=>$_lang['resource_opt_folder'],
                                                                                                   'help'=>$_lang['resource_opt_show_menu_help'],
                                                                                                   'checked'=>($content['isfolder']==1||$_REQUEST['a']=='85') ? "checked" : ''));
$tpe->setElement('input.hidden', 'mutate.isfolder',                                         array('name'=>'isfolder', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>($content['isfolder']==1||$_REQUEST['a']=='85') ? 1 : 0));

// Alias visible
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.alias_visible_check', array('name'=>'alias_visible_check', 'onclick'=>'changestate(document.mutate.alias_visible);',
                                                                                                  'label'=>$_lang['resource_opt_alvisibled'],
                                                                                                  'help'=>$_lang['resource_opt_alvisibled_help'],
                                                                                                  'checked'=>(!isset($content['alias_visible'])|| $content['alias_visible']==1) ? "checked" : ''));
$tpe->setElement('input.hidden', 'mutate.alias_visible',                                          array('name'=>'alias_visible', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>(!isset($content['alias_visible']) || $content['alias_visible']==1) ? 1 : '0'));

// Richtext
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.richtextcheck', array('name'=>'richtextcheck', 'onclick'=>'changestate(document.mutate.richtext);',
                                                                                                        'label'=>$_lang['resource_opt_richtext'],
                                                                                                        'help'=>$_lang['resource_opt_richtext_help'],
                                                                                                        'checked'=>$content['richtext']==0 && $_REQUEST['a']=='27' ? '' : "checked"));
$tpe->setElement('input.hidden', 'mutate.richtext',                                         array('name'=>'richtext', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>$content['richtext']==0 && $_REQUEST['a']=='27' ? 0 : 1));

// Track visitors
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.donthitcheck', array('name'=>'donthitcheck', 'onclick'=>'changestate(document.mutate.donthit);',
                                                                                                        'label'=>$_lang['track_visitors_title'],
                                                                                                        'help'=>$_lang['resource_opt_trackvisit_help'],
                                                                                                        'checked'=>($content['donthit']!=1) ? 'checked="checked"' : ''));
$tpe->setElement('input.hidden', 'mutate.donthit',                                         array('name'=>'donthit', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>($content['donthit']==1) ? 1 : 0));

// Searchable
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.searchablecheck', array('name'=>'searchablecheck', 'onclick'=>'changestate(document.mutate.searchable);',
                                                                                                        'label'=>$_lang['page_data_searchable'],
                                                                                                        'help'=>$_lang['page_data_searchable_help'],
                                                                                                        'checked'=>(isset($content['searchable']) && $content['searchable']==1) || (!isset($content['searchable']) && $search_default==1) ? "checked" : ''));
$tpe->setElement('input.hidden', 'mutate.searchable',                                         array('name'=>'searchable', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>(isset($content['searchable']) && $content['searchable']==1) || (!isset($content['searchable']) && $search_default==1) ? 1 : 0));

// Cacheable
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.cacheablecheck', array('name'=>'cacheablecheck', 'onclick'=>'changestate(document.mutate.cacheable);',
                                                                                                    'label'=>$_lang['page_data_cacheable'],
                                                                                                    'help'=>$_lang['page_data_cacheable_help'],
                                                                                                    'checked'=>(isset($content['cacheable']) && $content['cacheable']==1) || (!isset($content['cacheable']) && $cache_default==1) ? "checked" : ''));
$tpe->setElement('input.hidden', 'mutate.cacheable',                                         array('name'=>'cacheable', 'type'=>'hidden', 'onchange'=>'documentDirty=true;', 'value'=>(isset($content['cacheable']) && $content['cacheable']==1) || (!isset($content['cacheable']) && $cache_default==1) ? 1 : 0));

// Empty Cache
$tpe->setElement('input.checkbox','mutate.documentPane.tabSettings.ts_section1.syncsitecheck', array('name'=>'syncsitecheck', 'onclick'=>'changestate(document.mutate.syncsite);',
                                                                                                        'label'=>$_lang['resource_opt_emptycache'],
                                                                                                        'help'=>$_lang['resource_opt_emptycache_help'],
                                                                                                        'checked'=>'checked="checked"'));
$tpe->setElement('input.hidden', 'mutate.syncsite',                                          array('name'=>'syncsite', 'type'=>'hidden', 'value'=>1));

// Set different row-template with help-icons
$tpe->setElementChildsTpe('ts_section1', 'outerTpl', 'form.table.row.tooltip', 'form.splitter');

// Tab "Meta Tags"
if ($modx->hasPermission('edit_doc_metatags') && $modx->config['show_meta']) {
	// get list of site keywords
	$keywords = array();
	$ds = $modx->db->select('id, keyword', $tbl_site_keywords, '', 'keyword ASC');
	while ($row = $modx->db->getRow($ds)) {
		$keywords[$row['id']] = $row['keyword'];
	}
	// get selected keywords using document's id
	if (isset ($content['id']) && count($keywords) > 0) {
		$keywords_selected = array();
		$ds = $modx->db->select('keyword_id', $tbl_keyword_xref, "content_id='{$content['id']}'");
		while ($row = $modx->db->getRow($ds)) {
			$keywords_selected[$row['keyword_id']] = ' selected="selected"';
		}
	}

	// get list of site META tags
	$metatags = array();
	$ds = $modx->db->select('id, name', $tbl_site_metatags);
	while ($row = $modx->db->getRow($ds)) {
		$metatags[$row['id']] = $row['name'];
	}
	// get selected META tags using document's id
	if (isset ($content['id']) && count($metatags) > 0) {
		$metatags_selected = array();
		$ds = $modx->db->select('metatag_id', $tbl_site_content_metatags, "content_id='{$content['id']}'");
		while ($row = $modx->db->getRow($ds)) {
			$metatags_selected[$row['metatag_id']] = ' selected="selected"';
		}
	}

	$tpe->setElement('tab',             'mutate.documentPane.tabMeta', 	                array('label'=>$_lang['settings_page_settings']));
	$tpe->setElement('grid',            'mutate.documentPane.tabMeta.grid2_1', array(), array('tpl'=>'grid.2columns'));
	$tpe->setElement('section.blank',   'mutate.documentPane.tabMeta.grid2_1.section1', array('label'=>$_lang['keywords']), array('pos'=>'block1')); // Keywords
	$tpe->setElement('section.blank',   'mutate.documentPane.tabMeta.grid2_1.section2', array('label'=>$_lang['metatags']), array('pos'=>'block2')); // Metatags
	
	// Keywords Select-Box
	$tpe->setElement('input.select',  'mutate.documentPane.tabMeta.grid2_1.section1.keywords', array('name'=>'keywords[]', 'onchange'=>'documentDirty=true;', 'manual'=>'multiple="multiple" size="16"',
	                                                                                                'label'=>$_lang['keywords']));
	// Select-Options
	foreach ($keywords as $key=>$value) {
		$tpe->setElement('select.option', 'mutate.documentPane.tabMeta.section1.keywords.opt_'.$key, array('label'=>$value, 'value'=>$key,
		                                                                                                   'selected'=>$keywords_selected[$key]));
	}
	
	// Clear Button
	$tpe->setElement('input.button',  'mutate.documentPane.tabMeta.grid2_1.section1.clearKeywords', array('name'=>'', 'onclick'=>'clearKeywordSelection();', 'value'=>$_lang['deselect_keywords']));
	
	// Metatags Select-Box
	$tpe->setElement('input.select',  'mutate.documentPane.tabMeta.grid2_1.section2.metatags', array('name'=>'metatags[]', 'onchange'=>'documentDirty=true;', 'manual'=>'multiple="multiple" size="16"',
	                                                                                                 'label'=>$_lang['metatags']));
	// Select-Options
	foreach ($metatags as $key=>$value) {
		$tpe->setElement('select.option', 'mutate.documentPane.tabMeta.section2.metatags.opt_'.$key, array('label'=>$value, 'value'=>$key,
		                                                                                                        'selected'=>$metatags_selected[$key]));
	}

	// Clear Button
	$tpe->setElement('input.button',  'mutate.documentPane.tabMeta.grid2_1.section2.clearMetatags', array('name'=>'', 'onclick'=>'clearMetatagSelection();', 'value'=>$_lang['deselect_metatags']));
}

	/*******************************
	 * Document Access Permissions */
	if ($use_udperms == 1) {
		$groupsarray = array();
		$sql = '';

		$documentId = ($_REQUEST['a'] == '27' ? $id : (!empty($_REQUEST['pid']) ? $_REQUEST['pid'] : $content['parent']));
		if ($documentId > 0) {
			// Load up, the permissions from the parent (if new document) or existing document
			$rs = $modx->db->select('id, document_group', $tbl_document_groups, "document='{$documentId}'");
			while ($currentgroup = $modx->db->getRow($rs))
				$groupsarray[] = $currentgroup['document_group'].','.$currentgroup['id'];

			// Load up the current permissions and names
			$vs = array($tbl_document_group_names, $tbl_document_groups, $documentId);
			$from = vsprintf("%s AS dgn LEFT JOIN %s AS groups ON groups.document_group=dgn.id AND groups.document='%s'",$vs);
			$rs = $modx->db->select('dgn.*, groups.id AS link_id',$from,'','name');
		} else {
			// Just load up the names, we're starting clean
			$rs = $modx->db->select('*, NULL AS link_id', $tbl_document_group_names, '', 'name');
		}

		// retain selected doc groups between post
		if (isset($_POST['docgroups']))
			$groupsarray = array_merge($groupsarray, $_POST['docgroups']);

		$isManager = $modx->hasPermission('access_permissions');
		$isWeb     = $modx->hasPermission('web_access_permissions');

		// Setup Basic attributes for each Input box
		$inputAttributes = array(
			'type' => 'checkbox',
			'class' => 'checkbox',
			'name' => 'docgroups[]',
			'onclick' => 'makePublic(false);',
		);
		$permissions = array(); // New Permissions array list (this contains the HTML)
		$permissions_yes = 0; // count permissions the current mgr user has
		$permissions_no = 0; // count permissions the current mgr user doesn't have

		// Loop through the permissions list
		while ($row = $modx->db->getRow($rs)) {

			// Create an inputValue pair (group ID and group link (if it exists))
			$inputValue = $row['id'].','.($row['link_id'] ? $row['link_id'] : 'new');
			$inputId    = 'group-'.$row['id'];

			$checked    = in_array($inputValue, $groupsarray);
			if ($checked) $notPublic = true; // Mark as private access (either web or manager)

			// Skip the access permission if the user doesn't have access...
			if ((!$isManager && $row['private_memgroup'] == '1') || (!$isWeb && $row['private_webgroup'] == '1'))
				continue;

			// Setup attributes for this Input box
			$inputAttributes['id']    = $inputId;
			$inputAttributes['value'] = $inputValue;
			if ($checked)
				$inputAttributes['checked'] = 'checked';
			else    unset($inputAttributes['checked']);

			// Create attribute string list
			$inputString = array();
			foreach ($inputAttributes as $k => $v) $inputString[] = $k.'="'.$v.'"';

			// Make the <input> HTML
			$inputHTML = '<input '.implode(' ', $inputString).' />';

			// does user have this permission?
			$from = "{$tbl_membergroup_access} AS mga, {$tbl_member_groups} AS mg";
			$vs = array($row['id'], $_SESSION['mgrInternalKey']);
			$where = vsprintf("mga.membergroup=mg.user_group AND mga.documentgroup=%s AND mg.member=%s", $vs);
			$rsp = $modx->db->select('COUNT(mg.id)',$from,$where);
			$count = $modx->db->getValue($rsp);
			if($count > 0) {
				++$permissions_yes;
			} else {
				++$permissions_no;
			}
			$permissions[] = "\t\t".'<li><label for="'.$inputId.'">'.$inputHTML.'&nbsp;'.$row['name'].'</label></li>';
		}
		// if mgr user doesn't have access to any of the displayable permissions, forget about them and make doc public
		if($_SESSION['mgrRole'] != 1 && ($permissions_yes == 0 && $permissions_no > 0)) {
			$permissions = array();
		}

		// See if the Access Permissions section is worth displaying...
		if (!empty($permissions)) {
			// Add the "All Document Groups" item if we have rights in both contexts
			if ($isManager && $isWeb)
				array_unshift($permissions,"\t\t".'<li><label for="groupall" class="warning"><input type="checkbox" class="checkbox" name="chkalldocs" id="groupall"'.(!$notPublic ? ' checked="checked"' : '').' onclick="makePublic(true);" />&nbsp;' . $_lang['all_doc_groups'] . '</label></li>');
			// Output the permissions list...

			$tpe->setElement('tab',             'mutate.documentPane.tabAccess', 	                array('label'=>$_lang['access_permissions']));
			$tpe->setElement('section.blank',   'mutate.documentPane.tabAccess.section1',           array('label'=>$_lang['access_permissions']));

			// Permissions-List
			$tpe->setElement('form.message',    'mutate.documentPane.tabAccess.section1.msg', array('message'=>$_lang["access_permissions_docs_message"]));
			$tpe->setElement('form.raw',        'mutate.documentPane.tabAccess.section1.permissions', array('content'=>'<ul>'.implode("\n", $permissions)."\n".'</ul>'));
			
		} // !empty($permissions)
		elseif($_SESSION['mgrRole'] != 1 && ($permissions_yes == 0 && $permissions_no > 0) && ($_SESSION['mgrPermissions']['access_permissions'] == 1 || $_SESSION['mgrPermissions']['web_access_permissions'] == 1)) {
			$tpe->setElement('form.message',    'mutate.documentPane.tabAccess.section1.msg', array('message'=>$_lang["access_permissions_docs_collision"]));
		}
	}
	/* End Document Access Permissions *
	 ***********************************/

$tpe->setElement('input', 'mutate.save', array('name'=>'save', 'type'=>'submit'));

// invoke OnDocFormRender event
$evtOut = $modx->invokeEvent('OnDocFormRender', array(
	'id' => $id,
	'template' => $content['template']
));
if(is_array($evtOut)) $evtOut = implode('', $evtOut);
if(!empty($evtOut)) $tpe->setElement('raw', 'mutate.OnDocFormRender', array('content'=>$evtOut), array('protect_phs'=>'content')); // Codemirror-Script contains [+ and +] so protect them

};

// Output 
echo $tpe->renderAction();

// Not changed after switch to Template-engine 
?>

<script type="text/javascript">
	storeCurTemplate();
</script>
<?php
    if (($content['richtext'] == 1 || $_REQUEST['a'] == '4' || $_REQUEST['a'] == '72') && $use_editor == 1) {
	    if (is_array($richtexteditorIds)) {
		    foreach($richtexteditorIds as $editor=>$elements) {
			    // invoke OnRichTextEditorInit event
			    $evtOut = $modx->invokeEvent('OnRichTextEditorInit', array(
				    'editor' => $editor,
				    'elements' => $elements,
				    'options' => $richtexteditorOptions[$editor]
			    ));
			    if (is_array($evtOut))
				    echo implode('', $evtOut);
		    }
	    }
    }

function getDefaultTemplate()
{
	global $modx;

	switch($modx->config['auto_template_logic'])
	{
		case 'sibling':
			if(!isset($_GET['pid']) || empty($_GET['pid']))
			{
				$site_start = $modx->config['site_start'];
				$where = "sc.isfolder=0 AND sc.id!='{$site_start}'";
				$sibl = $modx->getDocumentChildren($_REQUEST['pid'], 1, 0, 'template', $where, 'menuindex', 'ASC', 1);
				if(isset($sibl[0]['template']) && $sibl[0]['template']!=='') $default_template = $sibl[0]['template'];
			}
			else
			{
				$sibl = $modx->getDocumentChildren($_REQUEST['pid'], 1, 0, 'template', 'isfolder=0', 'menuindex', 'ASC', 1);
				if(isset($sibl[0]['template']) && $sibl[0]['template']!=='') $default_template = $sibl[0]['template'];
				else
				{
					$sibl = $modx->getDocumentChildren($_REQUEST['pid'], 0, 0, 'template', 'isfolder=0', 'menuindex', 'ASC', 1);
					if(isset($sibl[0]['template']) && $sibl[0]['template']!=='') $default_template = $sibl[0]['template'];
				}
			}
			break;
		case 'parent':
			if (isset($_REQUEST['pid']) && !empty($_REQUEST['pid']))
			{
				$parent = $modx->getPageInfo($_REQUEST['pid'], 0, 'template');
				if(isset($parent['template'])) $default_template = $parent['template'];
			}
			break;
		case 'system':
		default: // default_template is already set
			$default_template = $modx->config['default_template'];
	}
	if(!isset($default_template)) $default_template = $modx->config['default_template']; // default_template is already set

	return $default_template;
}