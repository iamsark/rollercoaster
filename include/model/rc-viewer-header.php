<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Model
 * @desc		: It's a model container for Mail Header structure
 * 				  It's a complete header primarily used by the Viewer module ( Client Side ) 
 *
 **/

if (!defined('RC_INIT')) {exit;}

class RC_ViewerHeader implements JsonSerializable {
	
	/* Message's 'Subject' property  */
	private $subject;
	/* Message's 'Date' property */
	private $date;
	/* Formatted 'To' addresses */
	private $to_address;
	/* Array list of unformatted 'To' address   */
	private $to;
	/* Formatted 'CC' addresses */
	private $cc_address;
	/* Array list of unformatted 'CC' address   */
	private $cc;
	/* Formatted 'BCC' addresses */
	private $bcc_address;
	/* Array list of unformatted 'BCC' address   */
	private $bcc;
	/* Formatted 'From' addresses */
	private $from_address;
	/* Array list of unformatted 'From' address   */
	private $from;
	/* Formatted 'Reply To' addresses */
	private $reply_to_address;
	/* Array list of unformatted 'Reply To' address   */
	private $reply_to;
	/* Formatted 'Sender' addresses */
	private $sender_address;
	/* Array list of unformatted 'Send' address   */
	private $sender;
	/* Message's 'Recent' flag */
	private $recent;
	/* Message's 'Unseen' flag */
	private $unseen;
	/* Message's 'Flagged' flag */
	private $flagged;
	/* Message's 'Answered' flag */
	private $answered;
	/* Message's 'Deleted' flag */
	private $deleted;
	/* Message's 'Draft' flag */
	private $draft;
	/* Message's sequence number */
	private $msgno;
	/* Message's size */
	private $size;
	
	public function __construct($_header) {
		$this->parse($_header);
	}
	
