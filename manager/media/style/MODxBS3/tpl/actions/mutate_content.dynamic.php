<?php

// Set different template for stay-options to allow Bootstrap Buttons with jQuery
$tpe->setElementChildsTpe('stay', array('tpl'=>'action.select.option'));

$tpe->setElementTpe('mutate', 'class', 'content form-horizontal');

$tpe->setElement('grid.2col', 'mutate.documentPane.tabGeneral.grid2_1'); // array('blockTpl=>array('block2'=>array('outerTpl'=>'form.section.container'))

// $tpe->setElementAttr('mutate.documentPane.tabGeneral.section1', array('label'=>$_lang['settings_templvars']));
$tpe->setElementTpe('tg_section1', array('tpl'=>'form.section'));

$tpe->moveElement('tg_section1', 'grid2_1');
$tpe->setElementTpe('tg_section1', array('pos'=>'block2'));

$tpe->moveElement('tg_section2', 'grid2_1');
$tpe->setElementTpe('tg_section2', array('pos'=>'block1'));

$tpe->setElementOrder('tg_section3', 99); // Move TV-section to end

// Add additional Bootstrap-Collapse mark-up as block-template (grids have target-blocks, these can optionally be wrapped in an outer-template)
// $tpe->setElementTpe('mutate.documentPane.tabGeneral.grid2_1', 'blockTpl', array('block2'=>array('outerTpl'=>'form.section.container')));

// LEAVE section4 COLLAPSED (remove "in" for 'collapsed', set in MODxBSE/engine.php via setTypeDefaults('section'...) )
// $tpe->setElementTpe('userform.pane1.tab2.grid2_1.section4', 'collapsed', '');

?>
<div class="breadcrumbs-bar">
		<div class="row">
			<div class="col-xs-12">
				[+breadcrumbs+]
			</div>
		</div>
</div>

[[mgrTpl?
	&get=`elements`
	&element=`mutate`
]]