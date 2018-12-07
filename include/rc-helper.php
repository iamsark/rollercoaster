<?php 

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Helper")) {

	class RC_Helper {
		
		/* Logger constants */
		const LOGGER_INFO = 0;
		const LOGGER_WARNNING = 1;
		const LOGGER_ERROR = 2;
		
		/* Net constants */
		const CONNECTOR_NONE = 0;
		const CONNECTOR_SSL = 1;
		const CONNECTOR_STARTTLS = 2;
		const CONNECTOR_AUTO_DETECT = 9;
		
		/* Tags that has to be removed from html response before sending it to Client */
		private $unsafe_tags = array (
			"script",
			"audio",
			"video",
			"object",
			"canvas"
		);
		
		public function __construct() {
			/* Inject this module to global RC */
			RC()->inject("helper", $this);
		}
		
		/**
		 * 
		 * @param 		string $_str
		 * @return 		string
		 * @desc		Strip all Double Quote from a given string
		 * 
		 */
		public function strip_quotes($_str) {
			return trim(trim(trim($_str)), '"');
		}
		
		/**
		 * 
		 * @param 		string $_html
		 * @return 		mixed
		 * 
		 */
		public function strip_unsafe_attrs($_html) {
			$unsafe_attrs = array (
				'/on(Blur)/si',
				'/on(Change)/si',
				'/on(Click)/si',
				'/on(DblClick)/si',
				'/on(Error)/si',
				'/on(Focus)/si',
				'/on(KeyDown)/si',
				'/on(KeyPress)/si',
				'/on(KeyUp)/si',
				'/on(Load)/si',
				'/on(MouseDown)/si',
				'/on(MouseEnter)/si',
				'/on(MouseLeave)/si',
				'/on(MouseMove)/si',
				'/on(MouseOut)/si',
				'/on(MouseOver)/si',
				'/on(MouseUp)/si',
				'/on(Move)/si',
				'/on(Resize)/si',
				'/on(ResizeEnd)/si',
				'/on(ResizeStart)/si',
				'/on(Scroll)/si',
				'/on(Select)/si',
				'/on(Submit)/si',
				'/on(Unload)/si'
			);
			return preg_replace($unsafe_attrs, 'оn\\1', $_html);
		}
		
		/**
		 * 
		 * @param 		string $_html
		 * @return 		string
		 * @desc		Strip unsafe tags from given html body
		 * 				"script", "audio", "video", "object" and "canvas" are Unsafe tags 
		 * 
		 */
		public function strip_unsafe_tags($_html) {
			if ($_html != "") {
				$doc = new DOMDocument();
				// load the HTML string we want to strip
				$doc->loadHTML($this->strip_unsafe_attrs($_html));
				/* Give other modules chance to add additional tags to be stripped down from HTML body */
				$us_tags = RC()->hook->trigger_filter("rc_unsafe_tags", $this->unsafe_tags);
				/* Loop over all the unsafe tags and remove them from DOM */
				foreach ($us_tags as $tag) {
					$tags = $doc->getElementsByTagName($tag);
					$length = $tags->length;
					for ($i = 0; $i < $length; $i++) {
						$tags->item($i)->parentNode->removeChild($tags->item($i));
					}
				}
				/* Return the safe HTML */
				return $doc->saveHTML();
			}
			return $_html;
		}
		
		/**
		 * 
		 * @param 		string $_html
		 * @return 		mixed
		 * @desc		It strip down the HTML body into normal string
		 * 				Not exactly, it still HTML string only but without 'html', 'head', 'body' tags 
		 * 				Also we will stripped down the all unsafe tags here
		 * 
		 */
		public function html_to_string($_html) {
			$safe_html = $this->strip_unsafe_tags($_html);
			/* Remove head tag */
			$safe_html = preg_replace('/<head([^>]*)>/im', '', $safe_html);
			$safe_html = preg_replace('/<\/head>/im', '', $safe_html);
			/* Remove body tag */
			$safe_html = preg_replace('/<body([^>]*)>/im', '<div class="rc-mail-body"\\1>', $safe_html);
			$safe_html = preg_replace('/<\/body>/im', '</div>', $safe_html );
			/* Remove html tag */
			$safe_html = preg_replace('/<html([^>]*)>/im', '<div class="rc-mail-html"\\1>', $safe_html);
			$safe_html = preg_replace('/<\/html>/im', '</div>', $safe_html);
			/* Remove DOCTYPE tag */
			$safe_html = preg_replace('/^<!DOCTYPE.+?>/', '', $safe_html);
			/* Replpace body css selector with '.rc-mail-body' class */
			//$safe_html = str_ireplace("body", ".rc-mail-body", $safe_html);
		
			return $safe_html;
		}
		
		/**
		 * 
		 * @param 		string $_msg
		 * @return 		string
		 * @desc		Turn string message into Fully qualified HTML Doc content
		 * 				Nothing magic here, we just wrap it with 'html', 'head' and 'body' tags
		 * 
		 */
		public static function construct_html_doc($_msg) {
			/* Remove any non printable character */
			$_msg = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $_msg);
			$doc = new DOMDocument();
			$doc->encoding = 'UTF-8';
			$doc->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body class="rc-message-body">'.$_msg.'</body></html>');
			return $doc->saveHTML();
		}
		
		/**
		 * 
		 * @param 		number $_port
		 * @param 		number $_security_type
		 * @return 		boolean
		 * @desc		Determine whether SSL can be used
		 * 
		 */
		public static function use_ssl($_port, $_security_type) {
			$_port = (int) $_port;
			$result = (int) $_security_type;
			if (self::CONNECTOR_AUTO_DETECT === $_security_type) {
				switch (true) {
					case 993 === $_port:
					case 995 === $_port:
					case 465 === $_port:
						$result = self::CONNECTOR_SSL;
						break;
				}
			}
			if (self::CONNECTOR_SSL === $result && !in_array('ssl', stream_get_transports())) {
				$result = self::CONNECTOR_NONE;
			}
			return self::CONNECTOR_SSL === $result;
		}
		
		/**
		 * 
		 * @param 		boolean $_supported
		 * @param 		number $_security_type
		 * @param 		string $_has_supported_auth
		 * @return 		boolean
		 * @desc		Determine whether STARTTLS can be used
		 * 
		 */
		public static function use_start_tls($_supported, $_security_type, $_has_supported_auth = true) {
			return ($_supported &&
					(self::CONNECTOR_STARTTLS === $_security_type || (self::CONNECTOR_AUTO_DETECT === $_security_type && ! $_has_supported_auth)) &&
					defined('STREAM_CRYPTO_METHOD_TLS_CLIENT') && self::function_exists('stream_socket_enable_crypto'));
		}
		
		/**
		 * 
		 * @param 		string $_fname
		 * @return 		boolean
		 * @desc		Determine whether the function is exist and it's callable
		 * 
		 */
		public static function function_exists($_fname) {
			return (function_exists($_fname) && is_callable($_fname));
		}
		
		/**
		 * 
		 * @param 		string $_message
		 * @param 		string $_type
		 * @desc		Common logging handler, does nothing special but put proper prefix for each message we tried to log 
		 * 
		 */
		public static function log($_message, $_type = self::LOGGER_INFO) {
			$prefix = "";
			if (self::LOGGER_INFO == $_type) {
				$prefix = "[INFO] ";
			} else if (self::LOGGER_WARNNING == $_type) {
				$prefix = "[WARNING] ";
			} else {
				$prefix = "[ERROR] ";
			}
			error_log( $prefix . $_message );
		}
		
		/**
		 * 
		 * @param 		string $_name
		 * @param 		string $_initial
		 * @return 		boolean
		 * @desc		Check if the given file name exist other wise create one
		 * 				File name should be fully qualified file path
		 * 				$_initial param will be json_encode if it is not a string value
		 * 
		 */
		public static function create_file($_name, $_initial="") {
			if (!file_exists($_name)) {
				$F = null;
				try {
					if (!is_string($_initial)) {
						$_initial = json_encode($_initial);
					}
					$F = fopen($_name, "w");
					fwrite($F, $_initial);
					fclose($F);
				} catch (Exception $e) {
					$this->log("File manipulation Error : ". $e->getMessage(), RC()->helper::LOGGER_ERROR);
					return false;
				}
			}
			return true;
		}
		
		/**
		 * 
		 * @param 		string $_fname
		 * @return 		string
		 * @desc		Extract the file extenstion from the given file name
		 * 
		 */
		public static function get_file_extension($_fname) {
			$dot_pos = strrpos($_fname, '.');
			return false === $dot_pos ? '' : substr($_fname, $dot_pos + 1);
		}
		
		/**
		 * 
		 * @param 		: int $_eno
		 * @return 		: string
		 * @desc		: Takes error number as the result of json_decode() op and returns the string reperesentation of that error number 
		 */
		public function get_json_parse_error( $_eno ) {
			
			$error_str = "";
			
			switch ( $_eno ) {
				case JSON_ERROR_NONE:
					$error_str = "No error has occurred";
					break;
				case JSON_ERROR_DEPTH:
					$error_str = "The maximum stack depth has been exceeded";
					break;
				case JSON_ERROR_STATE_MISMATCH:
					$error_str = "Invalid or malformed JSON";
					break;
				case JSON_ERROR_CTRL_CHAR:
					$error_str = "Control character error, possibly incorrectly encoded";
					break;
				case JSON_ERROR_SYNTAX:
					$error_str = "Syntax error";
					break;
				case JSON_ERROR_UTF8:
					$error_str = "Malformed UTF-8 characters, possibly incorrectly encoded";
					break;
				case JSON_ERROR_RECURSION:
					$error_str = "One or more recursive references in the value to be encoded";
					break;
				case JSON_ERROR_INF_OR_NAN:
					$error_str = "One or more NAN or INF values in the value to be encoded";
					break;
				case JSON_ERROR_UNSUPPORTED_TYPE:
					$error_str = "A value of a type that cannot be encoded was given";
					break;
				case JSON_ERROR_INVALID_PROPERTY_NAME:
					$error_str = "A property name that cannot be encoded was given";
					break;
				case JSON_ERROR_UTF16:
					$error_str = "Malformed UTF-16 characters, possibly incorrectly encoded";
					break;
			}
			
			return $error_str;
			
		}
		
		public static function mime_to_string($_id) {
			switch ( $_id ) {
				case 0:
					return 'text';
				case 1:
					return 'multipart';
				case 2:
					return 'message';
				case 3:
					return 'application';
				case 4:
					return 'audio';
				case 5:
					return 'image';
				case 6:
					return 'video';
				default:
				case 7:
					return 'other';
			}
		}
		
		/**
		 *
		 * @return		void
		 * @desc		Ping the email server, used to validate the Imap or Smtp server details.
		 * 				Used to serve Admin's client module - while trying to register new Imap and Smtp server
		 *  
		 **/
		private function ping_server() {
			$errorStr = '';
			$errorNo = 0;
			$connection = null;
			$payload = RC()->request->get_payload();
		
			$streamSettings = array (
				'ssl' => array (
					'verify_host' => false,
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);
		
			$streamContext = stream_context_create($streamSettings);
		
			try {
				$connection = stream_socket_client($payload["host"].':'.$payload["port"], $errorNo, $errorStr, 15, STREAM_CLIENT_CONNECT, $streamContext);
			} catch (Exception $e) {
				$errorStr = $e->getMessage();
				$errorNo = $e->getCode();
			}
		
			if($connection) {
				stream_set_timeout($connection, 10);
				RC()->response = new RC_Response(true, "Looks Fine.!", 0, 0, array());
			} else {
				RC()->response = new RC_Response(false, "Not able to connect.!", 0, 0, array());
			}
		
			return;
		}
		
		public static function strtotime($_date, $_timezone = null) {
		    $_date   = self::clean_datestr($_date);
		    $tzname = $_timezone ? ' ' . $_timezone->getName() : '';
		    
		    // unix timestamp
		    if (is_numeric($_date)) {
		        return (int) $_date;
		    }
		    
		    // It can be very slow when provided string is not a date and very long
		    if (strlen($_date) > 128) {
		        $_date = substr($_date, 0, 128);
		    }
		    
		    // if date parsing fails, we have a date in non-rfc format.
		    // remove token from the end and try again
		    while (($ts = @strtotime($_date . $tzname)) === false || $ts < 0) {
		        if (($pos = strrpos($_date, ' ')) === false) {
		            break;
		        }
		        
		        $_date = rtrim(substr($_date, 0, $pos));
		    }
		    
		    return (int) $ts;
		}
		
		public static function compressMessageSet($_messages, $_force=false) {
		    // given a comma delimited list of independent mid's,
		    // compresses by grouping sequences together
		    if (!is_array($_messages)) {
		        // if less than 255 bytes long, let's not bother
		        if (!$_force && strlen($_messages) < 255) {
		            return preg_match('/[^0-9:,*]/', $_messages) ? 'INVALID' : $_messages;
		        }
		        
		        // see if it's already been compressed
		        if (strpos($_messages, ':') !== false) {
		            return preg_match('/[^0-9:,*]/', $_messages) ? 'INVALID' : $_messages;
		        }
		        
		        // separate, then sort
		        $_messages = explode(',', $_messages);
		    }
		    
		    sort($_messages);
		    
		    $result = array();
		    $start  = $prev = $_messages[0];
		    
		    foreach ($_messages as $id) {
		        $incr = $id - $prev;
		        if ($incr > 1) { // found a gap
		            if ($start == $prev) {
		                $result[] = $prev; // push single id
		            }
		            else {
		                $result[] = $start . ':' . $prev; // push sequence as start_id:end_id
		            }
		            $start = $id; // start of new sequence
		        }
		        $prev = $id;
		    }
		    
		    // handle the last sequence/id
		    if ($start == $prev) {
		        $result[] = $prev;
		    }
		    else {
		        $result[] = $start.':'.$prev;
		    }
		    
		    // return as comma separated string
		    $result = implode(',', $result);
		    
		    return preg_match('/[^0-9:,*]/', $result) ? 'INVALID' : $result;
		}
		
		public static function uncompressMessageSet($_messages) {
		    if (empty($_messages)) {
		        return array();
		    }
		    
		    $result   = array();
		    $_messages = explode(',', $_messages);
		    
		    foreach ($_messages as $idx => $part) {
		        $items = explode(':', $part);
		        $max   = max($items[0], $items[1]);
		        
		        for ($x=$items[0]; $x<=$max; $x++) {
		            $result[] = (int)$x;
		        }
		        unset($_messages[$idx]);
		    }
		    
		    return $result;
		}
		
	}

	new RC_Helper();
	
}

?>