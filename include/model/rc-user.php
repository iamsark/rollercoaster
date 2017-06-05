<?php

/**
 * 
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's model container for User Preferences, will be prepared at the time of User login
 * 				  and made it available via User Session context
 *
 **/

if (!defined('RC_INIT')) {exit;}

class RC_User {
	
	/* Email id which is being used to signed in now */
	private $email;
	/* Password of the user */
	private $pass;
	/* Records per page - used in pagination */
	private $rcount;
	/* Timer interval for Background Mail Sync - ( In seconds ) */
	private $ticker;
	/* Theme name prefered by the this user */
	private $theme;
	/* List of modules ( plugins ) activated by this user */
	private $modules;
	/* Additon property container, where other modules can insert properties on their own */
	public $options;
	
	public function __construct($_email = "", $_pass="", $_options) {
		$this->email = $_email;
		$this->pass = $_pass;
		$this->rcount= RC_DEFAULT_RCOUNT;
		$this->theme = RC_DEFAULT_THEME;
		$this->ticker = RC_DEFAULT_TICKER;
		$this->modules = array();
		$this->options = array();
		/* Load additional user options */
		$this->load_user_preferences($_options);
	}
	
	/**
	 * 
	 * @param 		object $_options
	 * @desc		Set 'theme', 'ticker' and 'modules' properties
	 * 				Also it triggers 'rc_load_user_preferences' action where other modules can insert additonal properties
	 * 
	 */
	private function load_user_preferences($_options) {
		if (isset($_options["rcount"])) {
			$this->rcount = $_options["rcount"]; 
		}
		if (isset($_options["ticker"])) {
			$this->ticker = $_options["ticker"];
		}
		if (isset($_options["theme"])) {
			$this->theme = $_options["theme"];
		}
		if (isset($_options["modules"])) {
			$this->modules = $_options["modules"];
		}		
		/* Trigger action loading additional properties - incase if any other modules wants to add it on user level */
		RC()->hook->trigger_action("rc_load_user_preferences", $this->options);
	}
	
	/**
	 *
	 * @return 		string
	 * @desc		Returns the Email property
	 *
	 */
	public function get_email() {
		return $this->email;
	}
	
	/**
	 * 
	 * @param 		string $_email
	 * @desc		Sets the email property
	 * 
	 */
	public function set_email($_email) {
		$this->email = $_email;
	}
	
	/**
	 *
	 * @return 		string
	 * @desc		Returns the Password property
	 *
	 */
	public function get_password() {
		return $this->pass;
	}
	
	/**
	 *
	 * @param 		string $_pass
	 * @desc		Sets the password property
	 *
	 */
	public function set_password($_pass) {
		$this->pass = $_pass;
	}

	/**
	 *
	 * @return 		integer
	 * @desc		Returns the rcount ( Records per page ) property
	 *
	 */
	public function get_rcount() {
		return $this->rcount;
	}
	
	/**
	 *
	 * @param 		integer $_ticker
	 * @desc		Sets the rcount property
	 *
	 */
	public function set_rcount($_rcount) {
		$this->rcount = $_rcount;
	}
	
	/**
	 *
	 * @return 		integer
	 * @desc		Returns the Ticker property
	 *
	 */
	public function get_ticker() {
		return $this->ticker;
	}
	
	/**
	 * 
	 * @param 		integer $_ticker
	 * @desc		Sets the ticker property
	 * 
	 */
	public function set_ticker($_ticker) {
		$this->ticker = $_ticker;
	}

	/**
	 *
	 * @return 		string
	 * @desc		Returns the Theme property
	 *
	 */
	public function get_theme() {
		return $this->theme;
	}
	
	/**
	 * 
	 * @param 		string $_theme
	 * @desc		Sets the Theme property
	 * 
	 */
	public function set_theme($_theme) {
		$this->theme = $_theme;
	}

	/**
	 *
	 * @return 		array
	 * @desc		Returns the Modules property
	 *
	 */
	public function get_modules() {
		return $this->modules;
	}
	
	/**
	 * 
	 * @param 		array $_modules
	 * @desc		Sets the Modules property
	 * 
	 */
	public function set_modules($_modules) {
		$this->modules = $_modules;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		returns the string representation of the 'RC_User'
	 * 				Used by RCDB module for persistence
	 */
	public function to_string() {
		return json_encode( array(
			"rcount" => $this->rcount,
			"ticker" => $this->ticker,
			"theme" => $this->theme,
			"modules" => $this->modules
		));		
	}
	
}

?>