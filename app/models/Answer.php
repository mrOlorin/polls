<?php
namespace app\models;

class Answer
{

	private $_id;
	private $_text;

	public function getId()
	{
		return $this->_id;
	}

	public function getText()
	{
		return $this->_text;
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

}