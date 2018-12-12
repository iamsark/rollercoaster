<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Loader
 * @desc		: Well this is where everything get started
 *
 **/

if (!defined("RC_INIT")) {
	define("RC_INIT", true);
}

if (!defined("RC_ROOT_URL")) {
	$root_path = dirname(dirname(__FILE__));
	$root_path = substr($root_path, strripos($root_path, DIRECTORY_SEPARATOR));
	define("RC_ROOT_URL", $root_path . DIRECTORY_SEPARATOR);
}

if (!defined( "RC_ROOT_DIR")) {
	define("RC_ROOT_DIR", dirname(dirname(__FILE__)));
}

if (!defined("RC_INC_DIR")) {
	define("RC_INC_DIR", dirname(dirname(__FILE__)). DIRECTORY_SEPARATOR ."include");
}

if (!defined("RC_LOCAL_DIR")) {
	define("RC_LOCAL_DIR", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR ."local");
}

if (!defined("RC_ADM_DIR")) {
	define("RC_ADM_DIR", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR ."admin");
}

if (!defined("RC_THEME_DIR")) {
	define("RC_THEME_DIR", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR ."view");
}

if (!defined("RC_PLUGIN_DIR")) {
	define("RC_PLUGIN_DIR", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR ."plugin");
}

if (!defined("RC_UPDATER_URL")) {
	define("RC_UPDATER_URL", "http://rollercoaster.sarkware.com/updates/rc-stats.php");
}

if (!defined("RC_FEEDS_URL")) {
	define("RC_FEEDS_URL", "https://rollercoaster.sarkware.com/feeds");
}

if (!defined("RC_DEFAULT_THEME")) {
	define("RC_DEFAULT_THEME", "Dark Knight");
}

if (!defined("RC_DEFAULT_TICKER")) {
	define("RC_DEFAULT_TICKER", 5);
}

if (!defined("RC_DEFAULT_RCOUNT")) {
	define("RC_DEFAULT_RCOUNT", 50);
}

if (!defined("RC_VERSION")) {
	define("RC_VERSION", 1.0);
}

if (!class_exists("RC_Loader")) {
	
	class RC_Loader {
	
		var
		/* Maintain the parsed request object
		 * which can be accessed through out the process life cycle across all modules
		 * like this "RC()->request" */
		$request,
		/* Maintain the prepared response object, any module that wants to send the response
		 * it can write it on this property, like this "RC()->response"
		 * which will be flushed out by the docker to client */
		$response,	
		/* Rollercoaster configurations, loaded from '/local/rcdb/internal/options.json' */
		$config;
		
		public function __construct() {}
		
		public function load_environment() {
			
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-hook.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-helper.php";
			
			/* Loading model classes */
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-session.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-request.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-response.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-user.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-sh-meta.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-rh-meta.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-domain.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-folder.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-lister-header.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-viewer-header.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-message.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "rc-context.php";
					
			/* Load receiver implementation */
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "receiver" . DIRECTORY_SEPARATOR . "rc-receiver.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "receiver" . DIRECTORY_SEPARATOR . "php-imap" . DIRECTORY_SEPARATOR . "rc-attachment.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "receiver" . DIRECTORY_SEPARATOR . "php-imap" . DIRECTORY_SEPARATOR . "rc-php-imap.php";
			
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "receiver" . DIRECTORY_SEPARATOR . "rc-socket" . DIRECTORY_SEPARATOR . "rc-socket.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "receiver" . DIRECTORY_SEPARATOR . "rc-socket" . DIRECTORY_SEPARATOR . "rc-socket-imap.php";
						
			/* Load sender implementation */
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "sender" . DIRECTORY_SEPARATOR . "rc-sender.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "sender" . DIRECTORY_SEPARATOR . "php-mailer" . DIRECTORY_SEPARATOR . "rc-php-mailer.php";
			
			/* Loading core modules */
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-po.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-plugin.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-theme.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-template.php";
			require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-account.php";
			
			/**
			 * 
			 * There are three run level
			 * 1. Admin			( Env for Admin Users )
			 * 2. User			( Env for Normal Users )
			 * 3. Pre Session 	( When no one is logged in )
			 * 
			 * */
			if( $this->session->get( "RCADM" ) != false ) {
				define( "RC_GOAHEAD", true );
				/* Admin territory */
				return true;
			} else if( $this->session->get( "RCUSR" ) != false ) {
				define( "RC_GOAHEAD", true );
				$this->context = unserialize( $this->session->get("RCUSR") );
				/* User territory */
				require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "rc-mailer.php";
			} else {
				/* Empty territory */
				define( "RC_GOAHEAD", false );
			}
			
			/* Load global configuration */
			$this->load_config();
			
			/* Load plugin manager */
			/* trigger the rc_plugins_loaded action */
			$this->hook->trigger_action( "rc_plugins_loaded" );
			
			/* Load theme manager */
			/* trigger the rc_themes_loaded action */
			$this->hook->trigger_action( "rc_themes_loaded" );
			
			/* trigger the rc_ready action */
			$this->hook->trigger_action( "rc_ready" );
			
		}
		
		/**
		 * 
		 * @param 		: mixed $_name
		 * @param 		: mixed $_value
		 * @desc		: Used to add new property to the Global RC() instance
		 * 
		 */
		public function inject($_name, $_value) {
			$this->{$_name} = $_value;
		}
		
		private function load_config() {
		    $this->config = array();
		    $config = json_decode(file_get_contents(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR ."local". DIRECTORY_SEPARATOR ."rcdb". DIRECTORY_SEPARATOR ."internal". DIRECTORY_SEPARATOR ."options.json"), true);
		    if ($config && isset($config["config"])) {
		        $this->config = $config["config"];
		    }		    
		}
		
	}

}

function RC() {
	if (!isset($GLOBALS["roller_coaster"])) {
		$GLOBALS["roller_coaster"] = new RC_Loader();
	}
	return  $GLOBALS["roller_coaster"];
}

RC();

?>