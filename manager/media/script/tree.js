var modx = [+modx_params+];

window.addEvent('load', function(){
    resizeTree();
    restoreTree();
    window.addEvent('resize', resizeTree);
});

// preload images
var i = new Image(18,18);
i.src=modx.style.tree_page;
i = new Image(18,18);
i.src=modx.style.tree_globe;
i = new Image(18,18);
i.src=modx.style.tree_minusnode;
i = new Image(18,18);
i.src=modx.style.tree_plusnode;
i = new Image(18,18);
i.src=modx.style.tree_folderopen;
i = new Image(18,18);
i.src=modx.style.tree_folder;


var rpcNode = null;
var ca = "open";
var selectedObject = 0;
var selectedObjectDeleted = 0;
var selectedObjectName = "";
var _rc = 0; // added to fix onclick body event from closing ctx menu

[+openedArray+]

    // return window dimensions in array
    function getWindowDimension() {
        var width  = 0;
        var height = 0;

        if ( typeof( window.innerWidth ) == 'number' ){
            width  = window.innerWidth;
            height = window.innerHeight;
        }else if ( document.documentElement &&
                 ( document.documentElement.clientWidth ||
                   document.documentElement.clientHeight ) ){
            width  = document.documentElement.clientWidth;
            height = document.documentElement.clientHeight;
        }
        else if ( document.body &&
                ( document.body.clientWidth || document.body.clientHeight ) ){
            width  = document.body.clientWidth;
            height = document.body.clientHeight;
        }

        return {'width':width,'height':height};
    }

    function resizeTree() {

        // get window width/height
        var win = getWindowDimension();

        // set tree height
        var tree = $('treeHolder');
        var tmnu = $('treeMenu');
        tree.style.width = (win['width']-20)+'px';
        tree.style.height = (win['height']-tree.offsetTop-6)+'px';
        tree.style.overflow = 'auto';
    }

    function getScrollY() {
      var scrOfY = 0;
      if( typeof( window.pageYOffset ) == 'number' ) {
        //Netscape compliant
        scrOfY = window.pageYOffset;
      } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
      } else if( document.documentElement &&
          (document.documentElement.scrollTop ) ) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
      }
      return scrOfY;
    }

    function showPopup(id,title,e){
        var x, y;
        var mnu = $('mx_contextmenu');
        var bodyHeight = parseInt(document.body.offsetHeight);
        x = e.clientX>0 ? e.clientX:e.pageX;
        y = e.clientY>0 ? e.clientY:e.pageY;
        y = getScrollY()+(y/2);
        if (y+mnu.offsetHeight > bodyHeight) {
            // make sure context menu is within frame
            y = y - ((y+mnu.offsetHeight)-bodyHeight+5);
        }
        itemToChange=id;
        selectedObjectName= title;
        dopopup(x+5,y);
        e.cancelBubble=true;
        return false;
    }

    function dopopup(x,y) {
        if(selectedObjectName.length>20) {
            selectedObjectName = selectedObjectName.substr(0, 20) + "...";
        }
        var h,context = $('mx_contextmenu');
        context.style.left= x+modx.param.contextOffset+"px"; //offset menu to the left if rtl is selected
        context.style.top = y+"px";
        var elm = $("nameHolder");
        elm.innerHTML = selectedObjectName;

        context.style.visibility = 'visible';
        _rc = 1;
        setTimeout("_rc = 0;",100);
}

function hideMenu() {
    if (_rc) return false;
    $('mx_contextmenu').style.visibility = 'hidden';
}

function toggleNode(node,indent,parent,expandAll,privatenode) {
    privatenode = (!privatenode || privatenode == '0') ?  '0' : '1';
    rpcNode = $(node.parentNode.lastChild);

    var rpcNodeText;
    var loadText = modx.lang.loading_doc_tree;

    var signImg = document.getElementById("s"+parent);
    var folderImg = document.getElementById("f"+parent);

    if (rpcNode.style.display != 'block') {
    // expand
    if(signImg && signImg.src.indexOf(modx.style.tree_plusnode)>-1) {
    signImg.src = modx.style.tree_minusnode;
    folderImg.src = (privatenode == '0') ? modx.style.tree_folderopen : modx.style.tree_folderopen_secure;
}

    rpcNodeText = rpcNode.innerHTML;

    if (rpcNodeText=="" || rpcNodeText.indexOf(loadText)>0) {
    var i, spacer='';
    for(i=0;i<=indent+1;i++) spacer+='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    rpcNode.style.display = 'block';
    //Jeroen set opened
    openedArray[parent] = 1 ;
    //Raymond:added getFolderState()
    var folderState = getFolderState();
    rpcNode.innerHTML = "<span class='emptyNode' style='white-space:nowrap;'>"+spacer+"&nbsp;&nbsp;&nbsp;"+loadText+"...<\/span>";
    new Ajax('index.php?a=1&f=nodes&indent='+indent+'&parent='+parent+'&expandAll='+expandAll+folderState, {method: 'get',onComplete:rpcLoadData}).request();
} else {
    rpcNode.style.display = 'block';
    //Jeroen set opened
    openedArray[parent] = 1 ;
}
}
    else {
    // collapse
    if(signImg && signImg.src.indexOf(modx.style.tree_minusnode)>-1) {
    signImg.src = modx.style.tree_plusnode;
    folderImg.src = (privatenode == '0') ? modx.style.tree_folder : modx.style.tree_folder_secure;
}
    //rpcNode.innerHTML = '';
    rpcNode.style.display = 'none';
    openedArray[parent] = 0 ;
}
}

