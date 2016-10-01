<?php

$tpe->setElementTpe('mutate', 'class', 'content form-horizontal');

$tpe->setElement('grid.2col', 'mutate.documentPane.tabGeneral.grid2_1'); // array('blockTpl=>array('block2'=>array('outerTpl'=>'form.section.container'))

// $tpe->setElementAttr('mutate.documentPane.tabGeneral.section1', array('label'=>$_lang['settings_templvars']));
$tpe->setElementTpe('mutate.documentPane.tabGeneral.section1', array('tpl'=>'form.section'));

$tpe->moveElement('mutate.documentPane.tabGeneral.section1', 'mutate.documentPane.tabGeneral.grid2_1');
$tpe->setElementTpe('mutate.documentPane.tabGeneral.grid2_1.section1', array('pos'=>'block2'));

$tpe->moveElement('mutate.documentPane.tabGeneral.section2', 'mutate.documentPane.tabGeneral.grid2_1');
$tpe->setElementTpe('mutate.documentPane.tabGeneral.grid2_1.section2', array('pos'=>'block1'));

$tpe->setElementOrder('mutate.documentPane.tabGeneral.section3', 99); // Move TV-section to end

// Add additional Bootstrap-Collapse mark-up as block-template (grids have target-blocks, these can optionally be wrapped in an outer-template)
// $tpe->setElementTpe('mutate.documentPane.tabGeneral.grid2_1', 'blockTpl', array('block2'=>array('outerTpl'=>'form.section.container')));

// LEAVE section4 COLLAPSED (remove "in" for 'collapsed', set in MODxBSE/engine.php via setTypeDefaults('section'...) )
// $tpe->setElementTpe('userform.pane1.tab2.grid2_1.section4', 'collapsed', '');

?>
[[mgrTpl?
	&get=`element`
	&element=`body`
]]