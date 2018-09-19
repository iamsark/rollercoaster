<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's model container for Smtp detail ( based on the domain name extracted from login email )
 * 				  will be prepared at the time of User login and made it available via User Session context
 * 
 **/

if (!defined('RC_INIT')) {exit;}

class RC_SHostMeta implements JsonSerializable {
	
	/* Imap ( pop3 ) host address */
	private $host;
	/* Imap ( pop3 ) host's port number */
	private $port;
	/* Security mehtod for connection - TLS, SSL ... */
	private $security;
	/* Whether to enable SMTP authentication */
	private $authenticate;
	
	/**
	 *
	 * @param 		object $_sender - Associative array detched from 'rc-domains.json'
	 *
	 */
	public function __construct($_sender) {
		$this->prepare($_sender);
	}
	
	/**
	 * 
	 * @param 		object $_sender
	 * @desc		Parse the incoming sender object and assign to appropriate properties
	 * 
	 */
	private function prepare($_sender) {
		$this->host = isset($_sender["host"]) ? $_sender["host"] : NULL;
		$this->port = isset($_sender["port"]) ? $_sender["port"] : NULL;
		$this->security = isset($_sender["security"]) ? $_sender["security"] : NULL;
		$this->authenticate = isset($_sender["authenticate"]) ? $_sender["authenticate"] : NULL;
	}
	
	/**
	 *
	 * @return 		string
	 * @desc		Returns the Host address property
	 *
	 */
	public function get_host() {
		return $this->host;
	}
	
	/**
	 *
	 * @param 		string $_host
	 * @desc		Sets the Host address property
	 *
	 */
	public function set_host($_host) {
		$this->host = $_host;
	}
	
	/**
	 *
	 * @return 		number
	 * @desc		Returns the Port number property
	 *
	 */
	public function get_port() {
		return $this->port;
	}
	
	/**
	 *
	 * @param 		number $_port
	 * @desc		Sets the Port number property
	 *
	 */
	public function set_port($_port) {
		$this->port = $_port;
	}
	
	/**
	 *
	 * @return 		string
	 * @desc		Returns the security type property
	 *
	 */
	public function get_security() {
		return $this->security;
	}
	
	/**
	 *
	 * @param 		string $_security
	 * @desc		Sets the Security type property
	 *
	 */
	public function set_security($_security) {
		$this->security = $_security;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Authenticate property
	 * 
	 */
	public function get_authenticate() {
		return $this->authenticate;
	}
	
	/**
	 * 
	 * @param 		boolean $_authenticate
	 * @desc		Sets the Authenticate property
	 * 
	 */
	public function set_authenticate($_authenticate) {
		$this->authenticate = $_authenticate;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 * 
	 */
	public function jsonSerialize() {
		return array (
			"host" => $this->host,
			"port" => $this->port,
			"security" => $this->security,
			"authenticate" => $this->authenticate
		);
	}
	
}

?>