<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
?>
	<style>
		html, body { margin: 0; padding: 0; width: 100%; height: 100% }
		body { position: relative }
		#mainMenu, #tree, #main { position: absolute }
		#mainMenu iframe, #tree iframe, #main iframe, #mask_resizer { position: absolute; width: 100%; height: 100%; }
		#mainMenu { height: 70px; width: 100%;}
		#tree { width: <?php echo $modx->config['manager_tree_width'];?>px; top: 70px; left: 0; bottom: 0; }
		#main { top: 70px; left: <?php echo $modx->config['manager_tree_width'];?>px; right: 0; bottom: 0; }
		#resizer { position: absolute; top: 70px; bottom: 0; left: <?php echo $modx->config['manager_tree_width'];?>px; width: 12px; cursor: col-resize; z-index: 999 }
		#resizer #hideMenu {
			display: block;
			margin-top: 5px;
			margin-left: -8px;
			cursor: pointer;
			background: transparent url("media/style/[(manager_theme)]/images/icons/application_side_contract.png") no-repeat !important;
			width: 16px;
			height: 16px;
		}
		#resizer2 { position: absolute; top: 65px; right: 50px; width: 3px; z-index: 999;}
		#resizer2 #hideTopMenu {display:block;
			margin-top:-4px;
			cursor:pointer;
			background:transparent url(media/style/[(manager_theme)]/images/icons/application_get.png)!important;
			width:16px;
			height:16px;
		}
	</style>
    <div id="resizer">
        <a id="hideMenu" onclick="mainMenu.toggleTreeFrame();"></a>
    </div>
    <div id="resizer2">
        <a id="hideTopMenu" onclick="mainMenu.toggleMenuFrame();"></a>
    </div>
    <div id="mainMenu">
	    [+iframe_mainMenu+]
    </div>
    <div id="tree">
        [+iframe_tree+]
    </div>

    <div id="main">
	    [+iframe_main+]
    </div>

    <script language="JavaScript" type="text/javascript">
        var _startY = 70;
        var _dragElement;
        var _oldZIndex = 999;
        var _left;
        var mask = document.createElement('div');
        mask.id = 'mask_resizer';
        mask.style.zIndex = _oldZIndex;

        InitDragDrop();

        function InitDragDrop() {
            document.getElementById('resizer').onmousedown = OnMouseDown;
            document.getElementById('resizer').onmouseup = OnMouseUp
        }

        function OnMouseDown(e) {
            if (e == null) e = window.event;
            _dragElement = e.target != null ? e.target : e.srcElement;
            if ((e.button == 1 && window.event != null || e.button == 0) && _dragElement.id == 'resizer') {
                _oldZIndex = _dragElement.style.zIndex;
                _dragElement.style.zIndex = 10000;
                _dragElement.style.background = '#444';
                document.body.appendChild(mask);
                document.onmousemove = OnMouseMove;
                document.body.focus();
                document.onselectstart = function () {
                    return false
                };
                _dragElement.ondragstart = function () {
                    return false
                };
                return false
            }
        }

        function ExtractNumber(value) {
            var n = parseInt(value);
            return n == null || isNaN(n) ? 0 : n
        }

        function OnMouseMove(e) {
            if (e == null) var e = window.event;
            _dragElement.style.left = e.clientX + 'px';
            _dragElement.style.top = _startY + 'px';
            document.getElementById('tree').style.width = e.clientX + 'px';
            document.getElementById('main').style.left = e.clientX + 'px'
        }

        function OnMouseUp(e) {
            if (_dragElement != null) {
                _dragElement.style.zIndex = _oldZIndex;
                _dragElement.style.background = 'transparent';
                _dragElement.ondragstart = null;
                _dragElement = null;
                document.body.removeChild(mask);
                document.onmousemove = null;
                document.onselectstart = null
            }
        }
        
        //save scrollPosition
        function getQueryVariable(variable, query) {
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                if (decodeURIComponent(pair[0]) == variable) {
                    return decodeURIComponent(pair[1]);
                }
            }
        }

        function scrollWork() {
            var frm = document.getElementById("mainframe").contentWindow;
            currentPageY = localStorage.getItem('page_y');
            pageUrl = localStorage.getItem('page_url');
            if (currentPageY === undefined) {
                localStorage.setItem('page_y', 0);
            }
            if (pageUrl === null) {
                pageUrl = frm.location.search.substring(1);
            }
            if ( getQueryVariable('a', pageUrl) == getQueryVariable('a', frm.location.search.substring(1)) ) {
                if ( getQueryVariable('id', pageUrl) == getQueryVariable('id', frm.location.search.substring(1)) ){
                    frm.scrollTo(0,currentPageY);
                }
            }

            frm.onscroll = function(){
                if (frm.pageYOffset > 0) {
                    localStorage.setItem('page_y', frm.pageYOffset);
                    localStorage.setItem('page_url', frm.location.search.substring(1));
                }
            }        
        }
    </script>