<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: Helper utility for the Theme manager
 * 				  Provides HTML template for various part of the theme
 **************************************************************/

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Template")) {

	class RC_Template {
		
		/**
		 * 
		 * @var 		array
		 * @desc		List of primary left navigation menu
		 * 
		 */
		public $primary_menu_items = array(
			"folders" => "Folders"
		);
		
		public function __construct() {
			/* Inject this module to global RC */
			RC()->inject("template", $this);
			/* Common template actions */
			RC()->hook->listen_action( "rc_head", array( $this, "inject_title" ) );
			RC()->hook->listen_action( "rc_head", array( $this, "enqueue_assets" ) );
			RC()->hook->listen_action( "rc_load_view", array( $this, "load_theme" ) );
			RC()->hook->listen_action( "rc_logo", array( $this, "inject_logo" ) );
			RC()->hook->listen_action( "rc_left_nav_section", array( $this, "inject_left_nav_container" ) );
			RC()->hook->listen_action( "rc_welcome_section", array( $this, "inject_welcome_screen" ) );
		}
		
		/**
		 * 
		 * @desc 		Loads the current theme into the main view
		 * 				If there is any issue, it redirect to default error page
		 * 
		 * 
		 */
		public function load_theme() {
			/* get the current theme directory name */
			$theme = RC()->theme->get_current_theme();
			if( $theme ) {
				/* Well load the index page of that theme */
				include( RC_THEME_DIR . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . "index.php" );
			} else {
				/* Redifrect to error page */
			}		
		}
		
		/**
		 * 
		 * @desc		Renders the title tag into from wherever the '' action got triggered ( usually on the head tag :) )
		 * 
		 */
		public function inject_title() {
			echo '<title>'. RC()->hook->trigger_filter( "rc_title", "RollerCoaster - An Open Source Mail Client" ) .'</title>';
		}
		
		/**
		 * 
		 * @desc		Loads all the core CSS and JS files
		 * 				into theme's main page ( or from wherever 'rc_head' action got triggered )
		 * 				It is the responsibility of the theme author to trigger 'rc_head' action
		 * 
		 */
		public function enqueue_assets() {  ob_start(); ?>
		
			<link rel="shortcut icon" href="<?php echo RC_ROOT_URL; ?>include/assets/images/favicon.png" type="image/x-icon">
		
			<link type="text/css" rel="stylesheet" href="<?php echo RC_ROOT_URL; ?>include/assets/styles/font-awesome.css">
		
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/lib/jquery.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/lib/moment.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/lib/ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/lib/mousetrap.min.js"></script>
			
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/binder/rc-fbinder.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/binder/rc-lbinder.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/binder/rc-vbinder.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/binder/rc-cbinder.js"></script>
			
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/view/rc-folder-view.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/view/rc-list-view.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/view/rc-viewer-view.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/view/rc-composer-view.js"></script>
			
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/model/rc-folder.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/model/rc-lister.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/model/rc-viewer.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/model/rc-composer.js"></script>
			
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/rc-events.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/rc-helper.js"></script>
			<script type="text/javascript" src="<?php echo RC_ROOT_URL; ?>include/assets/scripts/rc-controller.js"></script>
			
			<script type="text/javascript">
				var docker = '<?php echo RC_ROOT_URL; ?>docker.php';
				$( document ).ready( function() {
					rcControllerObj = new rcController();
					rcControllerObj.init();
				});
			</script>
			
		<?php
		
			echo ob_get_clean();
		
		}
		
		/**
		 * 
		 * @desc		Renders the main logo image 
		 * 				That top left corner
		 * 
		 */
		public function inject_logo() {
			echo '<img src="'. RC()->hook->trigger_filter( "rc_logo_img", RC_ROOT_URL. "include/assets/images/rc-logo.png" ) .'" title="Roller Coaster" alt="Roller Coaster" class="rc-logo-img" />';
		}
		
		/**
		 * 
		 * @desc		This is where the left side navigation bar gets injected
		 * 				Use '' filter to add your custom navigation menu item into left sidebar 	
		 * 
		 */
		public function inject_left_nav_container() { 
		
			RC()->hook->trigger_action( "rc_before_left_nav_section" );
			
			/**
			 *Trigger the 'rc_primary_menu_items' filter to allow other modules to add additonal menu item
			 *On the left side menu bar 
			 */
			$mitems = RC()->hook->trigger_filter("rc_primary_menu_items", $this->primary_menu_items); 
			foreach ($mitems as $menu => $label) { ?>
				
			<div class="rc-left-nav-section-bar">
				<h3 class="rc-left-nav-section-title"><?php echo $label; ?> <a href="#rc-<?php echo $menu; ?>-list" id="rc-left-nav-acc-<?php echo $menu; ?>-btn" class="rc-left-nav-accordian-btn"><i class="fa fa-caret-up"></i></a></h3>
				<ul id="rc-<?php echo $menu; ?>-list" class="rc-left-nav-list-container">
				<?php 
				/**
				 * Trigger 'rc_render_menu_item_container' so that other modules can inject their specific menu items here
				 */
				RC()->hook->trigger_action("rc_render_menu_item_container", $menu); ?>
				</ul>
			</div>		
				
			<?php 
			
			}
			
			RC()->hook->trigger_action( "rc_after_left_nav_section" );
		
		}
		
		/**
		 * 
		 * @return		string	( Echoed it )
		 * @desc		Responsible for constructing welcome section
		 * 
		 */
		public function inject_welcome_screen() {
			$html = '<div id="rc-mail-welcome-screen">';	
			$html .= '<img class="rc-mail-welcome-logo" src="'. RC_ROOT_URL. 'include/assets/images/rc-welcome-logo.png" title="Roller Coaster" />';
			$html .= '<h1 class="rc-mail-welcome-title">Thank you for choosing RollerCoaster</h1>';
			$html .= '<p>This is a project to fulfill our own expectations from a web mail client, we tried many open source web mail clients out there, but none of them satisfy our needs, so we decided to build one.</p>';
			$html .= '<p>An open source web based mail client with great usability and yet remains light weight. It simple to use, only has whatever an user expect from a mail client. No dependencies needed ( Not even a Data Base ), just extract it into your web server\'s html folder and you are good to go.</p>';
			$html .= '<p>As far as setup concerns, Roller Coaster comes with two modules, <strong>Admin Module</strong> where you will be setting up your Mail Servers, and the <strong>Mail Client Module</strong> itself. RollerCoaster has support for Imap & Pop3 and sending mail through SMTP.</p>';
			$html .= '<p>Apart from sending and receiving mails It also support Presets ( a kind of folder where you can apply various filters ) and Labels using which you can tag mails with colours.</p>';
			
			$html .= '<div class="rc-mail-copyright-bar">';
			$html .= '<table class="rc-mail-copyright-table">';
			$html .= '<tr>';
			$html .= '<td>';
			$html .= '<div>Developed by : <span><a href="http://iamsark.com" title="Saravana Kumar K Profile" target="_blank" rel="bookmark" class="logout">Saravana Kumar K</a></span></div>';
			$html .= '<div>UI Design : <span>Paranjothi G</span></div>';
			$html .= '<div>© '. date('Y') .' <a href="https://sarkware.com" title="Sarkware" target="_blank" rel="bookmark" class="logout">Sarkware Pvt Ltd</a> All rights reserved</div>';
			$html .= '</td>';
			$html .= '<td class="rc-mail-copyright-links-bar">';
			$html .= '<div><strong>Links :</strong> <a href="#">RollerCoaster Home</a> <a href="#">Contact Us</a></div>';
			$html .= '<div><strong>Follow Us :</strong> <a href="#" title="Sarkware"><i class="fa fa-facebook"></i> Facebook</a> <a href="#" title="Sarkware"><i class="fa fa-twitter"></i> Twitter</a> <a href="#" title="Sarkware"><i class="fa fa-linkedin"></i> LinkedIn</a></div>';
			$html .= '</td>';
			$html .= '</tr>';
			$html .= '</table>';				
			$html .= '<div>';
			$html .= '</div>';
			echo $html;
		}
		
		/**
		 * 
		 * @return 		string ( Echo it )
		 * @desc		Renders the bulk action list items
		 * 				Use 'rc_bulk_action_list' filter to add your custom bulk actions
		 */
		public function load_bulk_actions() {
			$actions = RC()->hook->trigger_filter("rc_bulk_action_list", array(
				"move" => array( "label" => "Move", "tip" => "Move selected mails to", "icon" => "exchange" ),
				"delete" => array( "label" => "Delete", "tip" => "Remove selected mails", "icon" => "times" ),
				"mark-read" => array( "label" => "Mark as Read", "tip" => "Mark selected mails as Read", "icon" => "envelope-open-o" ),
				"mark-unread" => array( "label" => "Mark as Unread", "tip" => "Mark selected mails as Unread", "icon" => "envelope-o" ),
				"mark-flag" => array( "label" => "Mark as Flagged", "tip" => "Mark selected mails as Flagged", "icon" => "flag" ),
				"mark-unflag" => array( "label" => "Mark as Unflagged", "tip" => "Mark selected mails as Unflagged", "icon" => "flag-o" )
			));	
			
			$tip = '';
			$icon = '';
			$label = '';
			$html = '';
			foreach ( $actions as $action => $prop ) {
				$icon = (isset($prop["icon"]) && $prop["icon"] != "" ) ? $prop["icon"] : "";
				$tip = (isset($prop["tip"]) && $prop["tip"] != "" ) ? $prop["tip"] : "";
				$label = (isset($prop["label"]) && $prop["label"] != "" ) ? $prop["label"] : $action;
				$html .= '<a href="#" data-action="'. $action .'" title="'. $tip .'"><i class="fa fa-'. $icon .'"></i> '. $label .'</a>';
			}
			echo $html;
		}
		
		/**
		 * 
		 * @return		string ( Echo it )
		 * @desc		Renders the search type list items
		 * 				Use 'rc_search_type_list' filter to add your custom search types 
		 * 
		 */
		public function load_search_types() {
			$types = RC()->hook->trigger_filter("rc_search_type_list", array(
				"to" => array("label" => "To", "checked" => false),
				"from" => array("label" => "From", "checked"=> false),
				"subject" => array("label" => "Subject", "checked" => true),
				"content" => array("label" => "Content", "checked" => false)
			));
			
			$html = '';
			$label = '';
			$checked = '';
			foreach ($types as $type => $prop) {
				$checked = (isset($prop["checked"]) && $prop["checked"]) ? "checked" : "";
				$label = (isset($prop["label"]) && $prop["label"] != "") ? $prop["label"] : $type;				
				$html .= '<label><input type="radio" name="rc-mail-search-type-item" value="'. $type .'" '. $checked .' /> '. $label .'</label>';
			}
			echo $html;
		}
	
	}
	
	new RC_Template();

}

?>