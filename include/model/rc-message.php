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

class RC_Message implements JsonSerializable {
	
	/* RC_ViewerHeader object - cintains complete message header */
	private $header;
	/* Text body of the message */
	private $text_body;
	/* Html body of the message */
	private $html_body;
	/* Array of RC_Attachment object */
	private $attachments;
	
	/**
	 * 
	 * @param 		RC_ViewerHeader $_header
	 * @param 		string $_text_body
	 * @param 		string $_html_body
	 * @param 		RC_Attachment $_attachments ( Array of )
	 * 
	 */
	public function __construct($_header, $_text_body, $_html_body, $_attachments) {
		$this->header = $_header;
		$this->text_body = $_text_body;
		$this->html_body = $_html_body;
		$this->attachments = $_attachments;
	}
	
	/**
	 * 
	 * @return 		RC_ViewerHeader
	 * @desc		Returns RC_ViewerHeader object
	 * 
	 */
	public function get_header() {
		return $this->header;
	}
	
	/**
	 * 
	 * @param 		RC_ViewerHeader $_header
	 * @desc		Sets the 
	 * 
	 */
	public function set_header($_header) {
		$this->header = $_header;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		returns the Text Body of this message	
	 * 
	 */
	public function get_text_body() {
		return $this->header;
	}
	
	/**
	 * 
	 * @param 		string $_text_body
	 * @desc		Sets the text body for this message
	 * 
	 */
	public function set_text_body($_text_body) {
		$this->text_body = $_text_body;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Html Body of this message
	 * 
	 */
	public function get_html_body() {
		return $this->html_body;
	}
	
	/**
	 * 
	 * @param 		string $_html_body
	 * @desc		Sets the Html Body for this message
	 * 
	 */
	public function set_html_body($_html_body) {
		$this->html_body = $_html_body;
	}
	
	/**
	 * 
	 * @return 		RC_Attachment
	 * @desc		Returns the List of RC_Attachment objects 
	 * 
	 */
	public function get_attachments() {
		return $this->attachments;
	}
	
	/**
	 * 
	 * @param 		RC_Attachment $_attachments
	 * @desc		Sets the RC_Attachment list 
	 * 
	 */
	public function set_attachments($_attachments) {
		$this->attachments = $_attachments;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 *
	 */
	public function jsonSerialize() {
		return array (
			"header" => $this->header,
			"text_body" => $this->text_body,
			"html_body" => $this->html_body,
			"attachments" => $this->attachments
		);
	}
	
}

?>