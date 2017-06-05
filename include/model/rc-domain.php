<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: http://sarkware.com
 * @category	: Model
 * @desc   		: Represent a single domain ( Used in Admin Panel )
 *
 */

if (!defined('RC_INIT')) {exit;}

class RC_Domain {
	
	/* Domain name  */
	private $domain;
	/* Instance of RC_SHostMeta */
	private $shmeta;
	/* Instance of RC_RHostMeta */
	private $rhmeta;
	
	/**
	 * 
	 * @param 		string $_domain
	 * @param 		RC_Smtp $_sender
	 * @param 		RC_Imap $_receiver
	 * 
	 */
	public function __construct($_domain, $_shmeta, $_rhmeta) {
		$this->domain = $_domain;
		$this->shmeta = $_shmeta;
		$this->rhmeta = $_rhmeta;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Domain name ( Provided at the time of registration )
	 * 
	 */
	public function get_domain() {
		return $this->domain;
	}
	
	/**
	 * 
	 * @param 		string $_domain
	 * @desc		Sets the Domain name property
	 * 
	 */
	public function set_domain($_domain) {
		$this->domain = $_domain;
	}
	
	/**
	 * 
	 * @return 		RC_Smtp
	 * @desc		Returns the RC_Smtp object
	 * 
	 */
	public function get_shmeta() {
		return $this->shmeta;
	}
	
	/**
	 * 
	 * @param 		RC_Smtp $_sender
	 * @desc		Sets the RC_Smtp object
	 * 
	 */
	public function set_shmeta($_shmeta) {
		$this->shmeta = $_shmeta;
	}
	
	/**
	 * 
	 * @return 		RC_Imap
	 * @desc		Returns the RC_Imap object
	 * 
	 */
	public function get_rhmeta() {
		return $this->rhmeta;
	}
	
	/**
	 * 
	 * @param 		RC_Imap $_receiver
	 * @desc		Sets the RC_Imap object
	 * 
	 */
	public function set_rhmeta($_rhmetar) {
		$this->rhmeta = $_rhmeta;
	}
	
}

?>