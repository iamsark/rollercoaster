<?php 
/**
 * @author		: Saravana Kumar K
 * @author_url	: http://iamsark.com
 * @purpose		: Responsible for managing themes and all kinds of page redirection are handled here.
 *
 */

if (!defined('RC_INIT')) {exit;}

if(!class_exists("RC_ThemeManager")) {
	
	class RC_ThemeManager {
	
		private $themes = array();
		private $broken = array();
	
		public function __construct() {
			/* Inject this module to global RC */
			RC()->inject("theme", $this);
		}
	
		public function list_themes() {
	
			$t_dirs = glob( RC_THEME_DIR . '/*', GLOB_ONLYDIR );
	
			foreach ( $t_dirs as $theme ) {
				if( file_exists( $theme . DIRECTORY_SEPARATOR ."theme.json" ) ) {
					$t_info = json_decode( file_get_contents( $theme . DIRECTORY_SEPARATOR ."theme.json" ), true );
					if( $t_info ) {
						$this->themes[] = $t_info;
					} else {
						/* 'theme.json' contains invalid details */
						$this->broken[] = array(
							"theme" => substr( $theme, strripos( $theme, DIRECTORY_SEPARATOR ) ),
							"reason" => "theme.json contains invalid details"
						);
					}
				} else {
					/* 'theme.json' Missing */
					$this->broken[] = array(
						"theme" => substr( $theme, strripos( $theme, DIRECTORY_SEPARATOR ) ),
						"reason" => "theme.json is missing"
					);
	
				}
			}
	
		}
	
		public function add_theme() {
	
		}
	
		public function remove_theme() {
	
		}
	
		/**
		 * 
		 * @return 		string|boolean
		 * @since 		1.0.0
		 * @desc		Returns the active themes directory object
		 * 				Returned object will have the following properties
		 * 
		 * 				@name": "",
						@directory": "",
						@desc": "",
						@dependencies": []	
		 * 
		 */
		public function get_current_theme() {
	
			if( RC()->po->is_db_ready() ) {
				$theme = RC()->po->get_option( "theme" );
				if( isset( $theme["directory"] ) ) {
					return $theme["directory"];
				}
			}
	
			return false;
	
		}
	
		/**
		 * 
		 * @return 		string|boolean
		 * @since		1.0.0
		 * @desc		
		 * 
		 */
		public function get_current_theme_dir() {
			$current_theme_dir = $this->get_current_theme();
			if( $current_theme_dir ) {
				return RC_ROOT_URL . $current_theme_dir;
			}
			return false;
		}
	
		public function sent_to_login_page() {
			header( "Location: " . $this->get_login_page() );
			die();
		}
	
		public function get_login_page() {
			return RC_ROOT_URL;
		}
		
		public function send_to_login_page() {
			header( "Location: " . $this->get_login_page() );
			die();
		}
	
		public function send_to_mail_page() {
			header( "Location: " . $this->get_mail_page() );
			die();
		}
	
		public function get_mail_page() {
			$current_theme_dir = $this->get_current_theme();
			if( $current_theme_dir ) {
				return RC_ROOT_URL . "view";
			}
	
			/* Send to error page */
			return false;
		}
	
		public function send_to_admin_page() {
			header( "Location: " . $this->get_admin_page() );
			die();
		}
	
		public function get_admin_page() {
			return RC_ROOT_URL . "rc-admin";
		}
	
	}
	
	new RC_ThemeManager();
	
}

?>