<?php
if(!function_exists('load_controller')) {
	function load_controller($controller) {
		$class_info = explode('/', str_replace('.php', '', strtolower($controller)));
		$class_name = ucfirst(array_pop($class_info)); 
		
		if(class_exists($class_name) == FALSE) {
			$class_file = count($class_info) > 0 ? implode('/', array_merge($class_info, (array) $class_name)) : $class_name;
			$uc_class_file = realpath(APPPATH . 'controllers/' . $class_file . '.php');
			$class_file = $uc_class_file != FALSE ? $uc_class_file : realpath(APPPATH . 'controllers/' . strtolower($class_file) . '.php');
			
			if(file_exists($class_file)) {
				include_once($class_file);
			} else {
				log_message('error', 'Controller not found! [' . APPPATH . 'controllers/' . $class_name . '.php]');
				return NULL;
			}
		}

		return new $class_name();
	}
}
