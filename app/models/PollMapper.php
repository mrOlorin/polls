<?php

namespace app\models;

use core\Connection;
use app\models\Poll;

/**
 * Не уверен, что правильная реализация Data Mapper-а
 */
class PollMapper
{

	private $_tbl_polls = 'poll_polls';
	private $_tbl_questions = 'poll_questions';
	private $_tbl_answers = 'poll_answers';
	private $_tbl_results = 'poll_results';

	private $_polls_cache;

	/**
	 * Сохранение данных опроса. 
	 * @param \app\models\Poll $poll
	 */
	public function savePoll(Poll $poll)
	{
		$pollData = [
			'name' => $poll->getName(),
			'status' => $poll->getStatus(),
		];
		if($poll->getId()) {
			$this->_update($this->_tbl_polls, ['id' => $poll->getId()], $pollData);
		} else {
			$poll->setId($this->_insert($this->_tbl_polls, $pollData));
		}

		//$answers_insert_data = [];
		foreach($poll->getQuestions() as $question) {
			$question_data = [
				'poll_id' => $poll->getId(),
				'text' => $question->getText(),
				'type' => $question->getType(),
				'required' => $question->getRequired(),
			];
			if($question->getId()) {
				$this->_update($this->_tbl_questions, 
							['id' => $question->getId()], 
							$question_data);
			} else {
				$question->setId($this->_insert($this->_tbl_questions, $question_data));
			}
			foreach($question->getAnswers() as $answer) {
				$answerData = [
						'question_id' => $question->getId(),
						'text' => $answer->getText(),
					];
				if($answer->getId()) {
					$this->_update($this->_tbl_answers, 
								['id' => $answer->getId()], 
								$answerData);
				} else {
					$answer->setId($this->_insert($this->_tbl_answers, $answerData));
					/*$answers_insert_data[] = [
						$question->getId(),
						$answer->getText(),
					];*/
				}
			}
		}
		// Так все ответы сохранятся одним запросом, но у нас не будет их айдишников
		/*if(!empty($answers_insert_data)) {
			$this->insert_batch($this->_answers_table, 
								['question_id', 'text'], 
								$answers_insert_data);
		}*/
		return $poll;
	}

	/**
	 * 
	 * @param array $RawData
	 */
	public function saveResults(array $RawData)
	{
		if(empty($RawData['id']) || empty($RawData['user_id']) 
			|| (empty($RawData['answer']) || !is_array($RawData['answer']))) {
			return false;
		}
		$time = time();
		$fields = [
			'user_id',
			'poll_id',
			'question_id',
			'answer_id',
			'time',
		];
		$data = [];
		foreach($RawData['answer'] as $questionId => $answerId) {
			$data[] = [
				$RawData['user_id'],
				$RawData['id'],
				$questionId,
				$answerId,
				$time,
			];
		}
		$this->_insertBatch($this->_tbl_results, $fields, $data);
	}

	public function getUserId()
	{
		$query = "SELECT MAX(`user_id`) + 1 FROM `" . $this->_tbl_results . "`";
		$sth = Connection::getLink()->prepare($query);
		$sth->execute();
		return $sth->fetchAll()[0][0];
	}

	public function deletePoll($id)
	{
		if(empty($id)) {
			return false;
		}
		$this->_delete($this->_tbl_polls, ['id' => $id]);
	}

	public function deleteQuestion($id)
	{
		if(empty($id)) {
			return false;
		}
		$this->_delete($this->_tbl_questions, ['id' => $id]);
	}

	public function deleteAnswer($id)
	{
		if(empty($id)) {
			return false;
		}
		$this->_delete($this->_tbl_answers, ['id' => $id]);
	}

	public function getPollById($id = null)
	{
		if(isset($id) && isset($this->_polls_cache[$id])) {
			return $this->_polls_cache[$id];
		}
		$pollArr = $this->getPolls(['poll_id' => $id]);
		return array_shift($pollArr);
	}

	public function getPolls($terms = '')
	{
		$query = 'SELECT p.`id` AS poll_id, p.`name` AS poll_name, p.`status` AS poll_status,'
				. ' q.`id` AS question_id, q.`text` AS question, q.`type` AS question_type, '
				. ' q.`required` AS question_required, a.`id` AS answer_id, a.`text` AS answer'
				. ' FROM `' . $this->_tbl_polls . '` AS p'
				. ' LEFT JOIN `' . $this->_tbl_questions . '` AS q ON q.poll_id = p.id'
				. ' LEFT JOIN `' . $this->_tbl_answers . '` AS a ON a.question_id = q.id';
				
		if($terms) {
			$query .= $this->_generateWhere($terms);
		}

		$sth = Connection::getLink()->prepare($query);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $this->_parsePolls($results);
	}

