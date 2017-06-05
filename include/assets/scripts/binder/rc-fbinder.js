/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @purpose		: Folder Binder, responsible for loading folder view
 * 				  Not much just has to initiate 
 * 
 **/
var rcFolderBinder = function( _folder, _fname, _fdname, _icon ) {
	
	/* Folder object's reference */
	this.folder = _folder;
	/* Holds the view object of this folder */
	this.view = null;
	/* Actual folder name */
	this.fname = _fname;
	/* Folder's display name */
	this.fdname = _fdname;	
	/* Holds the total number of messag in this folder */
	this.total_message = -1;	
	/* Holds the counts of un read message */
	this.total_unread = -1;	
	/* Font awesome icon class for  */
	this.icon = _icon;		
	/**/
	this.ready = false;
	/* Whether this folder is Inbox */
	this.isInbox = false;
	/* Whether this folder is Sent */
	this.isSent = false;
	/* Whether this folder is Trash */
	this.isTrash = false;
	/* Whether this folder is Draft */
	this.isDraft = false;
	
	/* Binder's initialization done here */
	this.init = function() {
		if( this.fname.toUpperCase() == "INBOX" ) {
			this.isInbox = true;
		} else if( ( this.fname.toUpperCase().indexOf( "SENT" ) != -1 ) || ( this.fname.toUpperCase().indexOf( "SEND" ) != -1 ) ) {
			this.isSent = true;
		} else if( ( this.fname.toUpperCase().indexOf( "TRASH" ) != -1 ) || ( this.fname.toUpperCase().indexOf( "BIN" ) != -1 ) ) {
			this.isTrash = true;
		} else if( ( this.fname.toUpperCase().indexOf( "DRAFT" ) != -1) || ( this.fname.toUpperCase().indexOf( "SAVE" ) != -1 ) ) {
			this.isDraft = true;
		}
		this.loadView();
	};
	
	/* Responsible for instanciating and loading the view */
	this.loadView = function() {
		this.view = new rcFolderView( this );
		this.view.init();
	};
	
	/* Update the folder meta
	 * Total message count & total unread message count */
	this.updateMeta = function( _req, _res ) {
		if( _res.status ) {
			this.ready = true;
			/* Update the meta properties */
			this.total_message = _res.payload.total_message;
			this.total_unread = _res.payload.total_unread;
			/* Well time to reload the folder view */
			this.view.updateMetaView();
			/* Check if the listing binder for this folder has been instanciated
			 * If is then update the paging properties there too */
			if( typeof this.folder.controller.lister.binders[ this.fname ] != 'undefined' ) {
				this.folder.controller.lister.binders[ this.fname ].updatePagerParams();
				/* Notify the folder mdel that meta sync has finished
				 * This call back is vital for Full reload ( Meta as well as Lister ) of particular folder
				 * Especially when user clicked Reload button */
				this.folder.reloadFolderLister( this.fname );
			}
		} else {
			/* Show the error message to the user */
			this.folder.controller.notify.show( _res.message, "error", false );
		}
	};
	
	/**
	 * 
	 */
	this.syncMeta = function() {
		var payload = {};
		payload[ "type" ] = "folder";
		payload[ "folder" ] = this.fname;
		payload[ "binder" ] = this.fname;
		/* Prepare request object */
		var request = rcControllerObj.prepareRequest( "GET", "email", "folder", "meta", payload );
		rcControllerObj.helper.syncDock( request, "folder", this.name );
	};
	
	this.handleResponse = function( _req, _res ) {		
		if( _req.task == "meta" ) {
			this.updateMeta( _req, _res );
		} else {
			/* Unlikely */
		}
	};
	
};