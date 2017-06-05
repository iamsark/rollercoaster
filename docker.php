<?php 
/**
 * @author		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: Roller Coaster core Ajax handler. common hub for all ajax related actions of RC
 *
 *
 */

/* Load the bootstrap module */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "include". DIRECTORY_SEPARATOR . "rc-loader.php";
/* Well initiate the Roller Coaster Environment */
RC()->load_environment();
/* Check whether user is already authenticated */
if (defined("RC_GOAHEAD") && RC_GOAHEAD) {	
	/* User has valid session */
	if (isset($_REQUEST["rc_param"])) {
		/* Parse the incoming request */
		RC()->request = new RC_Request();
		if (RC()->request->parse($_REQUEST["rc_param"])) {			
			RC()->hook->trigger_action("rc_before_handle_request");			
			if (RC()->hook->has_action("rc_". RC()->request->get_module() . "_" . RC()->request->get_context() . "_" . RC()->request->get_task())) {				
				/* Handle the request */
				RC()->hook->trigger_action("rc_". RC()->request->get_module() . "_" . RC()->request->get_context() . "_" . RC()->request->get_task());					
				/* Respond the request */
				if( RC()->response ) {
					RC()->hook->trigger_action( "rc_before_response" );
					echo json_encode( RC()->response );
				} else {
					RC()->hook->trigger_action( "rc_exception_caught", error_get_last() );
					echo json_encode( new RC_Response( false, "Oops, Internal Error", 0, 0, array() ) );
				}				
			} else {
				/* Unknown context */
				RC()->hook->trigger_action("rc_unknown_context_request");
				echo json_encode(new RC_Response(false, "No handler mapped for this action", 0, 0, array()));
			}			
		} else {
			/* Parsing failed */
			echo json_encode(new RC_Response(false, "Invalid 'rc_param'", 0, 0, array()));
		}		
	} else if (isset($_REQUEST["LOGOUT"])) {
		/* Destroy the session */
		session_destroy();
		echo json_encode(new RC_Response(true, "login", 0, 0, array()));
	} else {
		/* Required parameter missing */
		echo json_encode(new RC_Response(false, "Missing 'rc_param'", 0, 0, array()));
	}	
} else if (isset($_REQUEST["rc_login"])) {
	/* User is trying to Login */
	/* Just trigger the action for authentication action request */
	RC()->hook->trigger_action("rc_auth_account_login");
} else {
	if (isset($_REQUEST["is_attach_req"])) {
		/* This must be the request attachemnt, but session seemed timeout */
		header("Location: /" . dirname(__FILE__));
	} else {
		/* Means either user not logged in or the session has expired 
		 * Since it's an Ajax request you cannot redirect from server */
		echo json_encode(new RC_Response(false, "login", 0, 0, array()));
	}
}
/* end the request - response cycle */
die();

?>