	private function _generateWhere($terms)
	{
		$query = ' WHERE ';
		foreach($terms as $field => $value) {
			$query .= '`' . $field . "`";
			if(!is_array($value)) {
				$query .= "= '" . $value . "',";
			} else {
				$query .= " IN(";
				foreach($value as $v) {
					$query .= "'" . $v . "',";
				}
				$query = substr($query, 0, -1) . '),';
			}
		}
		return substr($query, 0, -1);
	}

	public function getResults($pollId, $filter = null)
	{
		$query = 'SELECT `user_id`, `question_id`, `answer_id`, `time`'
				. ' FROM `' . $this->_tbl_results . '`'
				. " WHERE `poll_id` = '" . $pollId . "'";

		if(isset($filter)) {
			$filterQuery = ' AND user_id IN (SELECT `user_id` FROM `' . $this->_tbl_results . '`'
					. ' WHERE ';
			foreach($filter as $question_id => $answer_ids) {
				$filterQuery .= "(`question_id` = '" . $question_id . "' "
						. "AND `answer_id` IN ('" . implode("','", $answer_ids) . "')) OR ";
			}
			$query .= substr($filterQuery, 0, -4)
					. ' GROUP BY `user_id` HAVING Count(`user_id`) = ' . count($filter) . ')';
		}

		$sth = Connection::getLink()->prepare($query);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);

