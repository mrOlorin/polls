function admin() {

	var _self = this;
	
	this.Init = function() {
		_self.bind_events();
	};

	this.bind_events = function() {
		$('.delete').bind('click', function() {
			if(!confirm("Удалить опрос? Это так же удалит все результаты.")) {
				return false;
			}
		});
	};
	
	this.form_toggle = function() {
		$('#filter-form').toggle('fast', function() {
			$('.filter-toggle').html(
				$(this).is(':visible') ? '(скрыть ↑)' : '(показать ↓)'
			);
		});
	};

	_self.Init();
}