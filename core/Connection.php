<?php

namespace core;

class Connection
{

	private static $_link;

	public static function getLink()
	{
		if (empty(self::$_link)) {
			$config = include(SITE_PATH . 'app' . DS . 'cfg' . DS . 'db.php');
			self::$_link = new \PDO ('mysql:' . $config['host'] . '=;dbname='.$config['dbname'], $config['uname'], $config['pass'], $config['options']);
		}
		return self::$_link;
	}

}