		return $this->_parseResults($pollId, $results);
	}

	/**
	 * Валидация опроса. 
	 * 
	 * @param \app\models\Poll $poll
	 * @return string|boolean|null
	 */
	public function validate(Poll $poll) {
		if (!($poll instanceof Poll)) {
			return false;
		}
		// TODO: Заменить текст ощибок айдишниками
		$errors = [];
		if(!$poll->getStatus()) {
			$poll->setStatus('draft');
		}
		if(!$poll->getName()) {
			$errors[] = 'Введите название опроса';
		}
		$questions = $poll->getQuestions();
		
		if(!$questions) {
			$errors[] = 'Введите вопрос';
		} else {
			foreach($questions as $question) {
				if (!($question instanceof Question)) {
					return false;
				}
				// TODO: Добавить ещё кучу правил с сообщениями об ошибках
			}
		}
		if(count($errors)) {
			return $errors;
		} else {
			return null;
		}
	}

	private function _parseResults($pollId, $results)
	{
		if(0 === count($results)) {
			return false;
		}
		$data = [];
		// Уникальные пользователи
		$users = [];
		// Статистика по пользователям
		foreach($results as $result) {
			if(!in_array($result['user_id'], $users)) {
				$users[] = $result['user_id'];
			}
			$data['stat'][$result['question_id']][$result['answer_id']]['users'][] 
					= (int)$result['user_id'];
		}

		// Подсчёт процентов
		$passagesCount = count($users);
		foreach($data['stat'] as $questionId => $answers) {
			foreach($answers as $answerId => $stat) {
				$data['stat'][$questionId][$answerId]['count'] = count($stat['users']);
				$data['stat'][$questionId][$answerId]['percent'] 
					= round((100 / $passagesCount) * $data['stat'][$questionId][$answerId]['count']);
			}
		}

		// Добиваем остальное нулями. В моей реализации это приходится делать, 
		// чтобы сами вопросы и ответы присутствовали в выборке и отображались 
		// на странице результатов. Где-то недодумал.
		$questions = $this->getPollById($pollId)->getQuestions();
		foreach($questions as $question) {
			$answers = $question->getAnswers();
			foreach($answers as $answer) {
				if(empty($data['stat'][$question->getId()][$answer->getId()]['count'])) {
					$data['stat'][$question->getId()][$answer->getId()]['users'] = [];
					$data['stat'][$question->getId()][$answer->getId()]['count'] = 0;
					$data['stat'][$question->getId()][$answer->getId()]['percent'] = 0;
				}
			}
		}

		$data['passages_count'] = $passagesCount;
		return $data;
	}

	/**
	 * Метод преобразует результаты запроса к базе в объекты.
	 * 
	 * @param array $results
	 * @return \app\models\Poll
	 */
	private function _parsePolls($results)
	{
		$pollsArr = [];
		foreach($results as $result) {
			$pollsArr[$result['poll_id']]['id'] = $result['poll_id'];
			$pollsArr[$result['poll_id']]['name'] = $result['poll_name'];
			$pollsArr[$result['poll_id']]['status'] = $result['poll_status'];

			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['id'] = $result['question_id'];
			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['text'] = $result['question'];
			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['type'] = $result['question_type'];
			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['required'] = $result['question_required'];

			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['answers'][$result['answer_id']]['id'] = $result['answer_id'];
			$pollsArr[$result['poll_id']]['questions'][$result['question_id']]['answers'][$result['answer_id']]['text'] = $result['answer'];
		}
		
		$pollsObj = [];
		foreach($pollsArr as $pollArr) {
			$poll = new Poll();
			$poll->setId($pollArr['id'])
					->setName($pollArr['name'])
					->setStatus($pollArr['status']);
			foreach($pollArr['questions'] as $questionArr) {
				$question = new Question();
				$question->setId($questionArr['id'])
						->setText($questionArr['text'])
						->setType($questionArr['type'])
						->setRequired($questionArr['required']);
				foreach($questionArr['answers'] as $answerArr) {
					$answer = new Answer();
					$answer->setId($answerArr['id'])
							->setText($answerArr['text']);
					$question->addAnswer($answer);
				}
				$poll->addQuestion($question);
			}
			$pollsObj[$pollArr['id']] = $poll;
			if(!isset($this->_polls_cache[$pollArr['id']])) {
				$this->_polls_cache[$pollArr['id']] = $poll;
			}
		}
		return $pollsObj;
	}

	private function _insert($table, $data)
	{
		if(empty($table)) {
			throw new \Exception('Empty table name');
		}elseif(empty($data)) {
			throw new \Exception('Empty data');
		}
		$keys = array_keys($data);
		$vals = array_values($data);
		if(count($keys) !== count($vals)) {
			throw new Exception('Wrong data');
		}
		$query = 'INSERT INTO `' . $table . '`'
				. ' (`' . implode('`,`', $keys) . '`) VALUES'
				. " ('" . implode("','", $vals) . "')";
		Connection::getLink()->prepare($query)->execute();
		return Connection::getLink()->lastInsertId();
	}

	private function _insertBatch($table, $fields, $data)
	{
		if(empty($table)) {
			throw new \Exception('Empty table name');
		}elseif(empty($fields)) {
			throw new \Exception('Empty fields');
		}elseif(empty($data)) {
			throw new \Exception('Empty data');
		}
		$query = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $fields) . '`) VALUES ';
		foreach($data as $record) {
			$query .= " ('" . implode("','", $record) . "'),";
		}
		$query = substr($query, 0, -1);
		return Connection::getLink()->prepare($query)->execute();
	}

	private function _update($table, $condition, $data)
	{
		if(empty($table)) {
			throw new \Exception('Empty table name');
		}elseif(empty($condition)) {
			throw new \Exception('Empty condition');
		}elseif(empty($data)) {
			throw new \Exception('Empty data');
		}		
		$query = 'UPDATE `' . $table . '` SET ';
		foreach($data as $field => $value) {
			$query .= '`' . $field . "` = '" . $value . "',";
		}
		$query = substr($query, 0, -1) . ' WHERE ';
		foreach($condition as $field => $value) {
			$query .= '`' . $field . "` = '" . $value . "',";
		}
		$query = substr($query, 0, -1);
		Connection::getLink()->prepare($query)->execute();
	}

	private function _delete($table, $condition)
	{
		$query = 'DELETE FROM `' . $table . '` WHERE ';
		foreach($condition as $field => $value) {
			$query .= '`' . $field . "`";
			// Можно было, наверное, не усложнять код и всегда использовать "IN()"...
			if(!is_array($value)) {
				$query .= "= '" . $value . "',";
			} else {
				$query .= " IN(";
				foreach($value as $v) {
					$query .= "'" . $v . "',";
				}
				$query = substr($query, 0, -1) . '),';
			}
		}
		$query = substr($query, 0, -1);
		Connection::getLink()->prepare($query)->execute();
	}

}
