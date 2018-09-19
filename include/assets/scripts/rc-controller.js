/**
 * @module		: Roller Coaster Controller object  
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @url			: http://sarkware.com 
 * @purpose		: Primary controller implementation
 **/

var rcControllerObj = null;

var rcController = function() {
	/* Current context's name, could be 'folder', 'composer', 'contact', 'calender', 'task' or 'meeting' */
	this.context = "folder";
	/**/
	this.contextObj = null;
	/* used to holds next request's data (most likely to be transported to server) */
	this.request = null;
	/* used to holds last operation's response from server */
	this.response = null;
	/* Prevent user from doing anything while any communication between server & client is active. */
	this.ajaxFlaQ = true;
	/* Helper handler which contains comonly used handler used by across the modules */
	this.helper = new rcHelper( this );
	/* Taking responsibility for registring all events across the modules */
	this.events = new rcEvents( this );
	/* Module which manage Mail Folders */
	this.folder = new rcFolder( this );
	/* Module which manage header listing functionality */
	this.lister = new rcLister( this );
	/* Module which manage email composing functionality */
	this.composer = new rcComposer( this );
	/* Module which manage email message rendering ( single mail view ) */
	this.viewer = new rcViewer( this );
	/* Responsible for showing Alert, Confirm and custom model dialogs */
	this.dialog = new rcDialogBox();
	/* User notification module */
	this.notify = new rcNotify();
		
	/* init the rc module */
	this.init = function() {
		this.events.registerShortcut();
		this.events.registerEvents();
		/* Well start from loading the folder */
		this.folder.init();
		/* Set this as default context object */
		this.contextObj = this.folder;
	};
	
	this.switchContext = function( _context ) {
		this.context = _context;
		/* Update the current context object */
		this.contextObj = this[ this.context ];
	};
	
	this.dock = function() {
		var me = this;
		/* Notify user */
		if( ! this.notify.isShown() && this.notify.silent ) {
			if( this.request.request == "GET" ) {
				this.notify.show( "Loading...", "info", true );
			} else {
				this.notify.show( "Processing", "info", true );
			}
		}	
		/* see the ajax handler is free */
		if( !this.ajaxFlaQ ) {	
			if( this.notify.isShown() && this.notify.silent ) {
				if( this.request.request == "GET" ) {
					this.notify.show( "Please wait while Loading...", "info", true );
				} else {
					this.notify.show( "Please wait while Processing", "info", true );
				}
			}
			return;
		}		
		
		$.ajax({  
			type       : "POST",  
			data       : { rc_param : JSON.stringify( this.request ) },  
			dataType   : "json",  
			url        : docker,  
			beforeSend : function() {				
				/* enable the ajax lock - actually it disable the dock */
				me.ajaxFlaQ = false;
				/* disable all action buttons in the top bar */
				$( "#rc-mail-context li a" ).addClass( "disabled" );				
			},
			success    : function( data ) {				
				/* disable the ajax lock */
				me.ajaxFlaQ = true;
				/**/
				me.notify.silent = false;
				me.response = me.prepareResponse( data.status, data.message, data.payload );
				me.handleResponse();
			},
			error      : function( jqXHR, textStatus, errorThrown ) {                    
				/* disable the ajax lock */
				me.ajaxFlaQ = true;
			}  
		});
	};
	
	this.prepareRequest = function( _rtype, _module, _context, _task, _data ) {
		return {
			/* can be POST, PUT, GET or DELETE */
			request : _rtype,
			/* Could be 'email', 'contact', 'calendar', 'task', 'meeting' ... It's actually the name of the module */
			module : _module,
			/* Used to denote sub context, like 'folder', 'mail', 'view' ... */
			context : _context,
			/* can be LIST, COUNT, QUERY */
			task : _task,			
			/* payload data, any additional data that has to be uploaded to server
			 * Which could be context & sub context specific */
			payload : _data
		};
	};
	
	/* dock with server (::--))). single point of function to communicate with server */
	this.prepareResponse = function( _status, _msg, _payload ) {
		return {
			/* status of the operation TRUE=SUCCESS FALSE=ERROR */
			status : _status,
			/* short message from server regarding last operation */
			message : _msg,
			/* actual data received from server (result of last operation) */
			payload : _payload
		};
	};
	
	this.handleResponse = function() {
		this.notify.remove();
		if( ! this.response.status && this.response.message == "login" ) {
			document.location.href = "";
			return;
		}			
		/* Just handed over the response to appropriate module */
		this[this.request.context].handleResponse( this.request, this.response );		
	};
	
}