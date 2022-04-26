
function genericOnClick(info, tab) {

    var http = new XMLHttpRequest();
    var url = 'http://ХХ_HIDDEN_XX/memes/picture/add/';

    if (info.srcUrl) {
        var params = 'img=' + encodeURIComponent(info.srcUrl);
    } else {
        return;
    }

    http.open('POST', url, true);

    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onreadystatechange = function () {//Call a function when the state changes.
        if (http.readyState == 4 && http.status == 200) {
            alert(http.responseText);
        }
    }

    http.send(params);

}

function genericOnDelay(info, tab) {

    var http = new XMLHttpRequest();
    var url = 'http://ХХ_HIDDEN_XX/memes/picture/post/';

    if (info.srcUrl) {
        var params = 'img=' + encodeURIComponent(info.srcUrl);
    } else {
        return;
    }


    http.open('POST', url, true);

    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onreadystatechange = function () {//Call a function when the state changes.
        if (http.readyState == 4 && http.status == 200) {
            alert(http.responseText);
        }
    }

    http.send(params);

}

// Create one test item for each context type.
var contexts = ["selection", "image"];
for (var i = 0; i < contexts.length; i++) {
    var context = contexts[i];
    var title = "Отправить в ОЧЕРЕДЬ";
    var id = chrome.contextMenus.create({
        "title": title, "contexts": [context],
        "onclick": genericOnClick
    });

    title = "ОПУБЛИКОВАТЬ ПРЯМ ЩА";
    id = chrome.contextMenus.create({
        "title": title, "contexts": [context],
        "onclick": genericOnDelay
    });
}
