/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @purpose		: Listing manager, manages all mail header listing related functionalities
 * 				  Instanciating binders, handle header caches and handle all the bulk operations on headers		
 * 				  
 **/
var rcLister = function( _rc ) {
	/* Controller object reference */
	this.controller = _rc;
	/* Listing binder objects */
	this.binders = {};
	/* Container refernce where the headers will be rendered */
	this.headerContainer = $( "#rc-mail-header-container" );
	/**/
	this.searchContext = false;
	
	/**/
	this.init = function() {};
	
	/* Called from 'selectFolder' function of the rcFolder model
	 * Responsible for instantiating binder modules and start the process
	 * to load the header list for the selected folder */
	this.loadLister = function( _folder ) {
		if( typeof this.binders[ _folder ] != 'undefined' ) {
			/* Binder already instanciated */
			this.binders[ _folder ].loadView();
			/* Always try to restore the original icon
			 * sometimes spinner is still on */
			this.controller.folder.binders[ _folder ].view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).removeClass().addClass( "fa fa-" + this.controller.folder.binders[ _folder ].icon );
		} else {
			/* Instanciate the binder object for this folder */
			this.binders[ _folder ] = new rcListingBinder( this, this.controller.folder.binders[ _folder ] );
			/**
			 * Some time the folder might contains zero message, in that case 'init' method returns 'false'
			 * So that we dont have to show spinner
			 */
			this.binders[ _folder ].init();
			/* Show the spinning icon */
			this.controller.folder.binders[ _folder ].view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).removeClass().addClass( "fa fa-gear fa-spin" );
		}
	};
	
	/**/
	this.getHeader = function( _folder, _uid ) {
		var header = null;	
		for( var i = 0; i < this.binders[ _folder ].headers.length; i++ ) {
			if( this.binders[ _folder ].headers[i].uid == _uid ) {
				header = this.binders[ _folder ].headers[i];
				break;
			}
		}
		return JSON.parse( JSON.stringify( header ) );
	};
	
	/* Update flaq property belongs to given binder */
	this.updateFlag = function( _folder, _uid, _flag, _val ) {
		if( typeof this.binders[ _folder ] != 'undefined' ) {
			for( var i = 0; i < this.binders[ _folder ].headers.length; i++ ) {
				if( this.binders[ _folder ].headers[i].uid == _uid ) {
					this.binders[ _folder ].headers[i][ _flag ] = _val;
					this.binders[ _folder ].view.reloadView();
					break;
				}
			}
		}
	};
	
	/* Since delete move operation is common to both
	 * Lister as well as Viewer we have to place the code here only */
	this.moveMessage = function( _folder, _msgno, _uid ) {
		var targetFolder = $( "#rc-move-folder-name" ).val();	
		if( ! targetFolder || targetFolder == "" ) {
			return;
		}
		/* Close the folder select dialog */
		this.controller.dialog.closeDialog();		
		/* Now send the command to server */
		rcControllerObj.request = rcControllerObj.prepareRequest( "DELETE", "email", "lister", "move", { folder: _folder, to: targetFolder, uids: _uid } );
		rcControllerObj.dock();
		rcControllerObj.notify.show( "Moving...", "warning", true );
	};
	
	this.handleMoveMessageResponse = function( _req, _res ) {
		if( _res.status ) {
			var uid = _req.payload.uids;
			var sourceFolder = _req.payload.folder;
			var targetFolder = _req.payload.to;
			var header = this.getHeader( sourceFolder, uid );
			this.controller.notify.show( "Moved Successfully.!", "success", false );			
			/* Remove the header from the source folder */
			this.binders[ sourceFolder ].removeMessage( uid );			
			/* Check whether the target folder is cached
			 * if it is then insert the header into that cache */
			if( this.binders[ targetFolder ].isCacheReady ) {
				this.binders[ targetFolder ].isCacheReady = false;
			}	
			/* Reload source folder meta */
			this.controller.folder.binders[ sourceFolder ].syncMeta();
			/* Reload target folder meta */
			this.controller.folder.binders[ targetFolder ].syncMeta();
			/* Check the viewer and if the message is opened for viewing then
			 * update all the folder reference their */
			if( this.controller.viewer.binders[ uid ] ) {
				/* Update vbinder 'fname' property */
				this.controller.viewer.binders[ uid ].fname = targetFolder;
				/* Update viewer UI */
				this.controller.viewer.binders[ uid ].view.tabHeader.attr( "data-folder", targetFolder );
				this.controller.viewer.binders[ uid ].view.tabContent.find( "div.rc-mail-viewer-attachment-row" ).attr( "data-folder", targetFolder );
				this.controller.viewer.binders[ uid ].view.tabContent.find( "a.rc-mail-viewer-download-all" ).attr( "data-folder", targetFolder );
				this.controller.viewer.binders[ uid ].view.tabContent.find( "a.rc-mail-viewer-action" ).attr( "data-folder", targetFolder );
			}	
		} else {
			/* Notify the user about the failure */
			this.controller.notify.show( "Move operation failed.!", "error", false );
		}
	};
	
	/* Since delete mail operation is common to both
	 * Lister as well as Viewer we have to place the code here only */
	this.deleteMessage = function( _folder, _msgno, _uid ) {
		rcControllerObj.request = rcControllerObj.prepareRequest( "DELETE", "email", "lister", "delete", { folder: _folder, uids: _uid } );
		rcControllerObj.dock();
		rcControllerObj.notify.show( "Deleting...", "warning", true );
	};
	
	this.handleDeleteMessageResponse = function( _req, _res ) {
		if( _res.status ) {
			var uid = _req.payload.uids;
			var folder = _req.payload.folder;
			this.controller.notify.show( "Deleted Successfully.!", "success", false );
			/* Now remove the uid from the corresponding folder cache
			 * Also remove it from the viewer ( If it is loaded for reading ) */
			this.binders[ folder ].removeMessage( uid );
			if( this.controller.viewer.binders[ uid ] ) {
				/* This means the message that deleted was loaded for reading
				 * Remove the view */
				this.controller.viewer.closeMail( uid );
			}		
			/* Sanity check for Checked Headers
			 * If the deleted message was in checked state before being deleted
			 * We need to remove that uid from 'checkedHeaders' property as well */
			for(var i = 0; i < this.binders[ folder ].checkedHeaders.length; i++  ) {
				if( this.binders[ folder ].checkedHeaders[i] == uid ) {
					this.binders[ folder ].checkedHeaders.splice( i, 1 );
					break;
				}
			}
			/* Reload source folder meta */
			this.controller.folder.binders[ folder ].syncMeta();
			/* Reload target folder meta, in this case its 'trash' */
			for( var i = 0; i < this.controller.folder.folders.length; i++ ) {
				if( this.controller.folder.binders[ this.controller.folder.folders[i].name ].isTrash ) {
					this.controller.folder.binders[ this.controller.folder.folders[i].name ].syncMeta();
				}
			}
			
		} else {
			/* Notify the user about the failure */
			this.controller.notify.show( "Delete operation failed.!", "error", false );
		}
	};
	
	/* Dispatcher for bulk action */
	this.handleBulkAction = function( _field ) {
		if( this.binders[ this.controller.folder.current ].checkedHeaders.length == 0 ) {
			this.controller.notify.show( "Please mark one or more messages to continue.!" );
			return;
		}		
		/* Make sure the current context is lister */
		this.controller.switchContext( "lister" );
		
		if( _field.attr( "data-action" ) == "move" ) {
			this.prepareBulkMove();
		} else if( _field.attr( "data-action" ) == "delete" ) {
			this.prepareBulkDelete();
		} else if( _field.attr( "data-action" ) == "mark-read" ) {
			this.prepareBulkMarkAsRead();
		} else {
			// of course this must be 'mark-unread'
			this.prepareBulkMarkAsUnRead();
		}
	};
	
	/* Shows the folder selection dialog
	 * where user can select to which folder the selected message has to be moved */
	this.prepareBulkMove = function() {
		var _options = [];
		for( var i = 0; i < this.controller.folder.folders.length; i++ ) {
			if( this.controller.folder.current != this.controller.folder.folders[i].name ) {
				_options.push({
					"key": this.controller.folder.folders[i].name,
					"value": this.controller.folder.folders[i].display_name
				});
			}			
		}
		if( _options.length > 0 ) {
			var fields = [
				{
					type: "select",
					label: "Move to",
					name: "rc-move-folder-name",
					tabindex: 1,
					classes: "",
					align: "",
					placeholder: "",
					attributes: [],
					char_length: "",
					readonly: false,
					value: "",
					options: _options
				}
			];
			var buttons = [
				{ action: "yes", title: "Move" },
				{ action: "cancel", title: "Cancel" }
			];
			this.controller.dialog.store = this.controller.folder.current;
			/* Show the dialog box */
			this.controller.dialog.popup( "Where.?", "", "move_messages", fields, buttons, "yes", "medium" );
		} else {
			this.controller.notify.show( "It seems you have only one mail folder, you can create a new folder than try again.!" );
			return;
		}		
	};
	
	/* By this time we have necesary details to initiate the ajax request
	 * for moving message */
	this.moveMessages = function( _folder ) {
		var targetFolder = $( "#rc-move-folder-name" ).val();		
		if( ! targetFolder || targetFolder == "" ) {
			return;
		}
		/* Close the folder select dialog */
		this.controller.dialog.closeDialog();		
		/* Now send the command to server */
		this.controller.request = this.controller.prepareRequest( "PUT", "email", "lister", "bulk_move", { folder: _folder, to: targetFolder, uids: this.binders[ _folder ].checkedHeaders.join(",") } );
		this.controller.dock( this );
		rcControllerObj.notify.show( "Moving...", "info", true );
		return true;
	};
	
	this.handleBulkMoveResponse = function( _req, _res ) {
		if( _res.status ) {		
			var targetFolder = _req.payload.to;
			var sourceFolder = _req.payload.folder;
			this.controller.notify.show( "Moved Successfully.!", "success", false );
			/* Before updating folders meta and local cache
			 * Remove the checked records from view immediately */
			var temp = [];
			var flag = true;
			for( var i = 0; i < this.binders[ sourceFolder ].headers.length; i++ ) {
				flag = true;
				for( var j = 0; j < this.binders[ sourceFolder ].checkedHeaders.length; j++ ) {
					if( this.binders[ sourceFolder ].checkedHeaders[j] == this.binders[ sourceFolder ].headers[i].uid ) {
						flag = false;
						break;
					}
				}
				if( flag ) {
					temp.push( this.binders[ sourceFolder ].headers[i] );
				}
			}
			/* Update the header cache of the source folder */
			this.binders[ sourceFolder ].headers = temp;
			/* Check whether the target folder is cached
			 * if it is then insert the header into that cache */
			if( this.binders[ targetFolder ].isCacheReady ) {
				this.binders[ targetFolder ].isCacheReady = false;
			}
			/* Reload source folder meta */
			this.controller.folder.binders[ sourceFolder ].syncMeta();
			/* Reload target folder meta */
			this.controller.folder.binders[ targetFolder ].syncMeta();
			/* Check the viewer and if the message is opened for viewing then
			 * update all the folder reference their */
			var cHeaders = this.binders[ sourceFolder ].checkedHeaders;
			for( var i = 0; i < cHeaders.length; i++ ) {
				if( this.controller.viewer.binders[ cHeaders[i] ] ) {
					/* Update vbinder 'fname' property */
					this.controller.viewer.binders[ cHeaders[i] ].fname = targetFolder;
					/* Update viewer UI */
					this.controller.viewer.binders[ cHeaders[i] ].view.tabHeader.attr( "data-folder", targetFolder );
					this.controller.viewer.binders[ cHeaders[i] ].view.tabContent.find( "div.rc-mail-viewer-attachment-row" ).attr( "data-folder", targetFolder );
					this.controller.viewer.binders[ cHeaders[i] ].view.tabContent.find( "a.rc-mail-viewer-download-all" ).attr( "data-folder", targetFolder );
					this.controller.viewer.binders[ cHeaders[i] ].view.tabContent.find( "a.rc-mail-viewer-action" ).attr( "data-folder", targetFolder );
				}
			}			
			/* Clear 'checkedHeaders' property of the source folder */
			this.binders[ sourceFolder ].checkedHeaders = [];
			/* This will clear the current view and recreate it */
			if( this.controller.folder.current == sourceFolder ) {
				this.binders[ sourceFolder ].loadView();
			}			
		} else {
			this.controller.notify.show( "Move operation failed", "error", false );
		}
	};
	
	/* Show the confirm box, before doing the bulk remove */
	this.prepareBulkDelete = function() {
		var msg = "";
		if( this.binders[ this.controller.folder.current ].checkedHeaders.length > 1 ) {
			msg = "Do you want to Delete these "+ this.binders[ this.controller.folder.current ].checkedHeaders.length +" messages.?";
		} else {
			msg = "Do you want to Delete this message.?";
		}
		this.controller.dialog.store = this.controller.folder.current;
		rcControllerObj.dialog.confirm( "Confirm", msg, "delete_messages",[ { action: "yes", title: "Yes" },{ action: "no", title: "No" } ] );
	};
	
	this.deleteMessages = function( _folder ) {
		/* Now send the command to server */
		rcControllerObj.request = rcControllerObj.prepareRequest( "DELETE", "email", "lister", "bulk_delete", { folder: _folder, uids: this.binders[ _folder ].checkedHeaders.join(",") } );
		rcControllerObj.dock( this );
		return true;
	};
	
	/* Handles response for both
	 * bulk move as well as bulk delete */
	this.handleBulkDeleteResponse = function( _req, _res ) {
		if( _res.status ) {			
			/* Before updating folders meta and local cache
			 * Remove the checked records from view immediately */
			var temp = [];
			var flag = true;
			for( var i = 0; i < this.binders[ _req.payload.folder ].headers.length; i++ ) {
				flag = true;
				for( var j = 0; j < this.binders[ _req.payload.folder ].checkedHeaders.length; j++ ) {
					if( this.binders[ _req.payload.folder ].checkedHeaders[j] == this.binders[ _req.payload.folder ].headers[i].uid ) {
						flag = false;
						break;
					}
				}
				if( flag ) {
					temp.push( this.binders[ _req.payload.folder ].headers[i] );
				}
			}
			this.binders[ _req.payload.folder ].headers = temp;
			this.binders[ _req.payload.folder ].checkedHeaders = [];
			/* This will clear the current view and recreate it */
			this.binders[ _req.payload.folder ].loadView();
			if( this.controller.folder.current == _req.payload.folder ) {
				this.binders[ _req.payload.folder ].loadView();
			}	
			/* Now update the meta and localcache for all the folders at one go */
			this.controller.folder.fetchFoldersMeta();	
			
		} else {
			this.controller.notify.show( _res.message, "error" );
		}
	};
	
	/* Called from confirm box */
	this.onUserConfirmed = function( _task, _action ) {
		if( _task == "delete" && _action == "yes" ) {
			this.deleteMessage( this.controller.dialog.store.folder, this.controller.dialog.store.msgno, this.controller.dialog.store.uid );
		} else if( _task == "move" && _action == "yes" ) {
			this.moveMessage( this.controller.dialog.store.folder, this.controller.dialog.store.msgno, this.controller.dialog.store.uid );
		} else if( _task == "move_messages" && _action == "yes" ) {
			this.moveMessages( this.controller.dialog.store );
		} else if( _task == "delete_messages" && _action == "yes" ) {
			this.deleteMessages( this.controller.dialog.store );
		} else {
			/* Unlikely - but some time does */
			if( rcControllerObj.dialog.isOn() ) {
				rcControllerObj.dialog.closeDialog();
			}
		}	
	};
	
	/* Response dispatcher for lister model */
	this.handleResponse = function( _req, _res ) {		
		if( typeof _req.payload.binder == 'undefined' ) {
			if( _req.task == "delete" ) {
				this.handleDeleteMessageResponse( _req, _res );
			} else if( _req.task == "bulk_move" || _req.task == "bulk_delete" ) {
				this.handleBulkMoveDeleteResponse( _req, _res );
			}
		} else {
			/* The request has been raised from one of the binder object
			 * So forward it to them */
			if( this.binders[ _req.payload.binder ] ) {
				this.binders[ _req.payload.binder ].handleResponse( _req, _res );
			} else {
				// Unlikely
			}		
		}
	};
	
};