<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: http://sarkware.com
 * @category	: Model
 * @desc`  		: Model class for all RollerCoaster Ajax response
 * 				: Almost all the response from the server will complain with this interface 
 *
 **/

/* Prevent direct access */
if ( ! defined( 'RC_INIT' ) ) { exit; }

class RC_Response implements JsonSerializable {

	/* Status of last operation ( true, false ) */
	private $status;
	/* Additional message regarding last operation ( eg. 'created successfully', 'db error' ) */
	private $message;
	/* Page number of current payload ( used in pagination ) */
	private $page;
	/* Record's count, which is being sent ( If the payload is an Array ). */
	private $count;
	/* This carries the actual data */
	private $payload;
	
	public function __construct($_status, $_msg, $_page, $_count, $_payload) {
		$this->status = $_status;
		$this->message = $_msg;
		$this->page = $_page;
		$this->count = $_count;
		$this->payload = $_payload;
		$this->get_status();
	}	
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Response Status property
	 * 
	 */
	public function get_status() {
		return $this->status;
	}
	
	/**
	 * 
	 * @param 		boolean $_status
	 * @desc		Sets the Status property 
	 * 
	 */
	public function set_status($_status) {
		$this->status = $_status;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Message property
	 * 
	 */
	public function get_message() {
		return $this->message;
	}
	
	/**
	 * 
	 * @param 		string $_msg
	 * @desc		Sets the Message property
	 * 
	 */
	public function set_message($_msg) {
		$this->message = $_msg;
	}
	
	/**
	 * 
	 * @return 		integer
	 * @desc		Returns the Page number property
	 * 
	 */
	public function get_page() {
		return $this->page;
	}
	
	/**
	 * 
	 * @param 		integer $_page
	 * @desc		Sets the Page number property
	 * 
	 */
	public function set_page($_page) {
		$this->page = $_page;
	}
	
	/**
	 * 
	 * @return 		integer
	 * @desc		Returns the Count property
	 * 
	 */
	public function get_count() {
		return $this->count;
	}
	
	/**
	 * 
	 * @param 		integer $_count
	 * @desc		Sets the Count property 
	 * 
	 */
	public function set_count($_count) {
		$this->count = $_count;
	}
	
	/**
	 * 
	 * @return 		mixed
	 * @dsc			Get the payload property
	 * 
	 */
	public function get_payload() {
		return $this->payload;
	}
	
	/**
	 * 
	 * @param 		mixed $_payload
	 * @desc		Sets the Payload property
	 * 
	 */
	public function set_payload($_payload) {
		$this->payload = $_payload;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 * 
	 */
	public function jsonSerialize() {
		return array (
			"status" => $this->status,
			"message" => $this->message,
			"page" => $this->page,
			"count" => $this->count,
			"payload" => $this->payload
		);
	}
	
}

?>