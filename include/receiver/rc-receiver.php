<?php 

/**
 *
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core Interface
 * @desc		: Interface for RC Receiver implementation
 * 				  Whatever implementation we have for receiver module it has to implement this interface
 * 				  ( either php imap or rc scket ) to provide a common API for the rest of the modules
 *
 **/

interface RC_Receiver {
	
	/**
	 * 
	 * @return		boolean
	 * @param		string $_folder
	 * @param		boolean $_force
	 * @desc		Open connection with mail host.
	 * 				Uses RC_RHostMeta from user session for connection parameters.
	 * 				If succeed return 'true' otherwise 'false'.
	 * 				It accept optional folder name too, incase if you need to open a connection for specific folder.
	 * 				$_force param is used to force to create new connection ( existing connection will be closed )
	 * 
	 */
	public function connect($_folder="", $_force=false);
	
	/**
	 * 
	 * Disconnect from Mail Host
	 * 
	 */
	public function disconnect();
	
	/**
	 * 
	 * @return		boolean
	 * @desc		Check whether the connection stram is still alive.
	 * 
	 */
	public function is_connected();
	
	/**
	 * 
	 * @return		resource|null
	 * @desc		Return the connection stream if it is active otherwise NULL.
	 * 
	 */
	public function get_stream();
	
	/**
	 * 
	 * @return		integer|NULL
	 * @desc		Returns the '$uid' property ( Place holder for the Message UID ).
	 * 				Used especially while fecthing message body and attachments.
	 * 
	 */
	public function get_uid();
	
	/**
	 * 
	 * @return		RC_Folder|boolean
	 * @desc		Fetch the mail folder list from the host and
	 * 				returns the array of "RC_Folder" instance.
	 * 				returns false otherwise.				
	 * 
	 */
	public function fetch_message_folders();
	
	/**
	 * 
	 * @return		boolean
	 * @param 		string $_folder
	 * @desc		Makes the given folder as current folder.
	 * 
	 */
	public function select_folder($_folder);
	
	/**
	 * 
	 * @return		boolean
	 * @param 		string $_new_folder
	 * @desc		Creates a new folder in the host.
	 * 
	 */
	public function add_folder($_new_folder);
	
	/**
	 * 
	 * @return		boolean
	 * @param		string $_folder
	 * @desc		Delete the mentioned folder from the host.
	 * 
	 */
	public function remove_folder($_folder);
	
	/**
	 * 
	 * @param 		string $_old_name
	 * @param 		string $_new_name
	 * @desc		Rename an existing folder.
	 * 
	 */
	public function rename_folder($_old_name, $_new_name);
	
	/**
	 * 
	 * @return		boolean
	 * @desc		Truncate the folder ( Delete all message from the folder ).
	 * 				Select the folder ( which you want to truncate ) before doing this.
	 * 
	 */
	public function empty_folder();
	
	/**
	 * 
	 * @return		string|boolean
	 * @desc		Return the trash folder's name ( not display name ).
	 * 				If trash folder not there already then create a new folder with the name 'Trash'.
	 * 				and returns the name. returns 'false' if anything goes wrong. 
	 * 
	 */
	public function get_trash();
	
	/**
	 * 
	 * @return		string|boolean
	 * @desc		Return the sent folder's name ( not display name ).
	 * 				If trash folder not there already then create a new folder with the name 'Sent'.
	 * 				and returns the name. returns 'false' if anything goes wrong.
	 * 
	 */
	public function get_sent();
	
	/**
	 *
	 * @return		string|boolean
	 * @desc		Return the draft folder's name ( not display name ).
	 * 				If trash folder not there already then create a new folder with the name 'Draft'.
	 * 				and returns the name. returns 'false' if anything goes wrong.
	 *
	 */
	public function get_draft();
	
	/**
	 * 
	 * @param 		string $_folder
	 * @param 		string $_mail
	 * @param 		string $_flaq
	 * @desc		Copy the given message to the particular folder.
	 * 				Used while after sending the message we need to move it to Sent Folder.
	 * 				Same way while user leave composer view without sending the mail we need to store the message in the Draft folder.
	 * 
	 */
	public function copy_to($_folder, $_mail, $_flaq = "");
	
	/**
	 * 
	 * @desc		Returns the total message count of the selected folder.
	 * 
	 */
	public function count();
	
	/**
	 * 
	 * @return		RC_ListerHeader ( Array )
	 * @param 		string $_criterias
	 * @desc		Search criteria ( eg. subject, from, to, body date ... ).
	 * 
	 */
	public function search($_criterias);
	
	/**
	 * 
	 * @return		RC_ListerHeader ( Array )
	 * @param 		number $_start_index
	 * @param 		number $_end_index
	 * @param 		string $_sort
	 * @desc		Fetch the list of message headers ranging from $_start_index to $_end_index.
	 * 				It then parse all the headers into RC_ListerHeader and return it.
	 * 				Note you have to select the intended folder before try to fetching
	 * 
	 */
	public function fetch_message_headers($_start_index=0, $_end_index=0, $_sort);
	
	/**
	 * 
	 * @return		RC_Message|boolean
	 * @param 		number $_uid
	 * @param 		number $_msgno
	 * @desc		Fetch the message for the given $_uid.
	 * 				It goes over recursively and fetch all parts of message.
	 * 				It returns the complete message ( Header, Body & Attachments ).
	 * 				Returns false if there is any issues on fetching
	 * 
	 */
	public function fetch_message($_uid, $_msgno);
	
	/**
	 * 
	 * @return		boolean
	 * @param 		array $_uids
	 * @desc		Move multiple messages from the current folder to trash.
	 * 
	 */
	public function delete_messages($_uids);

	/**
	 *
	 * @return		boolean
	 * @param 		number $_uid
	 * @param 		string $_to
	 * @desc		Move mulitple messages from the current folder to target folder.
	 *
	 */
	public function move_messages($_uids, $_to);
	
	/**
	 * 
	 * @return		boolean
	 * @param 		boolean $_val
	 * @desc		Update Seen flag.
	 * 
	 */
	public function update_flag($_uid, $_flag, $_val);
	
	/**
	 * 
	 * @return		ZIP|FILE|RC_Response
	 * @param 		string $_type
	 * @param 		string $_folder
	 * @param 		number $_uid
	 * @param 		number $_msgno
	 * @param 		string $_filename
	 * @desc		Fetch the attachment part of the message and return the file.
	 * 				Depends on the $_type option wither it will return single file or all file as a ZIP archive.
	 * 
	 */
	public function get_attachment($_type="all", $_folder, $_uid, $_msgno, $_filename="");
	
}

?>