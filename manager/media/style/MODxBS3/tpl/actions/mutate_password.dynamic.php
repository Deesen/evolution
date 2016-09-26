<?php
$tpe
// Add additional Bootstrap-Collapse mark-up
->addElementTpe('userform.pane1.tab2.grid2_1', 'blockTpl', array('block2'=>array('outerTpl'=>'form.section.container')))
// LEAVE section4 COLLAPSED (remove "in" for 'collapsed', set in MODxBSE/engine.php via setTypeDefaults('section'...) )
->addElementTpe('userform.pane1.tab2.grid2_1.section4', 'collapsed', '')

/*  
// TEST-EXAMPLE FOR ORIGINAL FORM
// Custom template or ManagerManager can modify matrix like this
// First add message and new section
->addElement('section', 'section2', 'userform', array('label'=>'Example'))
// Now move existing element to newly created section
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