<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: One of the core module, which talk to the Mail server using PHP Imap module
 * 				  This is one of the earlier implementation for receiver, which will be replaced by the more powerfull 'RC_Socket'
 * 				  Because default PHP Imap implemenation lack many modern features such as Oauth support etc..
 * 
 */

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_PhpImapReceiver")) {

	class RC_PhpImapReceiver implements RC_Receiver {
		
		/* Name for this implementation */
		private $name = "";	
		
		/* Url of the connected Imap Server */
		private $host = null;
		
		/* Imap server connection object */
		private $stream = null;
		
		/* Holds the mailbox location */
		private $mail_box = null;
		
		/* Currently selected mail folder */
		private $folder = null;
		
		/* UID of the message which is being fetched - ( Used while fetching single message ) */
		private $uid = null;
		
		/* Sequence number of the message which is being fetched - ( Used while fetching single message ) */
		private $msgno = null;
		
		/* HTML body of the message - ( Used while fetching single message ) */
		private $html_message = null;
		
		/* TEXT body of the message - ( Used while fetching single message ) */
		private $text_message = null;
		
		/* Array of RC_Attachment instance - ( Used while fetching single message ) */
		private $attachments = array();
		
		/* Charset */
		public static $charset = 'UTF-8';
		
		/**/
		public static $charsetFlag = '//TRANSLIT';
		
		
		public function __construct() {
			$this->name = "phpimap";
			/* Inject this module to global RC */
			RC()->inject("receiver", $this);
		}
		
		/**
		 * 
		 * close connection on the destructor call
		 * 
		 **/
		public function __destruct() {
			if ($this->stream) {
				imap_close($this->stream);
				$this->stream = null;
			}
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::open()
		 */
		public function connect($_folder = "", $_force = false) {		
			if ($this->stream && !$_force) {
				return true;
			}	
			/* Before open a new connection with given folder ( If given any )
			 * Make sure existing connection is closed - if it still opened */
			if($_force) {
				$this->disconnect();
			}
			/* Ok now we are safe to open the connection */
			if (RC()->context) {		
				if( $this->prepare_host() ) {
					$user = RC()->context->get_user();
					if (is_object($user)) {
						$this->stream = imap_open($this->host . $_folder, $user->get_email(), $user->get_password());
					} else {
						RC_Helper::log("User not found on context", RC_Helper::LOGGER_ERROR);
					}
					if (is_resource($this->stream)) {
						/* Update the current folder property */
						$this->folder = ($_folder != "") ? $_folder : "INBOX";
						return true;
					}
				}					
			} else {
				RC_Helper::log("Attempting to connect with imap server before context ready", RC_Helper::LOGGER_ERROR);
			}
			/* If reached here then something not well */
			return false;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::close()
		 */
		public function disconnect() {
			if (is_resource($this->stream)) {
				imap_close($this->stream);
				$this->stream = NULL;
			}
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::is_connected()
		 */
		public function is_connected() {
			return is_resource($this->stream);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_stream()
		 */
		public function get_stream() {
			return $this->stream;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_uid()
		 */
		public function get_uid() {
			return $this->uid;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::fetch_message_folders()
		 */
		public function fetch_message_folders() {
			/* Array container for holding list of RC_Folder instances */
			$rc_folders = array();
			/* Ok fetch the mail folders */
			$folders = imap_getmailboxes($this->stream, $this->host, "*");
			if (is_array($folders)) {
				foreach ($folders as $folder) {
					$folder_name = str_replace($this->host, "", imap_utf7_decode($folder->name));
					$lIndex = strripos($folder->name, "}");
					if ($lIndex) {
						$display_name = substr($folder->name, ($lIndex + 1));
						if (stripos($display_name, "INBOX") !== false) {
							/* Check for INBOX parent folder */
							if (strtoupper($display_name) != "INBOX") {
								$display_name = str_replace("INBOX.", "", $display_name);
								$display_name = str_replace("inbox.", "", $display_name);
								$display_name = str_replace("Inbox.", "", $display_name);
							}
						} else if (stripos($display_name, "[GMAIL]") !== false) {
							/* Check for GMAIL parent folder */
							if (strtoupper($display_name) != "[GMAIL]" && stripos($display_name, "All Mail") === false) {
								$display_name = str_replace("[GMAIL]/", "", $display_name);
								$display_name = str_replace("[Gmail]/", "", $display_name);
								$display_name = str_replace("[gmail]/", "", $display_name);
								$display_name = stripslashes($display_name);
							} else {
								$display_name = false;
							}
						}
					} else {
						$display_name = "Mail Folder";
					}
					if ($display_name) {
						$display_name = ucfirst(trim(strtolower($display_name)));
						$rc_folders[] = new RC_Folder($folder_name, $display_name);
					}
				}
				return $rc_folders;
			}
			return false;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::select_folder()
		 */
		public function select_folder($_folder) {
			/* Ok now you are safe to open the new connection */
			$this->connect($_folder, true);
			return is_resource($this->stream);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::add_folder()
		 */
		public function add_folder($_new_folder) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* Make sure the new folder name has INBOX prefix
			 * For now we are supporting only adding sub folder of INBOX */
			if (strpos($_new_folder, "INBOX.") === false) {
				$_new_folder = "INBOX." . $_new_folder;
			}
			return imap_createmailbox($this->stream, $this->host . $_new_folder);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::remove_folder()
		 */
		public function remove_folder($_folder) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			return imap_deletemailbox($this->stream, $this->host . $_folder);
		}	
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::rename_folder()
		 */
		public function rename_folder($_old_name, $_new_name) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* INBOX folder cannot be renamed */
			if (strtoupper($_old_name) == "INBOX") {
				return false;
			}
			/* Make sure the old folder name has INBOX as prefix */
			if (strpos($_old_name, "INBOX.") === false) {
				$_old_name = "INBOX." . $_old_name;
			}
			/* Make sure the new folder name has INBOX as prefix */
			if (strpos($_new_name, "INBOX.") === false) {
				$_new_name = "INBOX." . $_new_name;
			}			
			return imap_renamemailbox($this->stream, $this->host . $_old_name, $this->host . $_new_name);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::empty_folder()
		 */
		public function empty_folder() {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* Check for Trash or Spam, if it either one of them then you are safe to clear them */
			if ($this->folder == $this->get_trash() || strtolower($this->folder) == "spam") {
				if (imap_delete( $this->stream, '1:*' )===false) {
					return false;
				}
				return imap_expunge($this->stream);
			} else {
				/* If the selected folder is not neither Trash nor Spam then move all messages to Trash */
				if (imap_mail_move($this->stream, '1:*', $this->get_trash()) == false) {
					return false;
				}
				return imap_expunge($this->stream);
			}
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_trash()
		 */
		public function get_trash() {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* Iterate over RC_Folder list */
			foreach ($this->fetch_message_folders() as $folder) {
				if (stripos(strtolower($folder->get_name()), 'trash') !== false || stripos(strtolower($folder->get_name()), 'bin') !== false ) {
					/* Trash folder is already exist so return it */
					return $folder->get_name();
				}
			}
			/* Trash folder not found, so create one */
			$this->add_folder( 'Trash' );
			return 'INBOX.Trash';
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_sent()
		 */
		public function get_sent() {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* Iterate over RC_Folder list */
			foreach ($this->fetch_message_folders() as $folder) {
				if (stripos(strtolower($folder->get_name()), 'sent') !== false) {
					/* Sent folder is already exist so return it */
					return $folder->get_name();
				}
			}
			/* Sent folder not found, so create one */
			$this->add_folder( 'Sent' );
			return 'INBOX.Sent';
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_draft()
		 */
		public function get_draft() {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			/* Iterate over RC_Folder list */
			foreach ($this->fetch_message_folders() as $folder) {
				if (stripos(strtolower($folder->get_name()), 'draft') !== false) {
					/* Draft folder is already exist so return it */
					return $folder->get_name();
				}
			}
			/* Draft folder not found, so create one */
			$this->add_folder( 'Draft' );
			return 'INBOX.Draft';
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::copy_to()
		 */
		public function copy_to($_folder, $_mail, $_flag="") {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			return imap_append($this->stream, $this->host . $_folder, $_mail."\r\n", $_flag);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::count()
		 */
		public function count() {
			return imap_num_msg($this->stream);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::search()
		 */
		public function search($_criterias) {
			return imap_search($this->stream, $_criterias);
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::fetch_message_headers()
		 */
		public function fetch_message_headers($_start_index=0, $_end_index=0,$_sort) {
			$headers = array();
			/* Construct the range string */
			$range = $_start_index .":".$_end_index;
			/*Init fetching */
			$raw_headers = imap_fetch_overview($this->stream, $range, 0);
			/* Ok try to extract the fetching result */			
			if ($raw_headers && is_array($raw_headers)) {
				foreach ($raw_headers as $header) {			
					$headers[] = new RC_ListerHeader($header);		
				}					
				if ($_sort == "DSC") {
					return array_reverse($headers);
				}
				return $headers;
			}
			return false;			
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::fetch_message()
		 */
		public function fetch_message($_uid, $_msgno) {			
			$this->uid = $_uid;
			$this->msgno = $_msgno;
			$this->text_message = "";
			$this->html_message = "";
			$this->attachments = array();
			
			//$raw = imap_rfc822_parse_headers( imap_fetchheader( $this->iconn, $_uid, FT_UID ) );
			$header = imap_headerinfo($this->stream, $this->msgno);
			$header = new RC_ViewerHeader($header);
			
			/* Well start to fetch various parts of this message 
			 * This will fetch 'Text' body 'HTML' body as well as all the attachment */
			$this->process_structure(imap_fetchstructure($this->stream, $this->msgno));
			if( $this->text_message != "" || $this->html_message != "" ) {
				/* Sanitize the html string for any non printable characters */
				$this->html_message = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $this->html_message);
				/* Additonal sanitization for removing vulnerable tags like 'script', 'canvas', 'object'...
				 * also it will remove HTML, BODY, HEAD and all the attributes related to java script
				 * Makse it safe to render it on client's browser */
				$this->html_message = RC()->helper->html_to_string($this->html_message);
				/* Instanciate RC_Message and return it */
				return new RC_Message($header, $this->text_message, $this->html_message, $this->attachments);
			}
			
			/* Message not fetched */
			return false;			
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::delete_messages()
		 */
		public function delete_messages($_uids) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			$trash = $this->get_trash();
			if( imap_mail_move( $this->stream, $_uids, $trash, FT_UID ) ) {
				imap_expunge( $this->stream );
				$this->disconnect();
				return true;
			}
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::move_messages()
		 */
		public function move_messages($_uids, $_to) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			if( imap_mail_move( $this->stream, $_uids, $_to, FT_UID ) ) {
				imap_expunge( $this->stream );
				$this->disconnect();
				return true;
			}
			return false;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::update_seen()
		 */
		public function update_flag($_uids, $_flaq, $_set) {
			/* Make sure the connection exist */
			if (!$this->is_connected()) {
				$this->connect();
			}
			$res = false;
			$flaq = '';
			if( $_flaq == "SEEN" ) {
				$flaq = '\\Seen';
			} else if( $_flaq == "ANSWERED" ) {
				$flaq = '\\Answered';
			} else if( $_flaq == "DELETED" ) {
				$flaq = '\\Deleted';
			} else if( $_flaq == "DRAFT" ) {
				$flaq = '\\Draft';
			} else {
				/* Flagged */
				$flaq = '\\Flagged';
			}
			if( $flaq != '' ) {
				if( $_set ) {
					/* Set flag */
					$res = imap_setflag_full($this->stream, $_uids, $flaq);
				} else {
					/* Clear flag */
					$res = imap_clearflag_full($this->stream, $_uids, $flaq);
				}
			}
			imap_expunge( $this->stream );
			$this->disconnect();
			return $res;
		}
		
		/**
		 * 
		 * {@inheritDoc}
		 * @see RC_Receiver::get_attachment()
		 */
		public function get_attachment($_type="all", $_folder, $_uid, $_msgno, $_filename="") {
			
		}
		
		/**
		 * 
		 * @param 		object $_structure
		 * @param 		string $_part_identifier
		 * @desc		Responsible for fetching message part from mail host
		 * 				If any parts has subpart then goes recursively and fetch them all
		 * 				This method referred from : https://github.com/tedious/Fetch
		 * 
		 */
		private function process_structure($_structure, $_part_identifier = null) {
			/* Flattening the parameter */
			$parameters = self::get_parameters_from_structure($_structure);
			if ((isset($parameters['name']) || isset($parameters['filename'])) || (isset($_structure->subtype) && strtolower($_structure->subtype) == 'rfc822')) {
				/* Attachment found, fetch it and prepare RC_AttachmentHeader instance */
				$this->attachments[] = new RC_Attachment($this->uid, $this->stream, $_structure, $_part_identifier);
			} elseif ($_structure->type == 0 || $_structure->type == 1) {
				/* If this is for Print or Reply than do not mark as Seen */
				if (RC()->request->get_task() == "print_mail_body" || RC()->request->get_task() == "reply_mail_body") {
					$message_body = isset($_part_identifier) ? imap_fetchbody($this->stream, $this->uid, $_part_identifier, FT_UID | FT_PEEK) : imap_body($this->stream, $this->uid, FT_UID | FT_PEEK);
				} else {
					$message_body = isset($_part_identifier) ? imap_fetchbody($this->stream, $this->uid, $_part_identifier, FT_UID) : imap_body($this->stream, $this->uid, FT_UID);
				}
					
				$message_body = self::decode($message_body, $_structure->encoding);
				if (!empty($parameters['charset']) && $parameters['charset'] !== self::$charset) {
					$mb_converted = false;
					if (function_exists('mb_convert_encoding')) {
						if (!in_array( $parameters['charset'], mb_list_encodings())) {
							if ($_structure->encoding === 0) {
								$parameters['charset'] = 'US-ASCII';
							} else {
								$parameters['charset'] = 'UTF-8';
							}
						}
						$message_body = @mb_convert_encoding($message_body, self::$charset, $parameters['charset']);
						$mb_converted = true;
					}
					if (!$mb_converted) {
						$message_body_conv = @iconv($parameters['charset'], self::$charset . self::$charsetFlag, $message_body);
						if ($message_body_conv !== false) {
							$message_body = $message_body_conv;
						}
					}
				}
				if (strtolower($_structure->subtype) === 'plain' || ($_structure->type == 1 && strtolower($_structure->subtype) !== 'alternative')) {
					if (isset($this->text_message)) {
						$this->text_message .= PHP_EOL . PHP_EOL;
					} else {
						$this->text_message = '';
					}
					$this->text_message .= trim($message_body);
				} elseif (strtolower( $_structure->subtype) === 'html') {
					if (isset($this->html_message)) {
						$this->html_message .= '<style type="text/css"> body {margin: 0;}</style>';
					} else {
						$this->html_message = '';
					}
					$this->html_message.= $message_body;
				}
			}
			if (isset($_structure->parts)) { // multipart: iterate through each part
				foreach ($_structure->parts as $part_index => $part) {
					$part_id = $part_index + 1;
					if (isset($_part_identifier))
						$part_id = $_part_identifier . '.' . $part_id;
						$this->process_structure($part, $part_id);
				}
			}
		}
		
		/**
		 * 
		 * @param 		object $_structure
		 * @return 		NULL|array
		 * @desc		Break down any object and flatten all its structure
		 * 				and return as Flat Associative array
		 * 
		 */
		public static function get_parameters_from_structure($_structure) {
			$parameters = array();
			if (isset($_structure->parameters))
				foreach ($_structure->parameters as $parameter)
					$parameters[strtolower($parameter->attribute)] = $parameter->value;
					if (isset($_structure->dparameters))
						foreach ($_structure->dparameters as $parameter)
							$parameters[strtolower($parameter->attribute)] = $parameter->value;
							return $parameters;
		}
		
		public static function decode( $_data, $_encoding )	{
			if (!is_numeric($_encoding)) {
				$_encoding = strtolower($_encoding);
			}
			switch (true) {
				case $_encoding === 'quoted-printable':
				case $_encoding === 4:
					return quoted_printable_decode($_data);
				case $_encoding === 'base64':
				case $_encoding === 3:
					return base64_decode($_data);
				default:
					return $_data;
			}
		}
		
		/**
		 * 
		 * @return 		string|boolean
		 * @desc		Constructing RFC:5092 host url
		 * 				Receiver host meta can be obtained from the user context
		 * 				Return false if RC_RHostMeta not found on user context
		 * 
		 */
		private function prepare_host() {
			$rhost_meta = RC()->context->get_receiver();
			if (is_object($rhost_meta)) {
				if ($rhost_meta->get_validate() == "no") {
					$this->host = "{". $rhost_meta->get_host() .":". $rhost_meta->get_port() ."/". $rhost_meta->get_type() ."/". $rhost_meta->get_security() ."/novalidate-cert}";
				} else {
					$this->host = "{". $rhost_meta->get_host() .":". $rhost_meta->get_port() ."/". $rhost_meta->get_type() ."/". $rhost_meta->get_security() ."}";
				}
				return true;
			}
			RC()->helper::log("Receiver Meta not found on context", RC()->helper::LOGGER_ERROR);
			return false;
		}
		
	}
	
	new RC_PhpImapReceiver();

}

?>