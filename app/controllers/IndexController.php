<?php

namespace app\controllers;

use app\models\Poll;
use app\models\PollMapper;

class IndexController extends \core\Controller
{

	private $_poll_mapper;

	public function __construct()
	{
		parent::__construct();
		$this->_poll_mapper = new PollMapper();
	}

	public function indexAction()
	{
		$polls = $this->_poll_mapper->getPolls(['status' => 'active']);
		$this->view->polls = $polls;
	}

	public function resultsAction()
	{
		$pollId = (int)filter_input(INPUT_GET, 'id');
		if(!$pollId) {
			\core\Route::redirect('/index');
		}
		$this->view->poll = $this->_poll_mapper->getPollById($pollId);
		if(!$this->view->poll) {
			\core\Route::redirect('/index');
		}
		$this->view->results = $this->_poll_mapper->getResults($pollId);
	}

	public function takeAction()
	{
		$id = (int)filter_input(INPUT_GET, 'id');
		$data = filter_input_array(INPUT_POST);
		if(!empty($data)) {
			if(!isset($data['id']) || !isset($data['id'])) {
				\core\Route::redirect('/index');
			}
			$data['user_id'] = $this->_poll_mapper->getUserId();
			$this->_poll_mapper->saveResults($data);
			\core\Route::redirect('/index/results?id=' . $data['id']);
		} elseif($id) {
			$poll = $this->_poll_mapper->getPollById($id);
			$this->view->poll = $poll;
		}
	}

}
