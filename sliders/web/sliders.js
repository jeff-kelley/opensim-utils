/* Original slider : John Judnich http://judnich.github.io/Kosmos/ */


_sliderMouseDown = false;


function jsMain() {	// Called from body onload

	$("#name").change(function(){
		applicationInit ();
	});

	$(".button").mousedown(function(e) {
		buttonEvent($(this));
	});

	$(".slider").mousedown(function(e) {
		_sliderMouseDown = true;
		var offX  = (e.offsetX || e.clientX - $(e.target).offset().left);
		var offY  = (e.offsetY || e.clientY - $(e.target).offset().top);
		sliderEvent($(this), offX, offY);
	});

	$(".slider").mouseup(function(event) {
		_sliderMouseDown = false;
	});

	$(".slider").mousemove(function(e) {
		if (_sliderMouseDown) {
			var offX  = (e.offsetX || e.clientX - $(e.target).offset().left);
			var offY  = (e.offsetY || e.clientY - $(e.target).offset().top);
			sliderEvent($(this), offX, offY);
		}
	});

	$(document).mouseup(function(event) {
		_sliderMouseDown = false;
	});

	applicationInit ();
}




function buttonEvent(target, x, y) {
	var id = target.attr('id');
	var active = target.hasClass('lit');

	if (active)
		target.removeClass('lit');
	else
		target.addClass('lit');

	applicationUpdate (id, !active);
}


function sliderEvent(target, x, y) {
	var id = target.attr('id');
	var value = 1.0 - ((y-0) / (target.height()+0));

	if (value > 1) value = 1;
	if (value < 0) value = 0;

	$('#'+id+'bar').height( ((1.0 - value) * 100.0) + '%');
	$('#'+id+'txt').text(value.toFixed(3));

	applicationUpdate (id, value.toFixed(3));
}