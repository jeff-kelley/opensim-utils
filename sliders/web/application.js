
// Called from slider.js

function applicationInit () {
	var targetNam = $("#name").val();
	var targetUrl = Resolve (targetNam);
	$("#url").val(targetUrl);
}


function applicationUpdate (id, value) {
	var msg = id+'='+value;
 	$("#debug").html(msg);
	Message ('', msg);
}


//
// Calling OpenCom's resolver
//

var registerUri = 'http://grid.pescadoo.net/opencom/register.php';
var resolverUri = 'http://grid.pescadoo.net/opencom/resolver.php';
var messagerUri = 'http://grid.pescadoo.net/opencom/messager.php';

function Resolve(name) {
	var queryUrl = resolverUri
		+ '?X-SecondLife-Region=required'
		+ '&X-SecondLife-Shard=required'
		+ '&name='+name
		+ '&wanturi=1';

	var resolverAnswer;
	
	jQuery.ajax({
		url: queryUrl,
		async: false,
		success: function (result) {
			resolverAnswer = result;
		}
	});

	var lines = resolverAnswer.split("\n");
	var targetUrl = lines[1]; // Answer in line 2
	if (targetUrl == '') targetUrl = 'Not Found';
	return targetUrl;
}


//
// Sending the message
//

var throttle = 20; // Milliseconds
var _last_time;


function Message(url, data) {

	// Throttle
	var date = new Date();
	var now_ms = date.getTime(); 
	var elapsed = now_ms - _last_time;
	if (elapsed < throttle) return;
	_last_time = now_ms;

	var targetUrl = $("#url").val();
	$.getJSON (targetUrl+"?"+data);
}