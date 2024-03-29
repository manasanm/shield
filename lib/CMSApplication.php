<?php

/**
 * A class to control the setup of a cms application.
 * It exposes methods which define the runtime environment of the app
 * which also allow other plugins to register themselves with the application.
 *
 * @package PHP-WAX CMS
 **/

 
class CMSApplication {
	
	static public $modules = array();
	
	
	/**
	 * Lets the application know there is a module available
	 * the array is made up of the following:
	 * array("name"=>"value", "controller"=>"value")
	 * @param array $module
	 **/

	static public function register_module($name, $values) {
		self::$modules[$name] = $values;
	}
	
	static public function get_modules($for_display=false, $usergroup=false) {
		if(!$for_display) return self::$modules;
		else{
			$modules = self::$modules;
			foreach($modules as $name => $settings){
				if($settings['dont_display'] || ($settings['auth_level'] > $usergroup )) unset($modules[$name]);
			}
			return $modules;
		}
	}
	
	static public function get_module($name) {
		return self::$modules[$name];
	}
	
	static public function unregister_module($name){
		unset(self::$modules[$name]);
	}
	
	static public function is_registered($name){
	  if(array_key_exists($name,self::$modules)){
			return true;
		} else {
		  return false;
		}
	}
	
	static public function configure_modules() {
	  
	}
	
	
}