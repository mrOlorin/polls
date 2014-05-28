<?php

namespace app\controllers;

use app\models\Poll;
use app\models\Question;
use app\models\Answer;
use app\models\PollMapper;

class AdminController extends \core\Controller
{

	private $_poll_mapper;
	private $_post_poll_args = array(
		'poll_name' => array(
			'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
			'flags' => FILTER_REQUIRE_SCALAR,
		),
		'id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'flags' => FILTER_REQUIRE_SCALAR,
		),
		'question_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'answer_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'question' => array(
			'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'answer' => array(
			'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'required' => array(
			'filter' => FILTER_VALIDATE_BOOLEAN,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'question_type' => array(
			'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'delete_answer_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
		'delete_question_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'flags' => FILTER_REQUIRE_ARRAY,
		),
	);
	private $_poll_statuses = ['active', 'draft', 'closed'];
	
	public function __construct()
	{
		parent::__construct();
		$this->_poll_mapper = new PollMapper();
	}

	public function indexAction()
	{
		$status = filter_input(INPUT_GET, 'status');
		if(!in_array($status, $this->_poll_statuses)) {
			$status = 'active';
		}
		
		$polls = $this->_poll_mapper->getPolls(['status' => $status]);
		$this->view->polls = $polls;
	}

	public function resultsAction()
	{
		$pollId = (int)filter_input(INPUT_GET, 'id');
		if(!$pollId) {
			\core\Route::redirect('/admin');
		}
		$filter = filter_input_array(INPUT_POST)['filter'];
		$this->view->filter = $filter;
		$this->view->poll = $this->_poll_mapper->getPollById($pollId);
		if(!$this->view->poll) {
			\core\Route::redirect('/admin');
		}
		$this->view->results = $this->_poll_mapper->getResults($pollId, $filter);
	}

	public function editAction()
	{
		$post_data = filter_input_array(INPUT_POST, $this->_post_poll_args);
		if(empty($post_data)) {
			$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
			if($id) {
				$poll = $this->_poll_mapper->getPollById($id);
				if(!$poll) {
					\core\Route::redirect('/admin');
				}
				$this->view->poll = $poll;
			}
		} else {
			// Валидация и сохранение
			$poll = $this->_format_poll($post_data);
			$errors = $this->_poll_mapper->validate($poll);
			if(empty($errors)) {
				$this->_poll_mapper->deleteQuestion($post_data['delete_question_id']);
				$this->_poll_mapper->deleteAnswer($post_data['delete_answer_id']);
				$this->_poll_mapper->savePoll($poll);
				\core\Route::redirect('/admin');
			} else {
				$this->view->messages['errors'] = $errors;
			}
			$this->view->poll = $poll;
		}
	}

	public function changeStatusAction()
	{
		$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
		if(empty($id)) {
			\core\Route::redirect('/admin');
		}
		$status = filter_input(INPUT_GET, 'status');
		if(!in_array($status, $this->_poll_statuses)) {
			\core\Route::redirect('/admin');
		}
		$poll = $this->_poll_mapper->getPollById($id);
		if(!$poll) {
			\core\Route::redirect('/admin');
		}elseif($status === $poll->getStatus()) {
			\core\Route::redirect('/admin');
		} else {
			$poll->setStatus($status);
			$this->_poll_mapper->savePoll($poll);
		}
		
	}

	public function deleteAction()
	{
		$id = filter_input(INPUT_GET, 'id');
		if(!$id) {
			return false;
		}
		$this->_poll_mapper->deletePoll($id);
		\core\Route::redirect('/admin');
	}

	/**
	 * Преобразует в объекты массив данных из формы.
	 * 
	 * @param type $data
	 * @return boolean|\app\models\Poll
	 */
	private function _format_poll($data)
	{
		if(empty($data)) {
			return false;
		}
		$poll = new Poll();
		if(!empty($data['id'])) {
			$poll->setId($data['id']);
		}
		if(!empty($data['poll_name'])) {
			$poll->setName($data['poll_name']);
		}
		if(empty($data['question'])) {
			return $poll;
		}
		
		$question_ids = array_keys($data['question']);
		foreach($question_ids as $question_id) {
			$question = new Question();
			if(isset($data['question_id'][$question_id])) {
				$question->setId($data['question_id'][$question_id]);
			}
			$question->setText($data['question'][$question_id]);
			if(isset($data['question_type'][$question_id])) {
				$question->setType($data['question_type'][$question_id]);
			}
			if(isset($data['required'][$question_id])) {
				$question->setRequired($data['required'][$question_id]);
			}
			if(isset($data['answer'][$question_id])) {
				$answer_ids = array_keys($data['answer'][$question_id]);
				foreach($answer_ids as $answer_id) {
					$answer = new Answer();
					if(isset($data['answer_id'][$question_id][$answer_id])) {
						$answer->setId($data['answer_id'][$question_id][$answer_id]);
					}
					$answer->setText($data['answer'][$question_id][$answer_id]);
					$question->addAnswer($answer);
				}
			}
			$poll->addQuestion($question);
		}
		return $poll;
	}
	
}
