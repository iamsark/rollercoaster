<?php 

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Account")) {
	class RC_Account {
	
		public function __construct() {
			/* Inject this module to global RC */
			RC()->inject("account", $this);
			/* Ajax action format : "rc_'your_context_name'_request" */
			RC()->hook->listen_action("rc_auth_account_login", array($this, "login"));
		}
	
		/**
		 *
		 */
		public function login() {
			if (isset($_REQUEST["rc_login"])) {
				if (isset($_REQUEST["rc_user_email"]) && isset($_REQUEST["rc_user_pass"])) {
					/* Clear the error message related session properties
					 * It might be possible to be in session due to previous failure login attempt */
					RC()->session->delete("rc-error");
					RC()->session->delete("rc-error-msg");
					/* Check whether the user credentials belongs to admin */
					if ($this->authenticate_admin($_REQUEST["rc_user_email"], $_REQUEST["rc_user_pass"])) {
						RC()->theme->send_to_admin_page();
						return;
					} else {
						if ($this->authenticate_user($_REQUEST["rc_user_email"], $_REQUEST["rc_user_pass"])) {
							RC()->theme->send_to_mail_page();
							return;
						}
					}
					RC()->session->set("rc-error-msg", "Invalid Credentials.!");
					RC()->theme->send_to_login_page();
				}
			}
		}
	
		/**
		 *
		 * @param 	string $email
		 * @param 	string $pass
		 * @return 	boolean
		 * @desc	This handler check the given 'email' and 'pass' is belong to one of the Admin user or not
		 * 			and the these admin credentials are usually stored along with rcdb config file ( rc-settings.json )
		 *
		 */
		private function authenticate_admin($_email, $_pass) {
			if (RC()->po->is_db_ready()) {
				$aoption = RC()->po->get_option("admin");
				if ($aoption) {
					if (isset($aoption["user"]) && isset($aoption["pass"])) {
						if ($_email == $aoption["user"] && $_pass == $aoption["pass"]) {
							/* Clear the normail mail user property from Session */
							RC()->session->delete("RCUSR");
							/* Mark the session with RCADMIN key */
							RC()->session->set("RCADM", $_email);
							return true;
						}
					}
				}
			}
			return false;
		}
	
		/**
		 *
		 * @param 	string $email
		 * @param 	string $pass
		 * @return 	boolean
		 * @desc	This handler is the one which does the authentication by
		 * 			connecting with the mail server, the connection details will be
		 * 			found by the 'loadAccount' method by using the given 'email'
		 * 			If the connection made successfull witht the supplied email & password
		 * 			then the given credentials are valid
		 *
		 */
		private function authenticate_user($_email, $_pass) {
			$domain = $this->load_domain_detail($_email);
			if ($this->prepare_user_context($_email, $_pass, $domain)) {
				if (RC()->receiver->connect()) {
					/* Make sure the user address suggestion cache file is there */
					$this->check_user_cache($_email);
					/* Remove admin session ( just in case ) */
					RC()->session->delete("RCADM");
					/* Load the context into session for subsequent request */
					RC()->session->set("RCUSR", serialize(RC()->context));
					return true;
				} else {
					/* Reset the context object */
					RC()->context = null;
				}
			}
			return false;
		}
	
		/**
		 *
		 * @param 	string $email
		 * @return	boolean|RC_Domain
		 * @desc	Load the domain details into the Session
		 * 			Domain list are already loaded in the 'rcdbDomains' property
		 * 			Intereseted domain name will be extracted from the given email
		 *
		 */
		private function load_domain_detail($_email) {
			/* Get the @ char index */
			$at = strpos($_email, "@");
			if ($at && RC()->po->is_db_ready()) {
				$domains = RC()->po->get_domains();
				/* Get the domain name string from the given email address */
				$d = substr($_email, $at + 1);
				if ($domains) {
					/* Iterate through the list of domains */
					foreach ($domains as $index => $domain) {
						if (isset($domain[$d])) {
							/* Instanciate RC_Sender */
							$rc_smeta = new RC_SHostMeta($domain[$d]["sender"]);
							/* Instanciate RC_Receiver */
							$rc_rmeta = new RC_RHostMeta($domain[$d]["receiver"]);
							/* Well construct the RC_Domain object and return it */
							return new RC_Domain($d, $rc_smeta, $rc_rmeta);
						}
					}
				}
			}
			return false;
		}
	
		private function prepare_user_context($_email, $_pass, $_domain) {
			if ($_domain) {
				$uoptions = RC()->po->load_user_options($_email);
				/* Instanciate User object */
				$user = new RC_User($_email, $_pass, $uoptions);
				/* Well load the RC_Context */
				RC()->context = new RC_Context($user, $_domain->get_shmeta(), $_domain->get_rhmeta());
				return true;
			}
			return false;
		}
	
		private function check_user_cache($_email) {
			/* Initialize the base cache directory PATH */
			$user_dir_ok = true;
			$user_dir = RC_LOCAL_DIR . DIRECTORY_SEPARATOR ."cache". DIRECTORY_SEPARATOR . $_email . DIRECTORY_SEPARATOR;
			$user_configs = RC_LOCAL_DIR . DIRECTORY_SEPARATOR ."cache". DIRECTORY_SEPARATOR . $_email . DIRECTORY_SEPARATOR . "configs.json";
			$user_suggestions = RC_LOCAL_DIR . DIRECTORY_SEPARATOR ."cache". DIRECTORY_SEPARATOR . $_email . DIRECTORY_SEPARATOR . "suggestions.json";
	
			if (!file_exists($user_dir)) {
				$user_dir_ok = mkdir($user_dir);
			}			
			if ($user_dir_ok) {
				/* Default user */
				$uoption = array (
					"ticker" => 5,
					"records" => 50,
					"theme" => "Dark Knight",
					"modules" => array (
						"contact",
						"calender"
					)
				);
				/* Check for user config json file - if it doesn't create one */
				RC()->helper::create_file($user_configs, $uoption);
				/* Check for user suggestions json file - if it doesn't create one */
				RC()->helper::create_file($user_suggestions, "[]");
			}
			return true;
		}
	
	}
	
	new RC_Account();
	
}

?>