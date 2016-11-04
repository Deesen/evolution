/*
 * Small script to keep session alive in MODX
 */
var lockedResources = {};
var intervalSeconds = 10;

function keepMeAlive() {
    consoleLog('keepMeAlive');
    var sessionJSON = new Ajax('index.php?a=998&o=' + Math.random(), {
        method: 'post',
        data: {
            'tok':document.getElementById('sessTokenInput').value,
            'lockedResources':JSON.stringify(lockedResources)
        },
        onComplete: function(sessionResponse) {
            resp = Json.evaluate(sessionResponse);
            if(resp.status != 'ok') {
                window.location.href = 'index.php?a=8';
            } else {
                cleanNulledResources();
            }
        }
    }).request();
}

function maintainLockedResources() {
    cleanExpiredResources();
    setResourcesOpen();
}

function cleanNulledResources() {
    for (var type in lockedResources) {
        if (!lockedResources.hasOwnProperty(type)) continue;
        for (var lockedId in lockedResources[type]) {
            if (!lockedResources[type].hasOwnProperty(lockedId)) continue;
            if(lockedResources[type][lockedId] == null) delete lockedResources[type][lockedId]; 
        }
        if(!Object.keys(lockedResources[type]).length)  delete lockedResources[type];
    }
}

function cleanExpiredResources() {
    var time = Math.floor(Date.now() / 1000);

    for (var type in lockedResources) {
        if (!lockedResources.hasOwnProperty(type)) continue;
        for (var lockedId in lockedResources[type]) {
            if (!lockedResources[type].hasOwnProperty(lockedId)) continue;
            lockedTime = lockedResources[type][lockedId];
            if ((lockedTime + intervalSeconds) < time) unlockResource(type, lockedId);
        }
    }
}

function setResourcesOpen() {
    if(Object.keys(lockedResources).length === 0 && lockedResources.constructor === Object) {
        top.resourcesOpen = 0;
    } else {
        top.resourcesOpen = 0;
        for (var type in lockedResources) {
            if (!lockedResources.hasOwnProperty(type)) continue;
            top.resourcesOpen += Object.keys(lockedResources[type]).length;
        }
    }
}

function lockResource(type, id) {
    if (!lockedResources.hasOwnProperty(type)) lockedResources[type] = {};
    var time = Math.floor(Date.now() / 1000);
    lockedResources[type][id] = time;
    setResourcesOpen();
    consoleLog('lock');
}
function unlockResource(type, id, instantly) {
    lockedResources[type][id] = null;
    setResourcesOpen();
    consoleLog('unlock');
    if(instantly) keepMeAlive();
}
function consoleLog(param) {
    if(top.resourcesOpen) console.log(param, top.resourcesOpen, lockedResources);
}

window.setInterval("keepMeAlive()", 1000 * intervalSeconds);
window.setInterval("maintainLockedResources()", 1000 * 1);
