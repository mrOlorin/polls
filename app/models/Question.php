<?php
namespace app\models;

class Question
{

	private $_id;
	private $_text;
	private $_type;
	private $_required;
	private $_answers;

	public function getId()
	{
		return $this->_id;
	}

	public function getText()
	{
		return $this->_text;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function getRequired()
	{
		return $this->_required;
	}
	
	public function getAnswers()
	{
		return $this->_answers;
	}
	
	public function getAnswer($id)
	{
		if(isset($this->_answers[$id])) {
			return $this->_answers[$id];
		} else {
			return false;
		}
	}

	public function setId($id)
	{
		$this->_id = $id;
		return $this;
	}

	public function setText($text)
	{
		$this->_text = $text;
		return $this;
	}

	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	}

	public function setRequired($required)
	{
		$this->_required = $required;
		return $this;
	}
	
	public function setAnswers($answers)
	{
		$this->_answers = $answers;
		return $this;
	}
	
	public function addAnswer(Answer $answer)
	{
		$answer_id = $answer->getId();
		if($answer_id) {
			$this->_answers[$answer_id] = $answer;
		} else {
			$this->_answers[] = $answer;
		}
		return $this;
	}

}