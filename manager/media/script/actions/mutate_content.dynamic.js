var modx = [+modx_params+];

window.addEvent('domready', function(){
    $$('img[src=modx.style.icons_tooltip_over]').each(function(help_img) {
        help_img.removeProperty('onclick');
        help_img.removeProperty('onmouseover');
        help_img.removeProperty('onmouseout');
        help_img.setProperty('title', help_img.getProperty('alt') );
        help_img.setProperty('class', 'tooltip' );
        if (window.ie) help_img.removeProperty('alt');
    });
    new Tips($$('.tooltip'),{className:'custom'} );
});

// save tree folder state
if (parent.tree) parent.tree.saveFolderState();

function changestate(element) {
    currval = eval(element).value;
    if (currval==1) {
        eval(element).value=0;
    } else {
        eval(element).value=1;
    }
    documentDirty=true;
}

function deletedocument() {
    if (confirm(modx.lang.confirm_delete_resource)==true) {
        document.location.href="index.php?id=" + document.mutate.id.value + modx.param.add_path;
    }
}

function duplicatedocument(){
    if(confirm(modx.lang.confirm_resource_duplicate)==true) {
        document.location.href="index.php?id="+modx.param.id+"&a=94" + modx.param.add_path;
    }
}

var allowParentSelection = false;
var allowLinkSelection = false;

function enableLinkSelection(b) {
    parent.tree.ca = "link";
    var closed = modx.style.tree_folder;
    var opened = modx.style.icons_set_parent;
    if (b) {
        document.images["llock"].src = opened;
        allowLinkSelection = true;
    }
    else {
        document.images["llock"].src = closed;
        allowLinkSelection = false;
    }
}

function setLink(lId) {
    if (!allowLinkSelection) {
        window.location.href="index.php?a=3&id="+lId+ modx.param.add_path;
        return;
    }
    else {
        documentDirty=true;
        document.mutate.ta.value=lId;
    }
}

function enableParentSelection(b) {
    parent.tree.ca = "parent";
    var closed = modx.style.tree_folder;
    var opened = modx.style.icons_set_parent;
    if (b) {
        document.images["plock"].src = opened;
        allowParentSelection = true;
    }
    else {
        document.images["plock"].src = closed;
        allowParentSelection = false;
    }
}

function setParent(pId, pName) {
    if (!allowParentSelection) {
        window.location.href="index.php?a=3&id="+pId+ modx.param.add_path;
        return;
    }
    else {
        if (pId==0 || checkParentChildRelation(pId, pName)) {
            documentDirty=true;
            document.mutate.parent.value=pId;
            var elm = document.getElementById('parentName');
            if (elm) {
                elm.innerHTML = (pId + " (" + pName + ")");
            }
        }
    }
}

// check if the selected parent is a child of this document
function checkParentChildRelation(pId, pName) {
    var sp;
    var id = document.mutate.id.value;
    var tdoc = parent.tree.document;
    var pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pId) : tdoc.all["node"+pId];
    if (!pn) return;
    if (pn.id.substr(4)==id) {
        alert(modx.lang.illegal_parent_self);
        return;
    }
    else {
        while (pn.getAttribute("p")>0) {
            pId = pn.getAttribute("p");
            pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pId) : tdoc.all["node"+pId];
            if (pn.id.substr(4)==id) {
                alert(modx.lang.illegal_parent_child);
                return;
            }
        }
    }
    return true;
}

function clearKeywordSelection() {
    var opt = document.mutate.elements["keywords[]"].options;
    for (i = 0; i < opt.length; i++) {
        opt[i].selected = false;
    }
}

function clearMetatagSelection() {
    var opt = document.mutate.elements["metatags[]"].options;
    for (i = 0; i < opt.length; i++) {
        opt[i].selected = false;
    }
}

var curTemplate = -1;
var curTemplateIndex = 0;
function storeCurTemplate() {
    var dropTemplate = document.getElementById('template');
    if (dropTemplate) {
        for (var i=0; i<dropTemplate.length; i++) {
            if (dropTemplate[i].selected) {
                curTemplate = dropTemplate[i].value;
                curTemplateIndex = i;
            }
        }
    }
}
function templateWarning() {
    var dropTemplate = document.getElementById('template');
    if (dropTemplate) {
        for (var i=0; i<dropTemplate.length; i++) {
            if (dropTemplate[i].selected) {
                newTemplate = dropTemplate[i].value;
                break;
            }
        }
    }
    if (curTemplate == newTemplate) {return;}

    if(documentDirty===true) {
        if (confirm(modx.lang.tmplvar_change_template_msg)) {
            documentDirty=false;
            document.mutate.a.value = modx.param.action;
            document.mutate.newtemplate.value = newTemplate;
            document.mutate.submit();
        } else {
            dropTemplate[curTemplateIndex].selected = true;
        }
    }
    else {
        document.mutate.a.value = modx.param.action;
        document.mutate.newtemplate.value = newTemplate;
        document.mutate.submit();
    }
}

