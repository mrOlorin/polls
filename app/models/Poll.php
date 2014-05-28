<?php
namespace app\models;

class Poll
{

	private $_id;
	private $_name;
	private $_questions;
	private $_status = 'draft';

	public function getId()
	{
		return $this->_id;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getQuestions()
	{
		return $this->_questions;
	}

	public function getQuestion($id)
	{
		
		if(isset($this->_questions[$id])) {
			return $this->_questions[$id];
		} else {
			return false;
		}
	}

	public function getStatus()
	{
		return $this->_status;
	}

	public function setId($id)
	{
		$this->_id = $id;
		return $this;
	}

	public function setName($name)
	{
		$this->_name = $name;
		return $this;
	}

	public function setQuestions($questions)
	{
		$this->_questions = $questions;
		return $this;
	}
	
	public function addQuestion(Question $question)
	{
		$question_id = $question->getId();
		if($question_id) {
			$this->_questions[$question_id] = $question;
		} else {
			$this->_questions[] = $question;
		}
		return $this;
	}

	public function setStatus($status)
	{
		$this->_status = $status;
		return $this;
	}

}