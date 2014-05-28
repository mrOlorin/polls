function results() {

	var _self = this;
	
	this.Init = function() {
		_self.bind_events();
	};

	this.bind_events = function() {
		$('.filter-toggle').bind('click', function() {
			_self.form_toggle();
		});
	};
	
	this.form_toggle = function() {
		$('#filter-form').toggle('fast', function() {
			$('.filter-toggle').html(
				$(this).is(':visible')?'(скрыть ↑)':'(показать ↓)'
			);
		});
	}

	_self.Init();
}