<?php 

if (!defined('RC_INIT')) {exit;}

class RC_Attachment implements JsonSerializable {
			
	/* Unique identifier for the message this attachment belongs to. */
	private $uid;
	
	/* Mail host connection resource */
	private $stream;
	
	/* Used to holds the attachment structure */
	private $structure;
		
	/* Part id which is pointing to the section of the message body that contains the attachment. */
	private $part_id;
	
	/* Name of the attached file */
	private $filename;
	
	/* Attached file size */
	private $size;
	
	/* Holds the actual file data retrieved from the mail host	 */
	private $data;
	
	/* Holds the MIME type of this attachment */
	private $mime_type;
	
	/* Holds the encoding method of this attachment */
	private $encoding;
	
	/**
	 * 
	 * @param 		number $_uid
	 * @param 		resource $_stream
	 * @param 		object $_structure
	 * @param 		number $_part_id
	 * 
	 */
	public function __construct($_uid, $_stream, $_structure, $_part_id=null) {
		
		$this->uid = $_uid;
		$this->stream = $_stream;
		$this->structure = $_structure;
		
		if (isset($_part_id)) {
			$this->part_id = $_part_id;
		}			
		
		$parameters = RC_PhpImapReceiver::get_parameters_from_structure($structure);
		if (isset($parameters['filename'])) {
			$this->setFileName($parameters['filename']);
		} elseif (isset($parameters['name'])) {
			$this->setFileName($parameters['name']);
		}
		
		$this->size = $structure->bytes;
		$this->mime_type = RC_Helper::mime_to_string($structure->type);
		
		if (isset($structure->subtype)) {
			$this->mime_type .= '/' . strtolower($structure->subtype);
		}
		
		$this->encoding = $structure->encoding;
		
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Fetch the actual file data from the mail host
	 * 
	 */
	public function get_data() {
		if (!isset($this->data)) {
			$messageBody = isset($this->part_id) ? imap_fetchbody($this->stream, $this->uid, $this->partId, FT_UID) : imap_body($this->stream, $this->uid, FT_UID);
			$messageBody = RC_PhpImapReceiver::decode($messageBody, $this->encoding);
			$this->data  = $messageBody;
		}
		return $this->data;
	}
	
	/**
	 * 
	 * @return 		string|boolean
	 * @desc		Returns the name of the attached file
	 * 				False if it doesn't have one
	 * 
	 */
	public function get_filename() {
		return (isset($this->filename)) ? $this->filename : false;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the MIME type of the attached file
	 * 
	 */
	public function get_mime_type() {
		return $this->mime_type;
	}
	
	/**
	 * 
	 * @return 		number
	 * @desc		Returns the file size of this attachment
	 * 
	 */
	public function get_size() {
		return $this->size;
	}
	
	/**
	 * 
	 * @return 		object
	 * @desc		Returns the attachment structure property
	 * 
	 */
	public function get_structure() {
		return $this->structure;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 *
	 */
	public function jsonSerialize() {
		return array (
			"filename" => $this->filename,
			"extension" => pathinfo($this->filename, PATHINFO_EXTENSION),
			"mime_type" => $this->mime_type,
			"size" => $this->size
		);
	}
	
}

?>