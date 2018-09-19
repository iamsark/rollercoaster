<?php 
/* Load the bootstrap module */
require_once dirname( dirname(__FILE__) ) . DIRECTORY_SEPARATOR . "include". DIRECTORY_SEPARATOR . "rc-loader.php";
/* Well initiate the Roller Coaster Environment */
RC()->load_environment();

if( ! RC()->session->get( "RCUSR" ) ) {
	/* Sent to Login page */
	RC()->theme->sent_to_login_page();
}

?>

<!DOCTYPE HTML>
<!-- 
****************************************************************************************
****************************************************************************************
Author : Saravana Kumar K,						              					 
Copyright : Sarkware Pvt Ltd.														 
URL 	: https://sarkware.com													 
****************************************************************************************
****************************************************************************************
-->
<html lang="<?php echo RC()->hook->trigger_filter( "rc_lang", "en" ); ?>">

	<head>
	
		<meta charset="utf-8">
	  	<meta name="viewport" content="width=device-width, initial-scale=1.0">		
		
		<script type="text/javascript">
			<?php $user = RC()->context->get_user() ?>
			var rc_user = "<?php echo $user->get_email(); ?>";
			var rc_print_logo = "<?php echo RC_ROOT_URL; ?>include/assets/images/rc-print-logo.png";
		</script>
		
		<?php 
		/**
		 * This action is responsible for 
		 * 	1. Inject title tag
		 * 	2. Inject fav icon
		 *  3. Inject Core Javascripts & Css Libs
		 *  4. Also if any other module wants to inject their own assets, they can listen for this action
		 */
		RC()->hook->trigger_action( "rc_head" ); ?>
		
	</head>
	
	<body class="<?php echo RC()->hook->trigger_filter( "rc_body_class", "" ); ?>">
						
		<?php
		/**
		 * This action is responsible for Loading the active theme's template
		 */
		RC()->hook->trigger_action( "rc_load_view" ); ?>
			
	</body>
	
</html>