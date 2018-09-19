<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's model container for User's context session
 * 				  Which will be used through out process cycle by many modules
 * 				  It possess detaisl about logged in user's preference, Imap & Smtp detaails	 
 * 				  will be prepared at the time of User login and will be put it into session
 *
 **/

if (!defined('RC_INIT')) {exit;}

class RC_Context {
	
	/* Instance of RC_User class, which will contains user related properties
	 * Like their preferences, current theme, list of active plugins ... */
	private $user;
	/* Instance of RC_Smtp class - which contain the connection properties for SMTP host */
	private $sender;
	/* Instance of RC_Imap class - which contain the connection properties for SMTP host */
	private $receiver;
	
	/**
	 * 
	 * @param 		RC_User $_user
	 * @param 		RC_Smtp $_sender
	 * @param 		RC_Receiver $_receiver
	 * 
	 */
	public function __construct($_user, $_sender, $_receiver) {
		$this->user = $_user;
		$this->sender = $_sender;
		$this->receiver = $_receiver;
	}
	
	/**
	 * 
	 * @return 		RC_User
	 * @desc		Returns the User object
	 * 
	 */
	public function get_user() {
		return $this->user;
	}
	
	/**
	 * 
	 * @param 		RC_User $_user
	 * @desc		Sets the RC_User object
	 * 
	 */
	public function set_user($_user) {
		$this->user = $_user;
	}
	
	/**
	 * 
	 * @return 		RC_Smtp
	 * @desc		Returns the RC_Smtp object
	 * 
	 */
	public function get_sender() {
		return $this->sender;	
	}
	
	/**
	 * 
	 * @param 		RC_Smtp $_sender
	 * @desc		Sets the RC_Smtp object
	 * 
	 */
	public function set_sender($_sender) {
		$this->sender = $_sender;		
	}
	
	/**
	 * 
	 * @return 		RC_Receiver
	 * @desc		Returns the RC_Imap object
	 * 
	 */
	public function get_receiver() {
		return $this->receiver;
	}
	
	/**
	 * 
	 * @param 		RC_Receiver $_receiver
	 * @desc		Sets the RC_Imap object
	 * 
	 */
	public function set_receiver($_receiver) {
		$this->receiver = $_receiver;
	}
	
}

?>