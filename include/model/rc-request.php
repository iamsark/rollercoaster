<?php 

/**
 * @author		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Primary Request Parser.
 * @desc		: Parse the request param and instanciate the RC_Request instance with that data, 
 * 				  which will be used through out the entire process life cycle.
 * 				  All ajax request coming from RC client must complain with this interface 
 *
 **/

/* Prevent direct access */
if (!defined('RC_INIT')) {exit;}

class RC_Request {
	
	/* Request type ( Could be GET, POST, PUT or DELETE ) */
	private $type;
	/* The module name which the request is belong to ( eg. 'mail', 'contact', 'calender' ... ) */
	private $module;
	/* Context in the module ( eg. 'folder', 'lister', 'message', 'attachement' ... ) */
	private $context;
	/* Represent the task in particular ( eg. list, get, update ... ) */
	private $task;
	/* The actual data that is being sent from the Client */
	private $payload;
	
	/**
	 * 
	 * @param 		string $_str
	 * 
	 */
	public function __construct() {}
	
	/**
	 * 
	 * @param 		string $_str
	 * @return 		boolean
	 * @desc		It prepares the request object, Parse the request param and fill the RC_Request properties
	 * 				with appriate values
	 * 
	 */
	public function parse($_str) {
		/* Convert request params to Object */
		$params = json_decode($_str, true);
		/* Make sure it's a valid JSON */
		if ($params === null && json_last_error() !== JSON_ERROR_NONE) {	
			return false;
		}
		/* Prepare the RC_Request properties */
		$this->type = isset($params["request"]) ? trim($params["request"]) : null;
		$this->module = isset($params["module"]) ? trim($params["module"]) : null;
		$this->context = isset($params["context"]) ? trim($params["context"]) : null;
		$this->task = isset($params["task"]) ? trim($params["task"]) : null;
		$this->payload = isset($params["payload"]) ? $params["payload"] : null;
		/* Everything looks fine */
		return true;
	}
	
	/**
	 * 
	 * @return 		NULL|String
	 * @desc		Returns the Request property
	 * 
	 */
	public function get_type() {
		return $this->type;
	}
	
	/**
	 * 
	 * @return 		NULL|string
	 * @desc		Returns the Module property
	 * 
	 */
	public function get_module() {
		return $this->module;
	}
	
	/**
	 * 
	 * @return 		NULL|string
	 * @desc		Returns the Context property 
	 * 
	 */
	public function get_context() {
		return $this->context;
	}
	
	/**
	 * 
	 * @return 		NULL|string
	 * @desc		Returns the Task property
	 * 
	 */
	public function get_task() {
		return $this->task;
	}
	
	/**
	 * 
	 * @return 		NULL|mixed
	 * @desc		Returns the Payload property
	 * 
	 */
	public function get_payload() {
		return $this->payload;
	}
	
}

?>