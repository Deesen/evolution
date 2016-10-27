/*
 * Small script to keep session alive in MODX
 */
var lockedResources = {};
var intervalSeconds = 10;

function keepMeAlive() {
    consoleLog('keepMeAlive');
    var sessionJSON = new Ajax('includes/session_keepalive.php?o=' + Math.random(), {
        method: 'post',
        data: {
            'tok':document.getElementById('sessTokenInput').value,
            'lockedResources':JSON.stringify(lockedResources)
        },
        onComplete: function(sessionResponse) {
            resp = Json.evaluate(sessionResponse);
            if(resp.status != 'ok') {
                window.location.href = 'index.php?a=8';
            }
        }
    }).request();
}

function maintainLockedResources() {
    cleanLockedResources();
    setResourcesOpen();
}

function cleanLockedResources() {
    var time = Math.floor(Date.now() / 1000);
    for (var lockedId in lockedResources) {
        if (!lockedResources.hasOwnProperty(lockedId)) continue;
        lockedTime = lockedResources[lockedId];
        if((lockedTime+intervalSeconds) < time) unlockResource(lockedId);
    }
}

function setResourcesOpen() {
    if(Object.keys(lockedResources).length === 0 && lockedResources.constructor === Object) {
        top.resourcesOpen = 0;
    } else {
        top.resourcesOpen = Object.keys(lockedResources).length;
    }
}

function lockResource(id) {
    var time = Math.floor(Date.now() / 1000);
    lockedResources[id] = time;
    setResourcesOpen();
    consoleLog('lock');
}
function unlockResource(id) {
    delete lockedResources[id];
    setResourcesOpen();
    consoleLog('unlock');
}
function consoleLog(param) {
    if(top.resourcesOpen) console.log(param, top.resourcesOpen, lockedResources);
}

window.setInterval("keepMeAlive()", 1000 * intervalSeconds);
window.setInterval("maintainLockedResources()", 1000 * 1);
