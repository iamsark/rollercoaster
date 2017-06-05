<?php 
/**
 * @author		: Saravana Kumar K
 * @copyright	: sarkware.com
 * @todo		: Roller Coaster core backup Ajax handler. this handler will active while RollerCoaster installation is in updates.
 *
 *
 */

/* Since Docker calls are made via Ajax we cannot do redirect to 'Maintenance' page directly
 * So just return a proper response with 'login' as message which will be redirected to login page */
echo json_encode( array(
	"status" => false,
	"message" => "login",
	"page" => 0,
	"count" => 0,
	"data" => array() 
));

?>