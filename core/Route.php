<?php

namespace core;

class Route
{

	public static function start()
	{
		// Дефолтные контроллер и вид
		$controller_name = 'Index';
		$action_name = 'index';
		$routes = explode('/', filter_input(INPUT_SERVER, 'REQUEST_URI'));
		if(!empty($routes[1])) {
			$controller_name = $routes[1];
		}
		$controller_class = 'app\\controllers\\' . ucfirst($controller_name) . 'Controller';
		if(!class_exists($controller_class)) {
			throw new \Exception('Wrong controller');
		}
		$controller = new $controller_class;
	
		if(!empty($routes[2])) {
			$action_name = $routes[2];
		}
		
		$parsed = parse_url($action_name);
		if(!empty($parsed['query'])) {
			$action_name = str_ireplace('?' . $parsed['query'], '', $action_name);
		}

		$method = $action_name . 'Action';
		if(!method_exists($controller, $method)) {
			throw new \Exception('Wrong action');
		}
		$controller->$method();
		$controller->view->generate($controller_name, $action_name);
	}

	public static function redirect($url, $isAbs = false)
	{
		if(false === $isAbs) {
			$url = '//' . SITE_URL . $url;
		}
		if(false === headers_sent()) {
			header('Location: ' . $url);
		} else {
			echo '<script>window.location.replace("' . $url . '");</script>';
		}
		exit();
	}

}
