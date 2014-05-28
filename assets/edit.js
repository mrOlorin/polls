function edit() {

	var _self = this;
	var _form_selector = '#poll';
	var _question_tpl;
	var _answer_tpl;
	var _question_num = 1;

	this.Init = function() {
		// Клон вопроса и ответа. Их клоны будут добавляться. Клоны клонов, да.
		_question_tpl = $('fieldset.question:last', _form_selector).clone(true);
		_question_tpl.find('[name^="question_id"], [name^="answer_id"]').remove();
		_question_tpl.find(':text').val('').removeAttr('checked');
		_answer_tpl = _question_tpl.find('.answers>p:last').clone(true);

		_self.get_last_id();
		_self.bind_events();
	};

	this.get_last_id = function(i) {
		i = i || _question_num || 0;
		while(true) {
			if(0 === $('[name="question[' + i + ']"]', _form_selector).length) {
				_question_num = i;
				return _question_num;
			}
			i++;
		};
	};

	this.bind_events = function() {
		$('#add_question', _form_selector).bind('click', function(){
			var new_question = _question_tpl.clone(true);
			if(0 !== $('[name="question[' + _question_num + '"]').length) {
				_self.get_last_id(_question_num);
			};
			new_question.find('[name^="question["]').attr('name', 'question[' + _question_num + ']');
			new_question.find('[name^="question_type["]').attr('name', 'question_type[' + _question_num + ']');
			new_question.find('[name^="required["]').attr('name', 'required[' + _question_num + ']');
			new_question.find('[name^="answer["]').attr('name', 'answer[' + _question_num + '][]');
			_question_num++;
			$('#questions', _form_selector).append(new_question);
		});

		$('#questions', _form_selector).on('click', '.add_answer', function(){
			var new_answer = _answer_tpl.clone(true);
			new_answer.find('[type="text"]').val('');
			$(this).prev('.answers').append(new_answer);
		});

		$('#questions', _form_selector).on('click', '.delete_answer', function(){
			_self.delete_answer($(this).parent());
		});
		
		$('#questions', _form_selector).on('click', '.delete_question', function(){
			_self.delete_question($(this).parent());
		});
	};

	this.delete_question = function(question) {
		var question_id = question.find('[name^="question_id"]').val();
		question.remove();
		if('undefined' !== typeof(question_id)) {
			var input = $('<input>').attr('type', 'hidden').attr('name', 'delete_question_id[]').val(question_id);
			$(_form_selector).append($(input));
		}
	};
	
	this.delete_answer = function(answer) {
		var answer_id = answer.find('[name^="answer_id"]').val();
		answer.remove();
		if('undefined' !== typeof(answer_id)) {
			var input = $('<input>').attr('type', 'hidden').attr('name', 'delete_answer_id[]').val(answer_id);
			$(_form_selector).append($(input));
		}
	};

	_self.Init();
}