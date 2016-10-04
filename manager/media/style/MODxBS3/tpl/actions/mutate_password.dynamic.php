<?php
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
    &element=`userform`
]]