function rpcLoadData(response) {
    if(rpcNode != null){
    rpcNode.innerHTML = typeof response=='object' ? response.responseText : response ;
    rpcNode.style.display = 'block';
    rpcNode.loaded = true;
    var elm = top.mainMenu.$("buildText");
    if (elm) {
    elm.innerHTML = "";
    elm.style.display = 'none';
}
    // check if bin is full
    if(rpcNode.id=='treeRoot') {
    var e = $('binFull');
    if(e) showBinFull();
    else showBinEmpty();
}

    // check if our payload contains the login form :)
    e = $('mx_loginbox');
    if(e) {
    // yep! the seession has timed out
    rpcNode.innerHTML = '';
    top.location = 'index.php';
}
}
}

function expandTree() {
    rpcNode = $('treeRoot');
    new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=1', {method: 'get',onComplete:rpcLoadData}).request();
}

function collapseTree() {
    rpcNode = $('treeRoot');
    new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=0', {method: 'get',onComplete:rpcLoadData}).request();
}

// new function used in body onload
function restoreTree() {
    rpcNode = $('treeRoot');
    new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=2', {method: 'get',onComplete:rpcLoadData}).request();
}

function setSelected(elSel) {
    var all = document.getElementsByTagName( "SPAN" );
    var l = all.length;

    for ( var i = 0; i < l; i++ ) {
    el = all[i];
    cn = el.className;
    if (cn=="treeNodeSelected") {
    el.className="treeNode";
}
}
    elSel.className="treeNodeSelected";
}

function setHoverClass(el, dir) {
    if(el.className!="treeNodeSelected") {
    if(dir==1) {
    el.className="treeNodeHover";
} else {
    el.className="treeNode";
}
}
}

// set Context Node State
function setCNS(n, b) {
    if(b==1) {
    n.style.backgroundColor="beige";
} else {
    n.style.backgroundColor="";
}
}

function updateTree() {
    rpcNode = $('treeRoot');
    treeParams = 'a=1&f=nodes&indent=1&parent=0&expandAll=2&dt=' + document.sortFrm.dt.value + '&tree_sortby=' + document.sortFrm.sortby.value + '&tree_sortdir=' + document.sortFrm.sortdir.value + '&tree_nodename=' + document.sortFrm.nodename.value;
    new Ajax('index.php?'+treeParams, {method: 'get',onComplete:rpcLoadData}).request();
}

function emptyTrash() {
    if(confirm(modx.lang.confirm_empty_trash)==true) {
    top.main.document.location.href="index.php?a=64";
}
}

currSorterState="none";
function showSorter() {
    if(currSorterState=="none") {
    currSorterState="block";
    document.getElementById('floater').style.display=currSorterState;
} else {
    currSorterState="none";
    document.getElementById('floater').style.display=currSorterState;
}
}

function treeAction(id, name, treedisp_children) {
    if(ca=="move") {
    try {
    parent.main.setMoveValue(id, name);
} catch(oException) {
    alert(modx.lang.unable_set_parent);
}
}
    if(ca=="open" || ca=="") {
    if(id==0) {
    // do nothing?
    parent.main.location.href="index.php?a=2";
} else {
    // parent.main.location.href="index.php?a=3&id=" + id + getFolderState(); //just added the getvar &opened=
    if(treedisp_children==0) {
    parent.main.location.href="index.php?a=3&id=" + id + getFolderState();
} else {
    parent.main.location.href="index.php?a="+modx.param.tree_page_click+"&id=" + id; // edit as default action
}
}
}
    if(ca=="parent") {
    try {
    parent.main.setParent(id, name);
} catch(oException) {
    alert(modx.lang.unable_set_parent);
}
}
    if(ca=="link") {
    try {
    parent.main.setLink(id);
} catch(oException) {
    alert(modx.lang.unable_set_link);
}
}
}

//Raymond: added getFolderState,saveFolderState
function getFolderState(){
    if (openedArray != [0]) {
    oarray = "&opened=";
    for (key in openedArray) {
    if (openedArray[key] == 1) {
    oarray += key+"|";
}
}
} else {
    oarray = "&opened=";
}
    return oarray;
}
function saveFolderState() {
    var folderState = getFolderState();
    new Ajax('index.php?a=1&f=nodes&savestateonly=1'+folderState, {method: 'get'}).request();
}

// show state of recycle bin
function showBinFull() {
    var a = $('Button10');
    var title = modx.lang.empty_recycle_bin;
    if (a) {
    if(!a.setAttribute) a.title = title;
    else a.setAttribute('title',title);
    a.innerHTML = modx.style.empty_recycle_bin;
    a.className = 'treeButton';
    a.onclick = emptyTrash;
}
}

function showBinEmpty() {
    var a = $('Button10');
    var title = modx.lang.empty_recycle_bin_empty;
    if (a) {
    if(!a.setAttribute) a.title = title;
    else a.setAttribute('title',title);
    a.innerHTML = modx.style.empty_recycle_bin_empty;
    a.className = 'treeButtonDisabled';
    a.onclick = '';
}
}