// Added for RTE selection
function changeRTE() {
    var whichEditor = document.getElementById('which_editor');
    if (whichEditor) {
        for (var i = 0; i < whichEditor.length; i++) {
            if (whichEditor[i].selected) {
                newEditor = whichEditor[i].value;
                break;
            }
        }
    }
    var dropTemplate = document.getElementById('template');
    if (dropTemplate) {
        for (var i = 0; i < dropTemplate.length; i++) {
            if (dropTemplate[i].selected) {
                newTemplate = dropTemplate[i].value;
                break;
            }
        }
    }

    documentDirty=false;
    document.mutate.a.value = modx.param.action;
    document.mutate.newtemplate.value = newTemplate;
    document.mutate.which_editor.value = newEditor;
    document.mutate.submit();
}

/**
 * Snippet properties
 */

var snippetParams = {};     // Snippet Params
var currentParams = {};     // Current Params
var lastsp, lastmod = {};

function showParameters(ctrl) {
    var c,p,df,cp;
    var ar,desc,value,key,dt;

    cp = {};
    currentParams = {}; // reset;

    if (ctrl) {
        f = ctrl.form;
    } else {
        f= document.forms['mutate'];
        ctrl = f.snippetlist;
    }

    // get display format
    df = "";//lastsp = ctrl.options[ctrl.selectedIndex].value;

    // load last modified param values
    if (lastmod[df]) cp = lastmod[df].split("&");
    for (p = 0; p < cp.length; p++) {
        cp[p]=(cp[p]+'').replace(/^\s|\s$/,""); // trim
        ar = cp[p].split("=");
        currentParams[ar[0]]=ar[1];
    }

    // setup parameters
    dp = (snippetParams[df]) ? snippetParams[df].split("&"):[""];
    if (dp) {
        t='<table width="100%" class="displayparams"><thead><tr><td width="50%">'+modx.lang.parameter+'<\/td><td width="50%">'+modx.lang.value+'<\/td><\/tr><\/thead>';
        for (p = 0; p < dp.length; p++) {
            dp[p]=(dp[p]+'').replace(/^\s|\s$/,""); // trim
            ar = dp[p].split("=");
            key = ar[0]     // param
            ar = (ar[1]+'').split(";");
            desc = ar[0];   // description
            dt = ar[1];     // data type
            value = decode((currentParams[key]) ? currentParams[key]:(dt=='list') ? ar[3] : (ar[2])? ar[2]:'');
            if (value!=currentParams[key]) currentParams[key] = value;
            value = (value+'').replace(/^\s|\s$/,""); // trim
            if (dt) {
                switch(dt) {
                    case 'int':
                        c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" \/>';
                        break;
                    case 'list':
                        c = '<select name="prop_'+key+'" height="1" style="width:168px" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">';
                        ls = (ar[2]+'').split(",");
                        if (currentParams[key]==ar[2]) currentParams[key] = ls[0]; // use first list item as default
                        for (i=0;i<ls.length;i++) {
                            c += '<option value="'+ls[i]+'"'+((ls[i]==value)? ' selected="selected"':'')+'>'+ls[i]+'<\/option>';
                        }
                        c += '<\/select>';
                        break;
                    default:  // string
                        c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" \/>';
                        break;

                }
                t +='<tr><td bgcolor="#FFFFFF" width="50%">'+desc+'<\/td><td bgcolor="#FFFFFF" width="50%">'+c+'<\/td><\/tr>';
            };
        }
        t+='<\/table>';
        td = (document.getElementById) ? document.getElementById('snippetparams'):document.all['snippetparams'];
        td.innerHTML = t;
    }
    implodeParameters();
}

function setParameter(key,dt,ctrl) {
    var v;
    if (!ctrl) return null;
    switch (dt) {
        case 'int':
            ctrl.value = parseInt(ctrl.value);
            if (isNaN(ctrl.value)) ctrl.value = 0;
            v = ctrl.value;
            break;
        case 'list':
            v = ctrl.options[ctrl.selectedIndex].value;
            break;
        default:
            v = ctrl.value+'';
            break;
    }
    currentParams[key] = v;
    implodeParameters();
}

function resetParameters() {
    document.mutate.params.value = "";
    lastmod[lastsp]="";
    showParameters();
}
// implode parameters
function implodeParameters() {
    var v, p, s = '';
    for (p in currentParams) {
        v = currentParams[p];
        if (v) s += '&'+p+'='+ encode(v);
    }
    //document.forms['mutate'].params.value = s;
    if (lastsp) lastmod[lastsp] = s;
}

function encode(s) {
    s = s+'';
    s = s.replace(/\=/g,'%3D'); // =
    s = s.replace(/\&/g,'%26'); // &
    return s;
}

function decode(s) {
    s = s+'';
    s = s.replace(/\%3D/g,'='); // =
    s = s.replace(/\%26/g,'&'); // &
    return s;
}