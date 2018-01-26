//**************** Library *************************************************
if (navigator.userAgent.indexOf("Safari") > 0) {
  isSafari = true;
  isMoz = false;
  isIE = false;
} else if (navigator.product == "Gecko") {
  isSafari = false;
  isMoz = true;
  isIE = false;
} else {
  isSafari = false;
  isMoz = false;
  isIE = true;
}

function addKeyListener(element, listener) {
  if (isSafari)
    element.addEventListener("keydown",listener,false);
  else if (isMoz)
    element.addEventListener("keypress",listener,false);
  else
    element.attachEvent("onkeydown",listener);
}

var request = false;

function makeXHTTPRequest(url, params, processRequestChange) {
	if (!request && window.XMLHttpRequest) {
		request = new XMLHttpRequest();
	} else if (!request) {
		request = new ActiveXObject("Microsoft.XMLHTTP");
	}
    request.onreadystatechange = processRequestChange;
    request.open("POST", url, true);
	request.setRequestHeader("Content-type", "application/x-www-form-urlencoded;charset=UTF-8");
	request.setRequestHeader("Content-length", params.length);
	request.setRequestHeader("Connection", "close");
    request.send(params);
}

//******************** QU Code ***************************

var lastSearchQuery = "";
var timeoutId = false;

function getQuery() {
	return document.SubmitNewTicket.subject.value + " " + document.SubmitNewTicket.body.value;
}

function querySuggestions() {
	if (lastSearchQuery != getQuery() && getQuery().length > 5) {
		lastSearchQuery = getQuery();
		makeXHTTPRequest(document.SubmitNewTicket.applicationURL.value + "server/kb_search.php", "query="+escape(lastSearchQuery), updateSuggestions);
		
	}
}

function updateSuggestions() {
	if(request.readyState == 4) {
		if (request.status == 200) {
			showSuggestions(request.responseText);
		} else {
			hideSuggestions();
	    	alert("There was a problem retrieving the XML data:\n" + request.statusText);
	    }
	}
}

function showSuggestions(html) {
	if (html.length > 0) {
		var suggestionsContainer = document.getElementById('suggestionsContainer');
		var suggestionsBlock = document.getElementById('suggestionsBlock');
		suggestionsContainer.innerHTML = html;
		suggestionsBlock.style.display = "block";
	} else {
		hideSuggestions();
	}
}

function hideSuggestions() {
	var suggestionsBlock = document.getElementById('suggestionsBlock');
	var suggestionsContainer = document.getElementById('suggestionsContainer');
	suggestionsContainer.innerHTML = "";
	suggestionsBlock.style.display = "none";
}

function scheduleKBSearch() {
	if (timeoutId) {
         window.clearTimeout(timeoutId);
	}
    timeoutId = window.setTimeout(querySuggestions, 1000);
}
        
function startListeners() {
	addKeyListener(document.SubmitNewTicket.subject, scheduleKBSearch);
	addKeyListener(document.SubmitNewTicket.body, scheduleKBSearch);
}
