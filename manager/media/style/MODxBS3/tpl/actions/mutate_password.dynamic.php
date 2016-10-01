<?php
// NOT REQUIRED IN PRODUCTION - JUST FOR DEMO
$tpe
// Add additional Bootstrap-Collapse mark-up as block-template (grids have target-blocks, these can optionally be wrapped in an outer-template)
->setElementTpe('userform.pane1.tab2.grid2_1', 'blockTpl', array('block2'=>array('outerTpl'=>'form.section.container')))

// LEAVE section4 COLLAPSED (remove "in" for 'collapsed', set in MODxBSE/engine.php via setTypeDefaults('section'...) )
->setElementTpe('userform.pane1.tab2.grid2_1.section4', 'collapsed', '')


// EXAMPLE: Modify Action Buttons (also possible via custom action-templates)
// ->addButtonParam('main', 'Button1', 'onclick', "alert('Button1')")	// Add parameter to existing ones
// ->setButtonParam('main', 'Button5', 'onclick', "alert('Button5')")	// Overwrite parameters

/*  
// EXAMPLE FOR USE WITH ORIGINAL FORM
// Custom template or ManagerManager can modify matrix like this

// First add a new section (type 'section') with id 'section2' as child of 'userform' (needs parameter 'label') = full id/target is then 'userform.section2'
->addElement('section', 'section2', 'userform', array('label'=>'Example'))

// Now move element 'pass2' from original position as child of above created 'userform.section2'
->moveElement('userform.section1.pass2', 'userform.section2')
*/

?>
<div class="sectionBody">
    <p>[+lang.change_password_message+]</p>
    <br/>
</div>
[[mgrTpl?
    &get=`element`
    &element=`body`
]]
<script>
$(function () {
	$('[data-toggle="tooltip"]').tooltip()
});
</script>