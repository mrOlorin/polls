<?php

namespace core;

class View
{

	public $messages;
	protected $menu;
	protected $content;
	
	protected $tpl_path;

	public function __construct()
	{
		$this->tpl_path = SITE_PATH . 'app' . DS . 'views' . DS;
	}

	public function generate($folder = 'index', $view = 'index')
	{
		$this->menu = $this->get_include_contents($this->tpl_path . $folder . DS . 'menu.phtml');
		$this->content = $this->get_include_contents($this->tpl_path . $folder . DS . $view . '.phtml');
		include($this->tpl_path . 'layout.phtml');
	}

	private function get_include_contents($filename)
	{
		if(is_file($filename)) {
			ob_start();
			include $filename;
			return ob_get_clean();
		}
		return false;
	}

}
