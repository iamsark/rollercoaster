<?php 

/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd - https://sarkware.com
 * @category	: Core
 * @desc		: This is the core module for pluggable functionality ( To run the arbitary codes at certain points )
 * 				  Adopted from Wordpress Framework ( Stripped down from 'wp-includes/plugin.php' )
 */

if (!defined('RC_INIT')) {exit;}

if (!class_exists("RC_Hook")) {

	class RC_Hook {
	
		/* Stores all of the filters */
		private $rc_filter;
		/* Increments the amount of times action was triggered */
		private $rc_actions;
	
		public function __construct() {
			$this->rc_filter = array();
			$this->rc_actions = array();
			/* Inject this module to global RC */
			RC()->inject("hook", $this);
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @param 		callable $_handler
		 * @param 		number $_priority
		 * @param 		number $_args
		 * @return 		boolean
		 * @desc		Attach the callable with a filter hook ( hook that specified by the $_tag )
		 * 				This is equalant to Wordpress's 'add_filter'
		 */
		public function listen_filter( $_tag, $_handler, $_priority = 10, $_args = 1 ) {
			$idx = $this->get_unique_handler_id( $_tag, $_handler, $_priority );
			$this->rc_filter[ $_tag ][ $_priority ][ $idx ] = array( 'function' => $_handler, 'args' => $_args );
			return true;
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @param 		callable $_handler
		 * @param 		number $_priority
		 * @param 		number $_args
		 * @desc		Attach the callable with aa action hook ( hook that specified by the $_tag )
		 * 				This is equalant to Wordpress's 'add_action'
		 * 
		 */
		public function listen_action( $_tag, $_handler, $_priority = 10, $_args = 1 ) {
			$this->listen_filter( $_tag, $_handler, $_priority, $_args );
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @param 		string $_value
		 * @return 		mixed
		 * @desc		This will invoke all the function one by one ( sorted by priority vallue give by user )
		 * 				along with the parameters passed by the callee
		 * 				This will return the parameter back after all the functions are applied to it
		 * 				This is equalant to Wordpress 'apply_filters'
		 * 
		 */
		function trigger_filter( $_tag, $_value ) {
	
			if (isset( $this->rc_filter[ $_tag ] ) ) {
					
				$args = array();
				ksort( $this->rc_filter[ $_tag ] );
				reset( $this->rc_filter[ $_tag ] );
					
				if ( empty($args) ) {
					$args = func_get_args();
				}
	
				do {
					foreach( (array) current( $this->rc_filter[ $_tag ] ) as $handler ) {
						if ( !is_null( $handler[ 'function' ] ) ) {
							$args[1] = $_value;
							$_value = call_user_func_array( $handler[ 'function' ], array_slice( $args, 1, (int) $handler[ 'args' ] ) );
						}
					}
	
				} while ( next( $this->rc_filter[ $_tag ] ) !== false );
	
			}
	
			return $_value;
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @param 		mixed $_arg
		 * @desc		This will invokes all the function attached to this hook ( Given by the $_tag param )
		 * 				You can also pass additional parameters with this hook using $_arg param
		 * 				This returns nothing
		 * 				This is equalant to Wordpress's 'do_action' 
		 * 
		 */
		function trigger_action( $_tag, $_arg = '' ) {
	
			if ( ! isset( $this->rc_actions[ $_tag ] ) ) {
				$this->rc_actions[ $_tag ] = 1;
			} else {
				++$this->rc_actions[ $_tag ];
			}
	
			$args = array();
	
			if ( is_array( $_arg ) && 1 == count( $_arg ) && isset( $_arg[0] ) && is_object( $_arg[0] ) ) {
				$args[] =& $_arg[0];
			} else {
				$args[] = $_arg;
			}
	
			for ( $a = 2; $a < func_num_args(); $a++ ) {
				$args[] = func_get_arg( $a );
			}
	
			if( isset( $this->rc_filter[ $_tag ] ) ) {
					
				ksort( $this->rc_filter[ $_tag ] );
				reset( $this->rc_filter[ $_tag ] );
					
				do {
					foreach ( (array) current( $this->rc_filter[ $_tag ] ) as $handler ) {
						if ( !is_null( $handler['function'] ) ) {
							call_user_func_array( $handler['function'], array_slice( $args, 0, (int) $handler['args'] ) );
						}
					}
				} while ( next( $this->rc_filter[ $_tag ] ) !== false );
					
			}
	
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @return 		boolean
		 * @desc		Check if any filter has been registered for a given hook.
		 * 
		 */
		function has_filter( $_tag ) {
			return isset( $this->rc_filter[ $_tag ] );
		}
	
		/**
		 * 
		 * @param 		string $_tag
		 * @return 		boolean
		 * @desc		Check if any action has been registered for a given hook.
		 * 
		 */
		function has_action( $_tag ) {
			return $this->has_filter( $_tag );
		}
	
		
		/**
		 * 
		 * @param 		string $_tag
		 * @param 		callable $_function
		 * @param 		number $_priority
		 * @return 		unknown|string|boolean
		 * @desc		Generate Unique ID for storage and retrieval.
		 * 
		 */
		private function get_unique_handler_id( $_tag, $_function, $_priority ) {
			static $filter_id_count = 0;
	
			if ( is_string( $_function ) ) {
				return $_function;
			}
	
			if ( is_object( $_function ) ) {
				$_function = array( $_function, '' );
			} else {
				$_function = (array) $_function;
			}
	
			if ( is_object( $_function[0] ) ) {
				if ( function_exists( 'spl_object_hash' ) ) {
					return spl_object_hash( $_function[0] ) . $_function[1];
				} else {
					$obj_idx = get_class( $_function[0] ).$_function[1];
					if ( !isset( $_function[0]->rc_filter_id ) ) {
						if ( false === $_priority )
							return false;
							$obj_idx .= isset( $this->rc_filter[ $_tag ][ $_priority ] ) ? count( (array) $this->rc_filter[ $_tag ][ $_priority ] ) : $filter_id_count;
							$_function[0]->rc_filter_id = $filter_id_count;
							++$filter_id_count;
					} else {
						$obj_idx .= $_function[0]->rc_filter_id;
					}
	
					return $obj_idx;
				}
			} else if ( is_string( $_function[0] ) ) {
				return $_function[0] . '::' . $_function[1];
			}
		}
	
	}
	
	new RC_Hook();

}

?>