	private function parse($_header) {
		
		$_header = (array)$_header;
		
		$subject = "";
		$toaddress = "";
		$ccaddress = "";
		$bccaddress = "";
		$fromaddress = "";
		$reply_toaddress = "";
		$senderaddress = "";
		
		if (isset($_header["subject"])) {
			$subject = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["subject"]));
		}
		if (isset($_header["toaddress"])) {
			$toaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["toaddress"]));
		}
		if (isset($_header["ccaddress"])) {
			$ccaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["ccaddress"]));
		}
		if (isset($_header["bccaddress"])) {
			$bccaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["bccaddress"]));
		}
		if (isset($_header["fromaddress"])) {
			$fromaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["fromaddress"]));
		}
		if (isset($_header["reply_toaddress"])) {
			$reply_toaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["reply_toaddress"]));
		}
		if ( isset($_header["senderaddress"])) {
			$senderaddress = RC()->helper->strip_quotes(mb_decode_mimeheader($_header["senderaddress"]));
		}
		
		$this->subject = $subject;
		$this->date = isset($_header["date"]) ? strtotime( $_header["date"] ) : "";
		$this->to_address = $toaddress;
		$this->to = isset($_header["to"]) ? $_header["to"] : array();
		$this->cc_address = $ccaddress;
		$this->cc = isset($_header["cc"]) ? $_header["cc"] : array();
		$this->bcc_address = $bccaddress;
		$this->bcc = isset($_header["bcc"]) ? $_header["bcc"] : array();
		$this->from_address = $fromaddress;
		$this->from = isset($_header["from"]) ? $_header["from"] : array();
		$this->reply_to_address = $reply_toaddress;
		$this->reply_to = isset($_header["reply_to"]) ? $_header["reply_to"] : array();
		$this->sender_address = $senderaddress;
		$this->sender = isset($_header["sender"]) ? $_header["sender"] : array();
		$this->recent = isset($_header["Recent"]) ? $_header["Recent"] : "";
		$this->unseen = isset($_header["Unseen"]) ? $_header["Unseen"] : "";
		$this->flagged = isset($_header["Flagged"]) ? $_header["Flagged"] : "";
		$this->answered = isset($_header["Answered"]) ? $_header["Answered"] : "";
		$this->deleted = isset($_header["Deleted"]) ? $_header["Deleted"] : "";
		$this->draft = isset($_header["Draft"]) ? $_header["Draft"] : "";
		$this->msgno = isset($_header["Msgno"]) ? $_header["Msgno"] : "";
		$this->size = isset($_header["Size"]) ? $_header["Size"] : "";
		
	}	
	
	public function get_subject() {
		return $this->subject;
	}
	
	public function set_subject($_subject) {
		$this->subject = $_subject;
	}
	
	public function get_date() {
		return $this->date;
	}
	
	public function set_date($_date) {
		$this->date = $_date;
	}
	
	public function get_to_address() {
		return $this->to_address;
	}
	
	public function set_to_address($_to_address) {
		$this->to_address = $_to_address;
	}
	
	public function get_to() {
		return $this->to;
	}
	
	public function set_to($_to) {
		$this->to = $_to;
	}
	
	public function get_cc_address() {
		return $this->cc_address;
	}
	
	public function set_cc_address($_cc_address) {
		$this->cc_address = $_cc_address;
	}
	
	public function get_cc() {
		return $this->cc;
	}
	
	public function set_cc($_cc) {
		$this->cc = $_cc;
	}
	
	public function get_bcc_address() {
		return $this->bcc_address;
	}
	
	public function set_bcc_address($_bcc_address) {
		$this->bcc_address = $_bcc_address;
	}
	
	public function get_bcc() {
		return $this->bcc;
	}
	
	public function set_bcc($_bcc) {
		$this->bcc = $_bcc;
	}
	
	public function get_from_address() {
		return $this->from_address;
	}
	
	public function set_from_address($_from_address) {
		$this->from_address = $_from_address;
	}
	
	public function get_from() {
		return $this->from;
	}
	
	public function set_from($_from) {
		$this->from = $_from;
	}
	
	public function get_reply_to_address() {
		return $this->reply_to_address;
	}
	
	public function set_reply_to_address($_reply_to_address) {
		$this->reply_to_address = $_reply_to_address;
	}
	
	public function get_reply_to() {
		return $this->reply_to;
	}
	
	public function set_reply_to($_reply_to) {
		$this->reply_to = $_reply_to;
	}
	
	public function get_sender_address() {
		return $this->sender_address;
	}
	
	public function set_sender_address($_sender_address) {
		$this->sender_address = $_sender_address;
	}
	
	public function get_sender() {
		return $this->sender;
	}
	
	public function set_sender($_sender) {
		$this->sender = $_sender;
	}
	
	public function get_recent() {
		return $this->recent;
	}
	
	public function set_recent($_recent) {
		$this->recent = $_recent;
	}
	
	public function get_unseen() {
		return $this->unseen;
	}
	
	public function set_unseen($_unseen) {
		$this->unseen = $_unseen;
	}
	
	public function get_flagged() {
		return $this->flagged;
	}
	
	public function set_flagged($_flagged) {
		$this->flagged = $_flagged;
	}
	
	public function get_answered() {
		return $this->answered;
	}
	
	public function set_answered($_answered) {
		$this->answered = $_answered;
	}
	
	public function get_deleted() {
		return $this->deleted;
	}
	
	public function set_deleted($_deleted) {
		$this->deleted = $_deleted;
	}
	
	public function get_draft() {
		return $this->draft;
	}
	
	public function set_draft($_draft) {
		$this->draft = $_draft;
	}
	
	public function get_msgno() {
		return $this->msgno;
	}
	
	public function set_msgno($_msgno) {
		$this->msgno = $_msgno;
	}
	
	public function get_size() {
		return $this->size;
	}
	
	public function set_size($_size) {
		$this->size = $_size;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see JsonSerializable::jsonSerialize()
	 *
	 */
	public function jsonSerialize() {
		return array (
			"subject" => $this->subject,
			"date" => $this->date,
			"to_address" => $this->to_address,
			"to" => $this->to,
			"cc_address" => $this->cc_address,
			"cc" => $this->cc,
			"bcc_address" => $this->bcc_address,
			"bcc" => $this->bcc,
			"from_address" => $this->from_address,
			"from" => $this->from,
			"reply_to_address" => $this->reply_to_address,
			"reply_to" => $this->reply_to,
			"sender_address" => $this->sender_address,
			"sender" => $this->sender,
			"recent" => $this->recent,
			"unseen" => $this->unseen,
			"flagged" => $this->flagged,
			"answered" => $this->answered,
			"deleted" => $this->deleted,
			"draft" => $this->draft,
			"msgno" => $this->msgno,
			"size" => $this->size
		);
	}
	
}

?>