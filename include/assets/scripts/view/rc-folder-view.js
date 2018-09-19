var rcFolderView = function( _binder ) {
	
	/* Folder binder object which own this view object */
	this.binder = _binder;
	/* jQuery object of the menu item which represents this folder */
	this.mItem = null;
	
	/*  */
	this.init = function() {
		this.renderView();
	};

	/* Render the folder menu item */
	this.renderView = function() {	
		this.mItem = '<li>';
		this.mItem += '<a href="#" class="rc-mail-folder-view-menu" data-folder="'+ this.binder.fname +'"><i class="fa fa-'+ this.binder.icon +'"></i> <span class="rc-folder-name">'+ this.binder.fdname +'</span> <span style="display: none;" class="rc-message-count-bubble">0</span></a>';
		if( ! this.binder.isInbox ) {
			this.mItem += '<a href="#" class="rc-mail-folder-refresh-menu" data-folder="'+ this.binder.fname +'" data-action="reload" title="Reload Mail List"><i class="fa fa-refresh"></i></a>';
			this.mItem += '<a href="#" class="rc-mail-folder-edit-menu" data-folder="'+ this.binder.fname +'" data-action="rename" title="Rename this folder"><i class="fa fa-pencil"></i></a>';
			this.mItem += '<a href="#" class="rc-mail-folder-delete-menu" data-folder="'+ this.binder.fname +'" data-action="delete" title="Remove this folder"><i class="fa fa-times"></i></a>';
		} else {
			/* Well adjust the 'right' style property of the refresh button
			 * Since that is the only button there for Inbox menu */
			this.mItem += '<a href="#" class="rc-mail-folder-refresh-menu" style="right: 15px;" data-folder="'+ this.binder.fname +'" data-action="reload" title="Reload Mail List"><i class="fa fa-refresh"></i></a>';
		}		
		this.mItem += '</li>';
		this.mItem = $( this.mItem );
		
		var createMenu = this.binder.folder.folders_container.find( "a.rc-mail-folder-add-menu" );
		var isAppend = ( createMenu.length > 0 );
		
		if( ! isAppend ) {
			this.binder.folder.folders_container.append( this.mItem );
		} else {
			this.binder.folder.folders_container.find( "li:last-child" ).before( this.mItem );
		}		
	};
	
	/* Used to update the folder's name */
	this.updateView = function() {
		this.mItem.find( "span.rc-folder-name" ).html( this.binder.fdname );
	};
	
	/* Used to update the meta part - count bubble */
	this.updateMetaView = function( _count ) {
		if( this.binder.total_unread > 0 ) {
			this.mItem.find( "span.rc-message-count-bubble" ).html( this.binder.total_unread ).show();
		} else {
			/* Hide the bubble */
			this.mItem.find( "span.rc-message-count-bubble" ).html( "" ).hide();
		}	
	};
	
	/* Remove the menu item from the view */
	this.remove = function() {
		this.mitem.remove();
	};
	
};