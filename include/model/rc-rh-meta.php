<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's model container for Imap ( Pop3 ) detail ( based on the domain name extracted from login email )
 * 				  will be prepared at the time of User login and made it available via User Session context
 * 
 **/

if (!defined('RC_INIT')) {exit;}

class RC_RHostMeta implements JsonSerializable {
	
	/* Imap ( pop3 ) host address */
	private $host;
	/* Imap ( pop3 ) host's port number */
	private $port;
	/* Imap or Pop3 */
	private $type;
	/* Security mehtod for connection - TLS, SSL ... */
	private $security;
	/* Whether the security certificate should be validated by the server */
	private $validate;
	
	/**
	 * 
	 * @param 		object $_receiver - Associative array detched from 'rc-domains.json'
	 * 
	 */
	public function __construct($_receiver) {
		$this->prepare($_receiver);
	}
	
	/**
	 * 
	 * @param 		object $_receiver
	 * @desc		Parse the incoming receiver object and assign to appropriate properties
	 * 
	 */
	private function prepare($_receiver) {
		$this->host = isset($_receiver["host"]) ? $_receiver["host"] : NULL;
		$this->port = isset($_receiver["port"]) ? $_receiver["port"] : NULL;
		$this->type = isset($_receiver["type"]) ? $_receiver["type"] : NULL;
		$this->security = isset($_receiver["security"]) ? $_receiver["security"] : NULL;
		$this->validate = isset($_receiver["validate"]) ? $_receiver["validate"] : NULL;
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
	 * @desc		Returns the Reciever Type property ( could be 'imap' or 'pop3' )
	 * 
	 */
	public function get_type() {
		return $this->type;
	}
	
	/**
	 * 
	 * @param 		string $_type
	 * @desc		Sets the Receiver Type property
	 * 
	 */
	public function set_type($_type) {
		$this->type = $_type;
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
	 * @return 		string
	 * @desc		Returns the Validate property	
	 * 
	 */
	public function get_validate() {
		return $this->validate;
	}
	
	/**
	 * 
	 * @param 		string $_validate
	 * @desc		Sets the Validate property
	 * 
	 */
	public function set_validate($_validate) {
		$this->validate = $_validate;
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
			"type" => $this->type,
			"security" => $this->security,
			"validate" => $this->validate
		);
	}
	
}

?>