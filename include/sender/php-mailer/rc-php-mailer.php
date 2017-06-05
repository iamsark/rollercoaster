<?php 

if ( ! defined( 'RC_INIT' ) ) { exit; }

if (!class_exists("RC_Smtp")) {
	
	class RC_Smtp {
		
		private $mailer;
		
		private $attachment;
		
		public function __construct() {
			
			/* Inject this module to global RC */
			RC()->inject("smtp", $this);
			/* Ajax action format : "rc_[module name]_[context name]_[task name]" */
			RC()->hook->listen_action("rc_email_smtp_send", array($this, "send"));
			RC()->hook->listen_action("rc_email_smtp_save", array($this, "save"));
			
		}
		
		public function send() {
			try {
				/* Load SMTP library
				 * We are using 'PHPMailer' ( https://github.com/PHPMailer/PHPMailer ) */
				$this->prepare_sender();
				/* Prepare the Message Properties */
				$this->prepare_mail();
				/**/
				$this->mailer->send();
			} catch ( phpmailerException $e ) {
				RC()->response = new RC_Response( false, $e->errorMessage(), 0, 0, null );
				return false;
			} catch ( Exception $e ) {
				RC()->response = new RC_Response( false, $e->getMessage(), 0, 0, null );
				return false;
			}
		
			RC()->response = new RC_Response( true, "Mail Sent Successfully", 0, 0, null );
			/* Ok now try move the sent mail to Sent Folder */
			$mail = $this->mailer->getSentMIMEMessage();
			$sent_folder = RC()->imap->get_sent_folder();
			RC()->imap->copy_to_folder( $sent_folder, $mail, "\\Seen" );
			return true;
		}
		
		public function save() {
			/* Load SMTP library
			 * We are using 'PHPMailer' ( https://github.com/PHPMailer/PHPMailer ) */
			$this->prepare_sender();
			/* Prepare the Message Properties */
			$this->prepare_mail();
			/**/
			$this->mailer->preSend();
			$mail = $this->mailer->getSentMIMEMessage();
			$draft_folder = RC()->imap->get_draft_folder();
			/* Save it in Draft Folder */
			return RC()->imap->copy_to_folder( $draft_folder, $mail, "\\Draft" );
		}
		
		private function prepare_sender() {
			/* Set the time zone to UTC */
			date_default_timezone_set( 'Etc/UTC' );
			/* Load PHP Mailer */
			require dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "php-mailer" . DIRECTORY_SEPARATOR . "PHPMailerAutoload.php";
			//Create a new PHPMailer instance
			$this->mailer = new PHPMailer;
			/* Load SMTP properties */
			//Tell PHPMailer to use SMTP
			$this->mailer->isSMTP();
			//Enable SMTP debugging
			// 0 = off (for production use)
			// 1 = client messages
			// 2 = client and server messages
			$this->mailer->SMTPDebug = 0;
			//Ask for HTML-friendly debug output
			$this->mailer->Debugoutput = 'html';
			/**/
			$this->mailer->Priority    = 1; // Highest priority - Email priority (1 = High, 3 = Normal, 5 =   low)
			/**/
			$this->mailer->CharSet     = 'UTF-8';
			/**/
			$this->mailer->Encoding    = '8bit';
			/**/
			$this->mailer->ContentType = 'text/html; charset=utf-8\r\n';
			//Set the hostname of the mail server
			$this->mailer->Host = RC()->context->get_sender_host();
			//Set the SMTP port number - likely to be 25, 465 or 587
			$this->mailer->Port = RC()->context->get_sender_port();
			//Whether to use SMTP authentication
			$this->mailer->SMTPAuth = true;
			//Enable TLS encryption, `ssl` also accepted
			$this->mailer->SMTPSecure = RC()->context->get_sender_security();
			//Username to use for SMTP authentication
			$this->mailer->Username = RC()->context->get_user();
			//Password to use for SMTP authentication
			$this->mailer->Password = RC()->context->get_password();
		}
		
		private function prepare_mail() {
			$payload = RC()->request->get_payload();
			if( isset( $payload[ "header" ][ "from" ] ) ) {
				foreach ( $payload[ "header" ][ "from" ] as $email => $name ) {
					$this->mailer->setFrom( $email, $name );
				}
			}
			if( isset( $payload[ "header" ][ "to" ] ) ) {
				foreach ( $payload[ "header" ][ "to" ] as $email => $name ) {
					$this->mailer->addAddress( $email, $name );
				}
			}
			if( isset( $payload[ "header" ][ "cc" ] ) ) {
				foreach ( $payload[ "header" ][ "cc" ] as $email => $name ) {
					$this->mailer->addCC( $email, $name );
				}
			}
			if( isset( $payload[ "header" ][ "bcc" ] ) ) {
				foreach ( $payload[ "header" ][ "bcc" ] as $email => $name ) {
					$this->mailer->addBCC( $email, $name );
				}
			}
			if( isset( $payload[ "header" ][ "subject" ] ) ) {
				$this->mailer->Subject = $payload[ "header" ][ "subject" ];
			}
			if( isset( $payload[ "body" ][ "html" ] ) ) {
				$this->mailer->msgHTML( RC_Helper::construct_html_doc( $payload[ "body" ][ "html" ] ) );
			}
			if( isset( $payload[ "body" ][ "plain" ] ) ) {
				$this->mailer->AltBody = $payload[ "body" ][ "plain" ];
			}
		}
	
	}
	
	new RC_Smtp();

}

?>