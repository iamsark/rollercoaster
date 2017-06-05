<?php

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com 
 * @category	: Model
 * @desc   		: Model class for Session Management
 *
 */

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Session")) {
	
	class RC_Session {
	
		private $sessionName;
	
		public function __construct($sessionName = null, $regenerateId = false, $sessionId = null) {
			if (! is_null ( $sessionId )) {
				session_id ( $sessionId );
			}
	
			session_start ();
	
			if ($regenerateId) {
				session_regenerate_id(true);
			}
	
			if (! is_null ( $sessionName )) {
				$this->sessionName = session_name ( $sessionName );
			}
			
			/* Inject this module to global RC */
			RC()->inject("session", $this);
		}
	
		public function set($key, $val) {
			$_SESSION [$key] = $val;
		}
	
		public function get($key) {
			return (isset ( $_SESSION [$key] )) ? $_SESSION [$key] : false;
		}
	
		public function delete($key) {
			if (isset ( $_SESSION [$key] )) {
				unset ( $_SESSION [$key] );
				return true;
			}
			return false;
		}
	
		public function regenerateId($destroyOldSession = false) {
			session_regenerate_id ( false );
	
			if ($destroyOldSession) {
				// hang on to the new session id and name
				$sid = session_id ();
				// close the old and new sessions
				session_write_close ();
				// re-open the new session
				session_id ( $sid );
				session_start ();
			}
		}
	
		public function destroy() {
			return session_destroy ();
		}
	
		public function getName() {
			return $this->sessionName;
		}
	
	}
	
	new RC_Session();

}

?>