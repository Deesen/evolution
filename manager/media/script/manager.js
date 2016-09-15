jQuery(function(){
    jQuery(window).on('beforeunload',function(){
        jQuery('#actions').fadeOut(100);
    });
});

window.addEvent('load', document_onload);
window.addEvent('beforeunload', document_onunload);

function document_onload() {
    stopWorker();
    hideLoader();
    [+doRefresh+]
}

function reset_path(elementName) {
    document.getElementById(elementName).value = document.getElementById('default_' + elementName).innerHTML;
}

var dontShowWorker = false;
function document_onunload() {
    if(!dontShowWorker) {
        top.mainMenu.work();
    }
}

// set tree to default action.
if (parent.tree) parent.tree.ca = "open";

// call the updateMail function, updates mail notification in top navigation
if (top.mainMenu) {
    if(top.mainMenu.updateMail) {
        top.mainMenu.updateMail(true);
    }
}

function stopWorker() {
    try {
        parent.mainMenu.stopWork();
    } catch(oException) {
        ww = window.setTimeout('stopWorker()',500);
    }
}

function doRefresh(r) {
    try {
        rr = r;
        top.mainMenu.startrefresh(rr);
    } catch(oException) {
        vv = window.setTimeout('doRefresh()',1000);
    }
}
var documentDirty=false;

function checkDirt(evt) {
    if(documentDirty==true) {
    var message = "[+lang.warning_not_saved+]";
    if (typeof evt == 'undefined') {
        evt = window.event;
    }
    if (evt) {
        evt.returnValue = message;
    }
        return message;
    }
}

function saveWait(fName) {
    document.getElementById("savingMessage").innerHTML = "[+lang.saving+]";
    for(i = 0; i < document.forms[fName].elements.length; i++) {
        document.forms[fName].elements[i].disabled='disabled';
}
}

var managerPath = "";

function hideLoader() {
    document.getElementById('preLoader').style.display = "none";
}

hideL = window.setTimeout("hideLoader()", 1500);

// add the 'unsaved changes' warning event handler
if( window.addEventListener ) {
    window.addEventListener('beforeunload',checkDirt,false);
} else if ( window.attachEvent ) {
    window.attachEvent('onbeforeunload',checkDirt);
} else {
    window.onbeforeunload = checkDirt;
}