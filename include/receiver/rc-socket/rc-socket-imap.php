<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: 
 *
 */

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_SocketReceiver")) {

    class RC_SocketReceiver implements RC_Receiver {
        
        /* Name for this implementation */
        private $name = "";
        
        /* Url of the connected Imap Server */
        private $host = null;
        
        /* Imap server connection object */
        private $socket = null;
        
        /**/
        private $options = array();
        
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
            $this->name = "socket";
            /* Inject this module to global RC */
            RC()->inject("receiver", $this);
            
            /**/
            $this->options = array(
                'auth_type' => 'check', 
                'language' => 'en_US',
                'retry' => 5,
                'timeout' => 100                
            );
            
            /* Initialize the socket */
            $this->socket = new RC_ImapSocket();
        }
        
        public function __destruct() {
            
        }
        
        public function connect($_folder = "", $_force = false) {
            if ($this->socket->connected() && !$_force) {                
                return true;
            }
            /* Before open a new connection with given folder ( If given any )
             * Make sure existing connection is closed - if it still opened */
            if($_force) {
                $this->disconnect();
            }            
            /* Ok now we are safe to open the connection */
            if (RC()->context) {
                if ($this->prepare_host()) {                    
                    $attempt = 0;
                    $host_meta = RC()->context->get_receiver();
                    $port = $host_meta->get_port();          
                    $this->options['ssl_mode'] = $host_meta->get_security();
                    
                    // check for OpenSSL support in PHP build
                    if ($host_meta->get_security() != "notls" && extension_loaded('openssl')) {
                        $this->options['ssl_mode'] = $host_meta->get_security() == 'imaps' ? 'ssl' : $host_meta->get_security();
                    } else if ($host_meta->get_security() != "notls") {                                                
                        $port = 143;
                        RC_Helper::log("Openssl not available", RC_Helper::LOGGER_WARNNING);
                    }
                    
                    $this->options['port'] = $port;                    
                    $user = RC()->context->get_user();
                    
                    do {
                        $this->options = RC()->hook->trigger_filter(
                            'rc_host_connecting', 
                            array_merge($this->options, array('host' => $host_meta->get_host(), 'user' => $user, 'attempt' => ++$attempt))
                        );
                        $this->socket->connect($host_meta->get_host(), $user->get_email(), $user->get_password(), $this->options);                        
                    } while(!$this->socket->connected() && $attempt < $this->options['retry']);
                    
                    if (!$this->socket->connected()) {
                        RC_Helper::log("User not found on context", RC_Helper::LOGGER_ERROR);
                    } else {
                        error_log("login succeed");
                        $this->list_folders();
                    }
                }
            } else {
                RC_Helper::log("Attempting to connect with imap server before context ready", RC_Helper::LOGGER_ERROR);
            }
            /* If reached here then something not well */
            return $this->socket->connected();
        }
        
        public function is_connected() {
            return $this->socket->connected();
        }
    
        public function disconnect() {
            return $this->socket->close();
        }
    
        public function rename_folder($_old_name, $_new_name) {
            
        }
    
        public function select_folder($_folder) {
            
        }
    
        public function list_folders() {
            if (!$this->is_connected()) {
                return null;
            }
            
            /* Array container for holding list of RC_Folder instances */
            $rc_folders = array();
            $folders = $this->socket->listMailboxes("", "*");
            error_log(json_encode($result));
                 
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
            
            
            error_log("Total Message : ". $this->socket->countMessages($result[0]));
            error_log("Unseen Message : ".$this->socket->countUnseen($result[0]));
            error_log("Recent Message : ".$this->socket->countRecent($result[0]));
            
            $sort_field = null;
            
            
            $index = $this->socket->index($result[0], "1:*",
                $sort_field, RC()->config['skip_deleted']);
            
            error_log(json_encode($index->get()));
        }
    
        public function list_headers($_start_index = 0, $_end_index = 0, $_sort) {
            $headers = $this->conn->fetchHeaders(
                $folder, $msgs, true, false, $this->get_fetch_headers());
        }
    
        public function delete_messages($_uids) {
            
        }
    
        public function count() {
            
        }
    
        public function get_trash() {
            
        }
    
        public function update_flag($_uid, $_flag, $_val) {
            
        }
    
        public function get_uid() {
            
        }
    
        public function get_attachment($_type = "all", $_folder, $_uid, $_msgno, $_filename = "") {
            
        }
    
        public function get_stream() {
            
        }
    
        public function get_sent() {
            
        }
    
        public function copy_to($_folder, $_mail, $_flaq = "") {
            
        }
    
        public function search($_criterias) {
            
        }
    
        public function move_messages($_uids, $_to) {
            
        }
    
        public function empty_folder() {
            
        }
    
        public function fetch_message($_uid, $_msgno) {
            
        }
    
        public function add_folder($_new_folder) {
            
        }
    
        public function get_draft() {
            
        }
    
        public function remove_folder($_folder) {
            
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
    
    new RC_SocketReceiver();

}

?>