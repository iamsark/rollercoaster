<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's model container for Mail Folder structure
 *
 **/

if (!defined('RC_INIT')) {exit;}

class RC_Folder implements JsonSerializable {
	
	/* Folder name */
	private $name;
	/* Folder display name - without any prefix */
	private $dname;
	/* Total message count of this folder */
	private $total_message;
	/* Total unread message count of this folder */
	private $total_unread;
	
	/**
	 * 
	 * @param 		string $_name
	 * @param 		string $_dname
	 * @param 		number $_total_message
	 * @param 		number $_total_unread
	 * 
	 */
	public function __construct($_name, $_dname = "", $_total_message = 0, $_total_unread = 0) {
		$this->name = $_name;
		$this->dname = $_dname;
		$this->total_message = $_total_message;
		$this->total_unread = $_total_unread;
	}
	
	/**
	 * 
	 * @param 		object $_folder
	 * @desc		Parse the incoming $_folder object and assign it with the appropriate properties
	 * 
	 */
	private function prepare($_folder) {
		$this->name = isset($_folder["name"]) ? $_folder["name"] : NULL;
		$this->dname = isset($_folder["dname"]) ? $_folder["dname"] : NULL;
		$this->total_message = isset($_folder["total_message"]) ? $_folder["total_message"] : NULL;
		$this->total_unread = isset($_folder["total_unread"]) ? $_folder["total_unread"] : NULL;
	}
		
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Folder name
	 * 
	 */
	public function get_name() {
		return $this->name;
	}
	
	/**
	 * 
	 * @param 		string $_name
	 * @desc		Sets the Folder name
	 * 
	 */
	public function set_name($_name) {
		$this->name = $_name;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Folder's display name
	 * 
	 */
	public function get_display_name() {
		return $this->dname;
	}
	
	/**
	 * 
	 * @param 		string $_dname
	 * @desc		Sets the Folder's display name
	 * 
	 */
	public function set_display_name($_dname) {
		$this->dname = $_dname;
	}
	
	/**
	 * 
	 * @return 		number
	 * @desc		Returns the Total Message count of this folder
	 * 
	 */
	public function get_total_message_count() {
		return $this->total_message;
	}
	
	/**
	 * 
	 * @param 		number $_total_message_count
	 * @desc		Sets the Total Message count for this folder 
	 * 
	 */
	public function set_total_message_count($_total_message_count) {
		$this->total_message = $_total_message_count;
	}
	
	/**
	 * 
	 * @return 		number
	 * @desc		Returns the Total Unread Message count
	 * 
	 */
	public function get_unread_message_count() {
		return $this->total_unread;
	}
	
	/**
	 * 
	 * @param 		number $_unread_message_count
	 * @desc		Sets the Total Unread Message count for this folder
	 * 
	 */
	public function set_unread_message_count($_unread_message_count) {
		$this->total_unread = $_unread_message_count;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 *
	 */
	public function jsonSerialize() {
		return array (
			"name" => $this->name,
			"display_name" => $this->dname,
			"total_message" => $this->total_message,
			"total_unread" => $this->total_unread
		);
	}
	
}

?>