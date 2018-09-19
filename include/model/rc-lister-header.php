<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's a model container for Mail Header structure
 * 				  It's header overview ( not detailed header ) used primarily by Lister Module ( Client side )
 *
 **/

if (!defined('RC_INIT')) {exit;}

class RC_ListerHeader implements JsonSerializable {
	
	/* 'To' property contains comma seperated recipient list  */
	private $to;
	/* 'From' property */
	private $from;
	/* Unique id of the message */
	private $uid;
	/* Message's 'Subject' property  */
	private $subject;
	/* Message date - UNIX Timestamp */
	private $date;
	/* Recent flag */
	private $recent;
	/* Seen flag */
	private $seen;
	/* Flagged flag */
	private $flagged;
	/* Answered flag */
	private $answered;
	/* Deleted flag */
	private $deleted;
	/* Draft flag */
	private $draft;
	/* Message sequence number */
	private $msg_no;
	/* Used internally - client side only */
	private $checked;
	
	/**
	 * 
	 * @param 		object $_header
	 * 
	 */
	public function __construct($_header) {
		$this->prepare($_header);
	}
	
	/**
	 * 
	 * @param 		object $_header
	 * @desc		Parse the incoming $_header object and assign it with the appropriate properties
	 * 
	 */
	private function  prepare($_header) {
		
		$to = "";
		$from = "";
		$subject = "";
		
		/* Cast the incoming param to Array ( Some time it might be Object ) */
		$_header = (array)$_header;	
			
		if (isset( $_header["to"])) {
			$to = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["to"]));
			$to = str_replace("\"", "", $to);
		}
		
		if (isset($_header["from"])) {
			$from = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["from"]));
			$from = str_replace("\"", "", $from);
		}
		
		if (isset($_header["subject"])) {
			$subject = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["subject"]));
		}
		
		$this->to = $to;
		$this->from = $from;		
		$this->subject = $subject;
		$this->uid = isset($_header["uid"]) ? $_header["uid"] : NULL;
		$this->msg_no = isset($_header["msgno"]) ? $_header["msgno"] : NULL;
		$this->date = isset($_header["date"]) ? strtotime($_header["date"]) : NULL;
		$this->recent = (isset($_header["recent"]) && $_header["recent"])  ? true : false;
		$this->seen = (isset($_header["seen"]) && $_header["seen"]) ? true : false;
		$this->flagged = (isset($_header["flagged"]) && $_header["flagged"]) ? true : false;
		$this->answered = (isset($_header["answered"]) && $_header["answered"]) ? true : false;
		$this->deleted = (isset($_header["deleted"]) && $_header["deleted"]) ? true : false;
		$this->draft = (isset($_header["draft"]) && $_header["draft"]) ? true : false;		
		/* For internal purpose */
		$this->checked = false;
		
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the To property
	 * 
	 */
	public function get_to() {
		return $this->to;
	}
	
	/**
	 * 
	 * @param 		string $_to
	 * @desc		Sets the To property
	 * 
	 */
	public function set_to($_to) {
		$this->to = $_to;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the From property 
	 * 
	 */
	public function get_from() {
		return $this->from;
	}
	
	/**
	 * 
	 * @param 		string $_from
	 * @desc		Sets the From property
	 * 
	 */
	public function set_from($_from) {
		$this->from = $_from;
	}
	
	/**
	 * 
	 * @return 		string
	 * @desc		Returns the Subject property
	 * 
	 */
	public function get_subject() {
		return $this->subject;
	}
	
	/**
	 * 
	 * @param 		string $_subject
	 * @desc		Sets the Subject property
	 * 
	 */
	public function set_subject($_subject) {
		$this->subject = $_subject;
	}
	
	/**
	 * 
	 * @return 		number|NULL
	 * @desc		Returns the UID property
	 * 
	 */
	public function get_uid() {
		return $this->uid;
	}
	
	/**
	 * 
	 * @param 		number $_uid
	 * @desc		Sets the UID property
	 * 
	 */
	public function set_uid($_uid) {
		$this->uid = $_uid;
	}
	
	/**
	 * 
	 * @return 		number|NULL
	 * @desc		Returns the Message Sequence number
	 * 
	 */
	public function get_msg_no() {
		return $this->msg_no;
	}
	
	/**
	 * 
	 * @param 		number $_msgno
	 * @desc		Sets the Message Sequence numnber
	 * 
	 */
	public function set_msg_no($_msgno) {
		$this->msg_no = $_msgno;
	}
	
	/**
	 * 
	 * @return 		number|NULL
	 * @desc		Returns the Date property ( Unix Timestamp )
	 * 
	 */
	public function get_date() {
		return $this->date;
	}
	
	/**
	 * 
	 * @param 		number $_date
	 * @desc		Sets the Date property ( Has to be a Unix Timestamp )
	 * 
	 */
	public function set_date($_date) {
		$this->date = $_date;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Recent flag property
	 * 
	 */
	public function get_recent() {
		return $this->recent;
	}
	
	/**
	 * 
	 * @param 		boolean $_recent
	 * @desc		Sets the Recent flag property
	 * 
	 */
	public function set_recent($_recent) {
		$this->recent = $_recent;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Seen property
	 * 
	 */
	public function get_seen() {
		return $this->seen;
	}
	
	/**
	 * 
	 * @param 		boolean $_seen
	 * @desc		Sets the Seen property
	 * 
	 */
	public function set_seen($_seen) {
		$this->seen = $_seen;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Flagged flag property
	 * 
	 */
	public function get_flagged() {
		return $this->flagged;
	}
	
	/**
	 * 
	 * @param 		boolean $_flagged
	 * @desc		Sets the Flagged property
	 * 
	 */
	public function set_flagged($_flagged) {
		$this->flagged = $_flagged;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Answered flag property
	 * 
	 */
	public function get_answered() {
		return $this->answered;
	}
	
	/**
	 * 
	 * @param 		boolean $_answered
	 * @desc		Sets the Answered property
	 * 
	 */
	public function set_answered($_answered) {
		$this->answered = $_answered;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Deleted flag property
	 * 
	 */
	public function get_deleted() {
		return $this->deleted;
	}
	
	/**
	 * 
	 * @param 		boolean $_deleted
	 * @desc		Sets the Deleted property
	 * 
	 */
	public function set_deleted($_deleted) {
		$this->deleted = $_deleted;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Draft flag property
	 * 
	 */
	public function get_draft() {
		return $this->draft;
	}
	
	/**
	 * 
	 * @param 		boolean $_draft
	 * @desc		Sets the Draft property
	 * 
	 */
	public function set_draft($_draft) {
		$this->draft = $_draft;
	}
	
	/**
	 * 
	 * @return 		boolean
	 * @desc		Returns the Checked flag property 
	 * 
	 */
	public function get_checked() {
		return $this->checked;
	}
	
	/**
	 * 
	 * @param 		boolean $_checked
	 * @desc		Sets the Checked property
	 * 
	 */
	public function set_checked($_checked) {
		$this->checked = $_checked;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 *
	 */
	public function jsonSerialize() {
		return array (
			"to" => $this->to,
			"from" => $this->from,
			"uid" => $this->uid,
			"subject" => $this->subject,
			"date" => $this->date,
			"recent" => $this->recent,
			"seen" => $this->seen,
			"flagged" => $this->flagged,
			"answered" => $this->answered,
			"deleted" => $this->deleted,
			"draft" => $this->draft,
			"msg_no" => $this->msg_no,
			"checked" => $this->checked
		);
	}
	
}

?>