<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: Does the delegator role for all email related actions
 * 				  Does the house keeping before routing the request and sending response back
 * 				  Routes the incoming request for both receving and sending emails to concerned modules	 
 * 
 **************************************************************/

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Mailer")) {

	class RC_Mailer {
		
		public function __construct() {
			/* Inject this module to global RC */
			RC()->inject("mailer", $this);
			/* Ajax action format : "rc_[module name]_[context name]_[task name]" */
			RC()->hook->listen_action("rc_email_folder_list", array($this, "get_message_folders"));
			RC()->hook->listen_action("rc_email_folder_create", array($this, "create_folder"));
			RC()->hook->listen_action("rc_email_folder_rename", array($this, "rename_folder"));
			RC()->hook->listen_action("rc_email_folder_delete", array($this, "delete_folder"));
			RC()->hook->listen_action("rc_email_folder_meta_list", array($this, "get_folders_meta"));
			RC()->hook->listen_action("rc_email_folder_meta", array($this, "get_folder_meta"));
			RC()->hook->listen_action("rc_email_lister_list", array($this, "get_message_list"));
			RC()->hook->listen_action("rc_email_lister_sync", array($this, "get_message_list"));
			RC()->hook->listen_action("rc_email_lister_delete", array($this, "delete_messages"));
			RC()->hook->listen_action("rc_email_lister_bulk_delete", array($this, "delete_messages"));
			RC()->hook->listen_action("rc_email_lister_move", array($this, "move_messages"));
			RC()->hook->listen_action("rc_email_lister_bulk_move", array($this, "move_messages"));
			RC()->hook->listen_action("rc_email_viewer_load", array($this, "get_message"));	
			RC()->hook->listen_action("rc_email_viewer_print_mail_body", array($this, "get_message"));
			RC()->hook->listen_action("rc_email_viewer_delete", array($this, "delete_messages"));			
			RC()->hook->listen_action("rc_email_attachment_load", array($this, "get_attachments"));
			
			RC()->hook->listen_action("rc_email_composer_uslist", array($this, "get_user_suggestion"));	
			RC()->hook->listen_action("rc_email_composer_reply_mail_body", array($this, "get_message"));
		}
		
		/**
		 * 
		 * @desc		Respond with folder list
		 * 				List of RC_Folder model 
		 * 				No parameter expected from the request
		 * 
		 */
		public function get_message_folders() {
			
			if( RC()->receiver->connect() ) {
				if($flist = RC()->receiver->list_folders()) {
					/* Trigger the filter for other modules to process the folder list before sending response */
					$flist = RC()->hook->trigger_filter("rc_folder_list", $flist);
					/* Prepare response */
					RC()->response = new RC_Response(true, "Folder list", 0, count($flist), $flist);
				} else {
					/* Fetching issue */
					RC()->response = new RC_Response(false, imap_last_error(), 0, 0, array());
				}				
			} else {
				/* Connection issue */
				RC()->response = new RC_Response(false, "Not able to reach mail host", 0, 0, array());
			}			
			
		}
		
		/**
		 * 
		 * @desc		Retrive the mail header list for the give page number
		 * 				Number header per page will be taken from User option property
		 * 				It calculate Start and End index based on SORT option 				
		 * 				Each list item is an instance of 'RC_ListingHeader'		
		 * 
		 *				Expect the following param from the request
		 *				@folder			From which folder the headers to be fetched
		 *				@page			What page ( Pagination's page number )
		 *				@sort			DSC or ASC default will be DSC 
		 * 
		 */
		public function get_message_list() {
		
			$payload = RC()->request->get_payload();
			if (isset($payload[ "folder" ])) {
				if (RC()->receiver->select_folder($payload[ "folder" ])) {
		
					$page = 1;
					$user = RC()->context->get_user();
					$count_per_page = $user->get_rcount();
		
					if (isset($payload["page"])) {
						$page = $payload["page"];
					}
					$sort = isset($payload["sort"]) ? $payload["sort"] : "DSC";
		
					if ($sort == "DSC") {
						$sindex = $payload["count"] - (($page * $count_per_page) - $count_per_page);
						$eindex = max(($sindex - $count_per_page + 1), 1);
					} else {
						$sindex = 1;
						$eindex = $count_per_page;
						if ($page > 1) {
							$eindex = $page * $count_per_page;
							$sindex = (($page * $count_per_page) - $count_per_page) + 1;
						}
						if ($eindex > $payload["count"]) {
							$eindex = $payload["count"];
						}
					}
						
					$mails = RC()->receiver->lilst_headers($sindex, $eindex, $sort);
					if (is_array($mails)) {
						$this->update_suggestion($mails);
						$mails = RC()->hook->trigger_filter("rc_message_list", $mails);
						RC()->response = new RC_Response(true, "Message list", $page, count($mails), $mails);
					} else {
						RC()->response = new RC_Response(false, imap_last_error(), $page, -1, array());
					}
					
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array());
				}
			} else {
				RC()->response = new RC_Response(false, "Required param missing.!", 0, -1, array());
			}
		
		}
		
		/**
		 * 
		 * @desc		Fetch the complete message, which will be the instance of 'RC_Message' 
		 * 				RC_Message will have the following properties
		 * 				HEADER			: complete mail header ( instance of 'RC_ViewerHeader' )
		 * 				PLAIN_BODY		: text message
		 * 				HTML_BODY		: html message
		 * 				ATTACHMENTS		: List of attachment ( each will be an instance of 'RC_Attachment' )
		 * 
		 * 				Expect the following param from the request
		 * 				@folder			From which folder the message belongs
		 * 				@uid			Unique ID of the message
		 * 				@msg_no			Message sequence number
		 * 
		 */
		public function get_message() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["folder"]) && isset($payload["uid"])) {
				if (RC()->receiver->select_folder($payload["folder"])) {
					if ($mail = RC()->receiver->fetch_message($payload["uid"], $payload["msgno"])) {
						if (isset( $payload[ "composer" ])) {
							if ($mail->get_html_body() != "") {
								$mail->set_html_body(RC()->helper->html_to_string( $mail->get_html_body()));
							}
						}
						/* Trigger action for giving chance other modules to access the message body before sent to client */
						$mail = RC()->hook->trigger_filter("rc_message", $mail);
						RC()->response = new RC_Response(true, "Single Mail", 0, 1, $mail);
					} else {
						/* Fetching message failed */
						RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array());
					}					
				} else {
					/* Selecting folder failed */
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array());
				}
			} else {
				RC()->response = new RC_Response(false, "Required Param Missing.!", 0, -1, array());
			}
			
		}
		
		/**
		 * 
		 * @desc		Fetch the folder's meta ( for all folders )
		 * 				Each folder meat will have the following properties
		 * 				TOTAL_MESSAGE		: Total message count
		 * 				UNREAD_MESSAGE		: Total un read message count
		 * 				The above two properties are packed inside the 'RC_Folder' instance
		 * 				Return value will be Array of 'RC_Folder' instance
		 * 				No parameter expected from the request
		 * 
		 */
		public function get_folders_meta() {
		    
			$is_ok = true;
			$folders = array();
			$payload = RC()->request->get_payload();			
			foreach ($payload as $folder) { 
				if (RC()->receiver->select_folder($folder["name"])) {
					$is_ok = true;
					/* Instantiate RC_Folder */
					$rc_folder = new RC_Folder($folder["name"]);
					$tcount = RC()->receiver->count();
					
					if ($tcount !== false) {
						$rc_folder->set_total_message_count($tcount);
						$urcount = RC()->receiver->search("UNSEEN");
						if (is_array($urcount)) {
							$rc_folder->set_unread_message_count(count($urcount));
						} else {
							$rc_folder->set_unread_message_count(0);
						}
					} else {
						$is_ok = false;
					}
					if(!$is_ok) {
						/* Set the error response and break execution */
						RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array());
						return;
					}
					$folders[$folder["name"]] = $rc_folder;
				}
			}
			RC()->response = new RC_Response(true, "Folders Meta", 0, count($folders), $folders);	
			
		}
		
		/**
		 * 
		 * @desc		Same as 'get_folders_meta' but instead of returning 
		 * 				Array of 'RC_Folder' it return only one instance of 'RC_Folder'
		 * 
		 * 				Expect the following param from the request
		 * 				@folder		Name of the folder
		 * 
		 */
		public function get_folder_meta() {
		    
			$payload = RC()->request->get_payload();
			if ($payload["type"] == "folder") {
				if (RC()->receiver->select_folder($payload["folder"])) {
					$is_ok = true;
					/* Instantiate RC_Folder */
					$rc_folder = new RC_Folder($payload["folder"]);
					$tcount = RC()->receiver->count();
					if ($tcount !== false) {
						$rc_folder->set_total_message_count($tcount);
						$urcount = RC()->receiver->search("UNSEEN");
						if (is_array($urcount)) {
							$rc_folder->set_unread_message_count(count($urcount));
						} else {
							$rc_folder->set_unread_message_count(0);
						}
					} else {
						$is_ok = false;
					}
					if(!$is_ok) {
						/* Set the error response and break execution */
						RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array());
						return;
					}
				}
				RC()->response = new RC_Response(true, "Folder Meta", 0, 0, $rc_folder);
			} else {
				// Unlikely
				RC()->response = new RC_Response(false, "Mandatory parameter missing", 0, -1,  array());
			}
			
		}
		
		/**
		 * 
		 * @desc		Returns the requested atachment depends on the requested parameters either it will be an individual file
		 * 				or all files as a ZIP output
		 * 
		 * 				Expect the following param from the request
		 * 				@folder			Name of the folder that mail belongs to 
		 * 				@uid			Unique id of the message
		 * 				@msgno			Message sequence number
		 * 				@type			Type of the output - could be 'all' or 'single'
		 * 				@file			Name of the attached file		
		 * 
		 */
		public function get_attachments() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["folder"]) && isset($payload["uid"]) && isset($payload["msgno"])) {
				$filename = isset($payload["file"]) ? $payload["file"] : "";
				RC()->receiver->get_attachment($payload["type"], $payload["folder"], $payload["uid"], $payload["msgno"], $filename);
			} else {
				// Unlikely
				RC()->response = new RC_Response(false, "Mandatory parameter missing", 0, -1,  array());
			}
			
		}
		
		/**
		 * 
		 * @desc		As the name itself suggest, it create mail folder
		 * 				Works only on IMAP, not in POP3 as it doesn't has the notion of folder
		 * 
		 *  			Expect the following param from the request
		 *  			@name 		Name of the folder - ofcourse
		 * 
		 */
		public function create_folder() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["name"])) {
				if (RC()->receiver->add_folder($payload["name"])) {
					RC()->response = new RC_Response(true, "Created Successfully", 0, 0,  $payload["name"]);
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1,  $payload["name"]);
				}
			} else {
				// Unlikely
				RC()->response = new RC_Response(false, "Mandatory parameter missing", 0, 1,  array());
			}
			
		}
		
		/**
		 * 
		 * @desc		Rename the existing mail folder
		 * 				Option not available for POP3
		 * 				
		 * 				Expect the following param from the request
		 *  			@old_name 		Current name of the folder
		 *  			@new_name		Updated name 
		 *  
		 */
		public function rename_folder() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["old_name"]) && isset($payload["new_name"])) {
				if (RC()->receiver->rename_folder($payload["old_name"], $payload["new_name"])) {
					RC()->response = new RC_Response(true, "Created Successfully", 0, 0, array("old_name" => $payload["old_name"], "new_name" => $payload["new_name"]));
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, $payload["name"]);
				}
			} else { 
				// Unlikely
				RC()->response = new RC_Response(false, "Mandatory parameter missing", 0, -1, array());
			}
			
		}
		
		/**
		 * 
		 * @desc		Delete the given folder 
		 * 				Option not available for POP3
		 * 
		 * 				Expect the following param from the request
		 * 				@name		Name of the folder that has to be deleted
		 * 
		 */
		public function delete_folder() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["name"])) {
				if (RC()->receiver->remove_folder($payload["name"])) {
					RC()->response = new RC_Response(true, "Removed Successfully", 0, 0, $payload["name"]);
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, $payload["name"]);
				}
			} else {
				// Unlikely
				RC()->response = new RC_Response(false, "Mandatory parameter missing", 0, -1, array());
			}
			
		}
		
		/**
		 * 
		 * @desc		Does the bulk delete operation
		 * 				
		 * 				Expect the following param from the request
		 * 				@folder			Name of the folder on which the messages belongs to
		 * 				@uids			List of uid
		 * 
		 */
		public function delete_messages() {
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["folder"]) && isset($payload["uids"])) {
				if (RC()->receiver->select_folder($payload["folder"])) {
					if (RC()->receiver->delete_messages($payload["uids"])) {
						RC()->response = new RC_Response(true, "Messages deleted successfully", 0, 0, array("folder" => $payload["folder"], "uids" => $payload["uids"]));
					} else {
						RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array("folder" => $payload["folder"], "uids" => $payload["uids"]));
					}
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array("folder" => $payload["folder"], "uids" => $payload["uids"]));
				}
			} else {
				RC()->response = new RC_Response(false, "Mandatory param missing.!", 0, -1, array());
			}
			
		}
		
		public function move_message() {
		
		}
		
		/**
		 * 
		 * @desc		Does the bulk move operation
		 * 
		 * 				Expect the following param from the request
		 * 				@folder			Name of the folder on which the messages belongs to
		 * 				@uids			List of uid
		 * 
		 */
		public function move_messages() {  
		    
			$payload = RC()->request->get_payload();
			if (isset($payload["folder"]) && isset($payload["to"]) && isset($payload["uids"])) {
				if (RC()->receiver->select_folder($payload["folder"])) {
					if (RC()->receiver->move_messages($payload["uids"], $payload["to"])) {
						RC()->response = new RC_Response(true, "Moved successfully.!", 0, 0, array("folder" => $payload["folder"], "to" => $payload["to"], "uids" => $payload["uids"]));
					} else {
						RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array("folder" => $payload["folder"], "to" => $payload["to"], "uids" => $payload["uids"]));
					}
				} else {
					RC()->response = new RC_Response(false, imap_last_error(), 0, -1, array("folder" => $payload["folder"], "to" => $payload["to"], "uids" => $payload["uids"]));
				}
			} else {
				RC()->response = new RC_Response(false, "Mandatory param missing.!", 0, -1, array());
			}
			
		}
		
		private function update_mail_status() {
		
		}
		
		/**
		 * 
		 * @desc		Returns email address suggestion list
		 * 
		 * 				Expect the following param from the request
		 * 				@search			Search text entered by the 
		 * 
		 */
		private function get_user_suggestion() {
		
			$res = array();
			$needle = RC()->request->get_payload();
			$ulist = RC()->po->load_rc_us_list();
			if (isset($needle[ "search" ]) && is_array($ulist)) {
				foreach ($ulist as $email => $name) {
					$length = strlen( $needle[ "search" ] );
					if (substr($email, 0, $length) === $needle["search"]) {
						$res[$email] = $name;
					}
				}
			}
			RC()->response = new RC_Response(true, "User Suggestion List", 0, 1, $res);
		
		}
		
		/**
		 * 
		 * @param 		RC_ListerHeader $_headers
		 * @desc		Update the suggestion list
		 * 
		 */
		private function update_suggestion($_headers) {
		
			$all_adr = RC()->po->load_rc_us_list();
			if (!is_array($all_adr)) {
				$all_adr = array();
			}
			foreach ($_headers as $header) {
				$from = $header->get_from();
				if(!empty($from)) {
					$addr = "";
					$name = "";
					$temp = "";
					if (strpos($from, '>') !== false) {
						preg_match('~<(.*?)>~', $from, $temp);
						if (isset($temp[0])) {
							$addr = substr($temp[0], 1, -1);
							if (strpos($from, "\"") !== false) {
								$name = substr($from, 0, strpos($from, "\""));
							}
						}
					} else {
						$addr = $from;
					}
					$all_adr[$addr] = $name;
				}
			}		
			RC()->po->update_rc_us_list($all_adr);
			
		}
		
	}
	
	new RC_Mailer();
	
}

?>