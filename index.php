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
<html lang="en">

	<head>
		
		<meta charset="utf-8">
	  	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	  	
		<title>Login - Roller Coaster</title>
		
		<link rel="shortcut icon" href="include/assets/images/favicon.png" type="image/x-icon">
		<link type="text/css" rel="stylesheet" href="include/assets/styles/font-awesome.css">
		<link type="text/css" rel="stylesheet" href="include/assets/styles/rc-login.css">
		<script type="text/javascript" src="include/assets/scripts/lib/jquery.js"></script>
		
	</head>
	
	<body class="rc-login">
	
		<?php 
		
		session_start();
		$errorMsg = isset( $_SESSION["rc-error-msg"] ) ? $_SESSION["rc-error-msg"] : "";
		/* Reset the error message, so that it won;t repeat - incase if user refreshed the page */
		$_SESSION["rc-error-msg"] = "";
		
		?>
	
		<div class="rc-master-container">	
		
			<div class="rc-login-container">
			
				<div class="rc-login-header">
					<img src="include/assets/images/rc-logo.png" alt="RollerCoaster" />
				</div>
				
				<form method="post" action="/<?php echo basename(__DIR__); ?>/docker.php" id="rc-account-from" class="rc-account-from">
					<div class="rc-login-form-row">
						<input type="email" id="rc_user_email" class="rc-login-form-field" name="rc_user_email" value="" placeholder="Email" />	
						<label class="rc-login-form-field-msg">Please enter your valid email address</label>
					</div>					
					<div class="rc-login-form-row">
						<input type="password" id="rc_user_pass" class="rc-login-form-field" name="rc_user_pass" value="" placeholder="password" />
						<label class="rc-login-form-field-msg">Please enter your password</label>
					</div>								
					<input type="hidden" name="rc_login" value="yes" />		
					<input type="submit" value="Sign in" />
					<label><input type="checkbox" name="rc_keep_me_singed_in"> Keep me signed in</label>
					<?php if( $errorMsg != "" ) : ?>
					<div class="rc-login-error"><p><i class="fa fa-warning rc-blink-icon"></i> <?php echo $errorMsg; ?></p></div>
					<?php endif; ?>						
				</form>
			
			</div>		
					
		</div>
		
		<script type="text/javascript">

			var isInProgress = false;
			/* Mail address pattern regex */
			var pattern = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;

			function validateFields( _field ) {
				if( _field.attr( "type" ) == "email" ) {
					if( ! pattern.test( _field.val() ) ) {
						/* Invalid email */
						_field.next().show();
						_field.focus();
						return false;
					}
				} else {
					if( _field.val() == "" ) {
						/* Password fields seems empty */
						_field.next().show();
						_field.focus();
						return false;
					}
				}
				/* Seems everything good, so hide the error msg, incase */
				_field.next().hide();
				return true;
			}
		    
			$(document).ready(function(){
				/* Set the default focus to User Name */
				$( "#rc_user_email" ).focus();
				/* Capture the Enter key event and submit the form */
				$( document ).keypress( function(e) {
					if( e.which == 13 && ( ! isInProgress ) ) {
					    $('form#rc-account-from').submit();
					    e.preventDefault();
					    return false;
					}
				});
				/* Validate the fields on their blur event */
				$( 'input.rc-login-form-field' ).blur( function() {
					validateFields( $( this ) );
				});
				/* Validate the fields before form submission */
				$('form#rc-account-from').submit( function(e) {
					var goAhead = true;
					$( 'input.rc-login-form-field' ).each( function(e) {
						if( ! validateFields( $( this ) ) ) {
							goAhead = false;
						}
					});
					/* If everything ok then disable the submit button */
					if( goAhead ) {
						isInProgress = true;
						$('input[type="submit"]').attr('disabled','disabled');
					}
					return goAhead;
				});
			});
		</script>		
	
	</body>

</html>