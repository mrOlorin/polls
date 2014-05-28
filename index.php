<?php

error_reporting(E_ALL);

define('DS', DIRECTORY_SEPARATOR);
define('SITE_PATH', filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') . DS);
define('SITE_URL', filter_input(INPUT_SERVER, 'HTTP_HOST'));

spl_autoload_register(function ($class) {

	$file = SITE_PATH . str_replace('\\', DS, $class) . '.php';
	if(file_exists($file)) {
		include $file;
	}
});

core\Route::start();