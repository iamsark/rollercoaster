<?php 
/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: Persistence Object for to manage Roller Coaster local DB ( not exactly, it just json data stored in the .json files )
 * 				: handles User preference, Receiver & Sender configuration and RC related configurations 
 **************************************************************/

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_PersistenceObject")) {

	class RC_PersistenceObject {
		
		/**/
		private $rcdb_cache = null;
		/* Holds the rcdb cache location */
		private $rcdb_base = null;
		/* Holds the rcdb settings object */
		private $rcdb_options = null;
		/* Holds the rcdb registered domain list */
		private $rcdb_domains = null;
		/* DB ready check flag */
		private $db_ready = false;
		
		const DOMAIN_FILE = "domains.json";
		const OPTION_FILE = "options.json";
		const USER_OPTION_FILE = "configs.json";
		const USER_SUGGESTION_FILE = "suggestions.json";
		
		public function __construct() {
			/**/
			$this->rcdb_cache = RC_LOCAL_DIR . DIRECTORY_SEPARATOR ."cache" . DIRECTORY_SEPARATOR;
			/* Initialize the rcdb config folder path */
			$this->rcdb_base =  RC_LOCAL_DIR . DIRECTORY_SEPARATOR ."rcdb". DIRECTORY_SEPARATOR ."internal". DIRECTORY_SEPARATOR;
			/* Load config from rc-settings.json file */
			$this->rcdb_options = file_get_contents($this->rcdb_base . self::OPTION_FILE);
			$this->rcdb_options = json_decode($this->rcdb_options, true);
			/* Load domains from rc-domains.json file */
			$this->rcdb_domains = file_get_contents($this->rcdb_base . self::DOMAIN_FILE);
			$this->rcdb_domains = json_decode($this->rcdb_domains, true);
			/* Make sure both Options as well as Domains are parsed successfully */
			if ($this->rcdb_options && $this->rcdb_domains) {
				$this->db_ready = true;
			} else {
				RC()->helper::log("PO not ready", RC()->helper::LOGGER_ERROR);
			}					
			/* Inject this module to global RC */
			RC()->inject("po", $this);
			/* Ajax action format : "rc_[module name]_[context name]_[task name]" */
			RC()->hook->listen_action("rc_admin_domain_list", array($this, "list_domains"));
			RC()->hook->listen_action("rc_admin_domain_create", array($this, "commit_domain"));
			RC()->hook->listen_action("rc_admin_domain_update", array($this, "commit_domain"));
			RC()->hook->listen_action("rc_admin_domain_delete", array($this, "delete_domain"));
			RC()->hook->listen_action("rc_admin_options_load", array($this, "load_options"));
			RC()->hook->listen_action("rc_admin_options_update", array($this, "update_options"));
		}
		
		/**
		 * 
		 * @return		boolean
		 * @desc		Return true if Local Cache is hot
		 * 				That means the Domain details and User configurations are loaded from files
		 * 
		 */
		public function is_db_ready() {
			return $this->db_ready;
		}
		
		/**
		 *
		 * @return		void
		 * @desc		loads the domain list from 'rc-domains.json'
		 * 				Used to serve the client request
		 * 
		 **/
		private function list_domains() {		
			if (is_array( $this->rcdb_domains)) {
				RC()->response = new RC_Response(true, "Domain list.!", 0, 0, $this->rcdb_domains);
			} else {
				RC()->response = new RC_Response(true, "Zero domain found.!", 0, 0, array());
			}		
		}
		
		/**
		 * 
		 * @return 		string|boolean
		 * @desc		Same as 'load_domain' except it returns the list of domain object 
		 * 				instead of setting it on Response object
		 * 
		 */		
		public function get_domains() {
			if (is_array($this->rcdb_domains)) {
				return $this->rcdb_domains;
			}
			return false;
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Insert or update domain details on 'rc-domains.json'
		 * 				Used to serve the Admin's Client module
		 * 
		 **/
		private function commit_domain() {
				
			if (!is_array( $this->rcdb_domains)) {
				$this->rcdb_domains = array();
			}
		
			$payload = RC()->request->get_payload();
		
			foreach ($this->rcdb_domains as $index => $domain) {
				foreach ($domain as $key => $details) {
					if ($key == $payload["domain"]) {
						if (RC()->request->get_request_type() == "POST") {
							RC()->response = new RC_Response(false, $payload["domain"] . " already registered.!", 0, 0, null);
							return;
						}
						if (RC()->request->get_request_type() == "PUT") {
							$this->rcdb_domains[ $index ] = array (
								$payload["domain"] => array (
									"receiver" => $payload["receiver"],
									"sender" => $payload["sender"]
								)
							);
						}
					}
				}
			}
		
			if (RC()->request->get_request_type() == "POST") {
				$this->rcdb_domains[] = array(
					$payload["domain"] => array(
						"receiver" => $payload["receiver"],
						"sender" => $payload["sender"]
					)
				);
			}
		
			if (!file_put_contents($this->rcdb_base . self::DOMAIN_FILE, json_encode($this->rcdb_domains))) {
				RC()->response = new RC_Response(false, "500 Internal Error, Failed to commit.!", 0, 0, null);
				return;
			}
		
			if (RC()->request->get_request_type() == "POST") {
				RC()->response = new RC_Response(true, $payload["domain"] . " has been successfully registered.!", 0, 0, array());
			} else {
				RC()->response = new RC_Response(true, $payload["domain"] . " has been successfully updated.!", 0, 0, array());
			}
		
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Delete a particular domain from 'rc-domains.json'
		 * 				Used to serve the Admin's Client module
		 * 
		 **/
		private function delete_domain() {
			$domains = file_get_contents($this->rcdb_base . self::DOMAIN_FILE);
			if ($domains !== false) {
				$domains = json_decode($domains, true);
			} else {
				RC()->response = new RC_Response(false, "500 Internal Error, Failed to read.!", 0, 0, null);
				return;
			}
		
			$payload = RC()->request->get_payload();
		
			foreach ($domains as $index => $domain) {
				foreach ($domain as $key => $details) {
					if ($key == $payload) {
						unset($domains[$index]);
						if (!file_put_contents($this->rcdb_base . self::DOMAIN_FILE, json_encode($domains))) {
							RC()->response = new RC_Response(false, "500 Internal Error, Failed to remove.!", 0, 0, null);
							return;
						}
						RC()->response = new RC_Response(true, "Successfully removed.!", 0, 0, array());
						return;
					}
				}
			}
			RC()->response = new RC_Response(false, "Domain not found.!", 0, 0, array());
		}
		
		/**
		 *
		 * @return		array
		 * @desc		Return the RC config object ( Loaded from options.json )
		 * 				Used for internal purpose
		 *
		 **/
		public function get_options() {
			return $this->rcdb_options;
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Loads the RC options ( Global ) object from 'options.json'
		 * 				and sets the Response object
		 * 				Used to serve the Admin's Client module
		 * 
		 **/
		private function load_options() {
			$settings = file_get_contents($this->rcdb_base . self::OPTION_FILE);
		
			if ($settings !== false) {
				$settings = json_decode($settings, true);
				if (is_array($settings)) {
					RC()->response = new RC_Response(true, "Domain list.!", 0, 0, $settings);
				} else {
					RC()->response = new RC_Response(true, "Config looks like empty.!", 0, 0, array());
				}
			} else {
				RC()->response = new RC_Response(false, "500 Internal Error.!", 0, 0, null);
			}
			return;
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Updates the RC options object on 'options.json'
		 * 				Used to serve the Admin's Client module, It replace the entire object
		 * 
		 **/
		private function update_options() {
			if (!file_put_contents($this->rcdb_base . self::OPTION_FILE, json_encode(RC()->request->get_payload()))) {
				RC()->response = new RC_Response(false, "500 Internal Error, Failed to commit.!", 0, 0, null);
				return;
			}
			RC()->response = new RC_Response(true, "Successfully committed.!", 0, 0, array());
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Updates the RC config object on 'options.json'
		 * 				Unlike 'update_options' it update a specific option given in $_key
		 *
		 **/
		private function update_option($_key, $_val) {
			$options = $this->load_options();
			/* Update the option */
			$options[$_key] = $_val;
			if (!file_put_contents($this->rcdb_base . self::OPTION_FILE, json_encode($options))) {
				RC()->response = new RC_Response(false, "500 Internal Error, Failed to commit.!", 0, 0, null);
				return;
			}
			RC()->response = new RC_Response(true, "Successfully committed.!", 0, 0, array());
		}
		
		/**
		 *
		 * @param 		string $key
		 * @return 		string|NULL
		 * @desc		Return the setting's value for a given key from the global option
		 * 				Used for internal purpose
		 *
		 **/
		public function get_option($_key) {
			if (is_array($this->rcdb_options)) {
				if (isset($this->rcdb_options[$_key])) {
					return $this->rcdb_options[$_key];
				}
			}
			return null;
		}
		
		/**
		 *
		 * @return 		string
		 * @desc		Return the local db base folder's path
		 * 
		 **/
		public function get_rcdb_base() {
			return $this->rcdb_base;
		}
		
		/**
		 * 
		 * @param 		string $_user
		 * @return 		mixed|NULL
		 * @desc		Returns the configuration object loaded for the given user
		 * 
		 */
		public function load_user_options($_email="") {
			if ($_email != "") {
				$user_option = file_get_contents($this->rcdb_cache . $_email . DIRECTORY_SEPARATOR . self::USER_OPTION_FILE);
				return json_decode($user_option, true);
			}
			return null;
		}
		
		public function update_user_options() {
			
		}
		
		/**
		 * 
		 * @return 		mixed
		 * @desc		Returns the address suggestion list for the current user
		 * 				Used while user composing mails
		 * 
		 */
		public function load_rc_us_list() {
			$user = RC()->context->get_user();
			if ($user) {
				$us_list = file_get_contents($this->rcdb_cache . $user->get_email() . DIRECTORY_SEPARATOR . self::USER_SUGGESTION_FILE);
				return json_decode($us_list, true);
			}
			return array();
		}
		
		/**
		 * 
		 * @param 		array $_uslist
		 * @desc		Updates the user suggestion list
		 * 
		 */
		public function update_rc_us_list($_uslist) {
			$_user = RC()->context->get_user();
			if ($_user) {
				if (!file_put_contents($this->rcdb_cache . $_user->get_email() . DIRECTORY_SEPARATOR . self::USER_SUGGESTION_FILE, json_encode($_uslist))) {
					file_put_contents($this->rcdb_cache . $_user->get_email() . DIRECTORY_SEPARATOR . self::USER_SUGGESTION_FILE, "[]");
				}
			}
		}
		
	}
	
	new RC_PersistenceObject();

}