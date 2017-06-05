/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @purpose		: Folder manager, although it has been named folder but it also manage 'presets' and 'labels' as well
 * 				: This module is the starting point for the email functionality
 * 
 **/
var rcFolder = function( _rc ) {
	
	/* Controller object's reference */
	this.controller = _rc;	
	/* Name of the current folder that is being viewed ( or listed ) */
	this.current = "";
	/* Cached the list of mail folders - fetched from server */
	this.folders = [];
	/* Holds the binder object ( each folder, preset and label items will have  ) */
	this.binders = {};	
	/* Holds the container reference where the folder list will be rendered */
	this.folders_container = $( "#rc-folders-list" );
	/* Holds the reference of Folder's section collapsible button */
	this.folder_group_title = $( "#rc-left-nav-acc-folders-btn" );	
	/**/
	this.sort = this.controller.helper.getSortOption();
	/* Holds the name of the folder that has to be reloaded */
	this.reloadWaiting = "";
	
	
	/* Well lets start the show */
	this.init = function() {
		/* Start this module by fetching the folder's list */
		this.fetchFolders();
	};
	
	this.fetchFolders = function() {
		/* Put spinner on folder title */
		this.folder_group_title.find( "i" ).removeClass().addClass( "fa fa-gear fa-spin" );
		/* Prepare request object */
		this.controller.request = this.controller.prepareRequest( "GET", "email", "folder", "list", {} );
		/* Contact the server */
		this.controller.dock( this );
	};
	
	/* Initialize Binder for each folders */
	this.initFolderBinders = function( _request, _response ) {		
		var icon = "";
		/* Cache the folder list */
		this.folders = _response.payload;
		/* Reset the spinner icon */
		this.folder_group_title.find( "i" ).attr( "class", "" );
		this.folder_group_title.find( "i" ).addClass( "fa fa-caret-up" );
		/* Make sure you have the valid response */
		if( _response.status ) {
			for ( var i = 0; i < this.folders.length; i++ ) {
				/* Get font awesome icon class name */
				icon = this.getFolderIcon( "folder", this.folders[i].display_name );
				/* Instanciate the binder object for this folder */
				this.binders[ this.folders[i].name ] = new rcFolderBinder( this, this.folders[i].name, this.folders[i].display_name, icon );
				/* Kick start it - where it will start to load the view and try to fetch the meta for this folder */
				this.binders[ this.folders[i].name ].init();
				/* Now update the meta data for folders ( total msg count & unread msg count ) */
				this.binders[ this.folders[i].name ].syncMeta();
			}
			/* Well add the folder create menu */
			this.folders_container.append( $( '<li><a href="#" class="rc-mail-folder-add-menu" data-context="folder" data-action="new" title="Click this to create a new mail folder"><i class="fa fa-plus-circle"></i> New Folder</a></li>' ) );
			
		} else {
			/* Display user regarding this error */
			this.controller.notify.show( "Internal error, not able to list the mail folders, Please try to reload the page.!", "error", true );
			return false;
		}
	};
	
	/** 
	 * Called whenever user select folder from folder list ( Left Menu )
	 * It switchs the current folder and initiate fetching header operation for that folder */
	this.selectFolder = function( _folder ) {
		var cfName = this.current;
		/* Update the current folder name */
		this.current = _folder;
		/* Clear the search text field */
		$( "#rc-mail-search-text" ).val( "" );
		/* Clear the select all check box */
		$( "#rc-mail-select-all-check" ).prop( "checked", false );
		
		if( cfName == "" || cfName != _folder ) {
			/* This means either this is very first time one of the folder menu has been clicked
			 * or a different folder has been clicked */
			this.controller.lister.loadLister( this.current );
		}		
	};
	
	/* Called whenever user click on 'New Folder' menu item
	 * This is where we show the dialog box to get the Folder name */
	this.prepareToCreateFolder = function() {
		var fields = [
			{
				type: "text",
				label: "Folder Name",
				name: "rc-new-folder-title",
				classes: "",
				align: "",
				placeholder: "",
				attributes: [],
				char_length: "",
				readonly: false,
				value: "",
				focus: true
			}
		];
		var buttons = [
			{ action: "yes", title: "Add" },
			{ action: "cancel", title: "Cancel" }
		];
		this.controller.dialog.popup( "Add a New Folder", "", "create", fields, buttons, "yes", "medium" );
	};
	
	/* Prepare to folder creation operation */
	this.createFolder = function() {
		var fname = $( "#rc-new-folder-title" ).val();
		if( fname == "" ) {
			this.controller.notify.show( "Please enter a name for the mail folder.", "warning", false );
			return false;
		}
		if( fname.indexOf( "/" ) != -1 || fname.indexOf( "\\" ) != -1 || fname.indexOf( "&" ) != -1 || fname.indexOf( "<" ) != -1 || fname.indexOf( ">" ) != -1 ) {
			this.controller.notify.show( "Folder name should not contains the following special characters '\ / & < >'.", "warning", false );
			return false;
		}
		this.controller.request = this.controller.prepareRequest( "POST", "email", "folder", "create", { name: fname } );
		this.controller.dock( this );
		return true;
	};
	
	/* Called upon the response of folder create request from the host */
	this.handleFolderCreationResponse = function( _req, _res ) {
		/* Close the folder create Popup */
		this.controller.dialog.closeDialog();		
		if( _res.status ) {
			var fmeta = {
				name: "INBOX." + _req.payload.name,
				display_name: _req.payload.name,				
				total_message: 0,
				total_unread: 0
			};
			/* Update folder list array */
			this.folders.push( fmeta );
			/* Determine the icon for this new folder */
			var icon = this.getFolderIcon( "folder", fmeta.display_name );
			/* Well initialize the binder */
			this.binders[ fmeta.name ] = new rcFolderBinder( this, fmeta.name, fmeta.display_name, icon );
			/* Load the view of the folder that just been created */
			this.binders[ fmeta.name ].init();
			/* Now update the meta data for folders ( total msg count & unread msg count ) */
			this.binders[ fmeta.name ].syncMeta();
			/* Notify the user */
			this.controller.notify.show( "Folder '"+ _req.payload.name + "' created successfully" );
		} else {
			this.controller.notify.show( _res.message, "error" );			
		}
	};
	
	/* Called whenever user tries to reload the folder ( as well as the message list ) */
	this.prepareToReloadFolder = function( _elem ) {
		/* Start with folder meta update */
		this.reloadWaiting = _elem.attr( "data-folder" );
		if( this.binders[ this.reloadWaiting ].ready ) {
			this.binders[ this.reloadWaiting ].syncMeta();
		} else {
			this.controller.notify.show( "Oh oh, not now.!", "info" );
		}		
	};
	
	this.reloadFolderLister = function( _folder ) {
		if( this.reloadWaiting == _folder ) {
			/* Reset the reload waiting folder property */
			this.reloadWaiting = "";
			/**/
			if( ! this.controller.lister.binders[ _folder ].syncInProgress ) {
				this.controller.lister.binders[ _folder ].fetchHeaders( true );
			} else {
				this.controller.notify.show( "Seems already one sync is in progress", "info" );
			}			
		}
	};
	
	/* Called whenever user tries to delete a folder
	 * This is where we display the confirm box before process delete request */
	this.prepareToDeleteFolder = function( _field ) {
		this.controller.dialog.store = _field.attr( "data-folder" );
		this.controller.dialog.confirm( "Confirm", "Do you want to Delete this Folder.?", "delete",[ { action: "yes", title: "Yes" },{ action: "no", title: "No" } ] );
	};
	
	/* Well this means user has confirmed for folder deletion
	 * Initiate the request */
	this.deleteFolder = function() {
		this.controller.request = this.controller.prepareRequest( "DELETE", "email", "folder", "delete", { name: this.controller.dialog.store } );
		this.controller.dock( this );
	};
	
	/* Called upon the response of folder delete request */
	this.handleFolderDeleteResponse = function( _req, _res ) {
		if( _res.status ) {
			/* Check viewer if there any mails is being opened from this folder
			 * If it is then close them too */
			var vkeys = Object.keys( this.controller.viewer.binders );
			for( var i = 0; i < vkeys.length; i++ ) {
				if( this.controller.viewer.binders[ vkeys[i] ].fname == _req.payload.name ) {
					this.controller.viewer.binders[ vkeys[i] ].closeMail();
					delete this.controller.viewer.binders[ vkeys[i] ];
				}
			}			
			/* Remove the folder from local folder cache */
			var fIndex = -1;
			for( var i = 0; i < this.folders.length; i++ ) {
				if( this.folders[i].name == _req.payload.name ) {
					fIndex = i;
					break;
				}
			}			
			if( fIndex >= 0 ) {
				this.folders.splice( fIndex, 1 );
				this.binders[ _req.payload.name ].view.mItem.remove();
				/* Remove Folder Binder */
				delete this.binders[ _req.payload.name ];
				/* Delete the Lister Binder object too */
				if( typeof this.controller.lister.binders[ _req.payload.name ] != 'undefined' ) {
					delete this.controller.lister.binders[ _req.payload.name ];
				}
				/* Finaly Check whether this folder is currently being viewed 
				 * If it is than clear the header list view and notify the user to select any folder */
				if( this.current == _req.payload.name ) {
					$( "#rc-mail-header-container" ).html( '<table class="rc-empty-folder-info"><tr><td><h3><i class="fa fa-info-circle"></i> Please select any folder.!</h3></td></tr></table>' );
				}
			}
			/* Notify user */
			this.controller.notify.show( "Folder '" + _req.payload.name + "' deleted successfully" );
		} else {
			this.controller.notify.show( _res.message, "error", false );		
		}
	};
	
	/* Called whenever user tries to rename the folder
	 * This is where we will show rename dialog box */
	this.prepareToRenameFolder = function( _field ) {		
		var cfname = _field.attr( "data-folder" );
		var fields = [
			{
				type: "text",
				label: "Folder Name",
				name: "rc-new-folder-title",
				tabindex: 1,
				classes: "",
				align: "",
				placeholder: "",
				attributes: [],
				char_length: "",
				readonly: false,
				value: this.binders[ cfname ].fdname
			}
		];
		var buttons = [
			{ action: "yes", title: "Update" },
			{ action: "cancel", title: "Cancel" }
		];
		this.controller.dialog.store = cfname;
		this.controller.dialog.popup( "Rename Folder", "", "rename", fields, buttons, "update", "medium" );
	};
	
	/* User has has confirmed with new name to be updated
	 * So initiate the request */
	this.renameFolder = function() {
		var fname = $( "#rc-new-folder-title" ).val();
		if( fname == "" ) {
			this.controller.notify.show( "Please enter a valid name for the mail folder.", "warning", false );
			return false;
		}
		if( fname.indexOf( "/" ) != -1 || fname.indexOf( "\\" ) != -1 || fname.indexOf( "&" ) != -1 || fname.indexOf( "<" ) != -1 || fname.indexOf( ">" ) != -1 ) {
			this.controller.notify.show( "Folder name should not contains the following special characters '\ / & < >'.", "warning", false );
			return false;
		}
		this.controller.request = this.controller.prepareRequest( "PUT", "email", "folder", "rename", { old_name: this.controller.dialog.store, new_name: fname } );
		this.controller.dock( this );
		return true;
	};
	
	/* Called upon the response of folder rename request */
	this.handleFolderRenameResponse = function( _req, _res ) {
		/* Close the folder create Popup */
		this.controller.dialog.closeDialog();	
		if( _res.status ) {
			/* Update the view */
			this.binders[ _req.payload.old_name ].view.mItem.find( "a.rc-mail-folder-view-menu" ).attr( "data-folder", _req.payload.new_name );
			this.binders[ _req.payload.old_name ].view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "span.rc-folder-name" ).html( _req.payload.new_name );
			/* Update folder name properties on Binder & Lister */
			this.binders[ _req.payload.old_name ].fdname = _req.payload.new_name;
			this.binders[ _req.payload.old_name ].fname = "INBOX." . _req.payload.new_name;
			
			/* Update the folder name on Listing Binder */
			if( typeof this.controller.lister.binders[ _req.payload.old_name ] != 'undefined' ) {
				this.controller.lister.binders[ _req.payload.old_name ].fname = "INBOX." . _req.payload.new_name;
			}			
			
			/* Update binder key reference */
			this.binders[ "INBOX." . _req.payload.new_name ] = this.binders[ _req.payload.old_name ];
			/* Now remove the old copy */
			delete this.binders[ _req.payload.old_name ];
			/* Update folder list array */
			for( var  i = 0; i < this.folders.length; i++ ) {
				if( this.folders[i].name == _req.payload.old_name ) {
					this.folders[i].name = "INBOX." . _req.payload.new_name;
					this.folders[i].display_name = _req.payload.new_name;
				}
			}
			/* Check viewer if there any mail is being opened from this folder
			 * If it is then update their folder name property */
			var vkeys = Object.keys( this.controller.viewer.binders );
			for( var i = 0; i < vkeys.length; i++ ) {
				if( this.controller.viewer.binders[ vkeys[i] ].fname == _req.payload.old_name ) {
					this.controller.viewer.binders[ vkeys[i] ].fname = "INBOX." . _req.payload.new_name;
				}
			}
		} else {
			this.controller.notify.show( _res.message, "error", false );		
		}
	};
	
	/**
	 * Fetch all folders meta but each with seperate call
	 * Mostly used by the Ticker Implementation
	 */
	this.reloadFoldersMeta = function() {
		for ( var i = 0; i < this.folders.length; i++ ) {
			this.binders[ this.folders[i].name ].syncMeta();
		}
	};
	
	/**
	 * Fetch all folders meta at one go
	 * Mostly used when viewing, deleting or bulk editing emails
	 */
	this.fetchFoldersMeta = function() {
		this.controller.request = this.controller.prepareRequest( "GET", "email", "folder", "meta_list", this.folders );
		this.controller.dock( this );
	};
	
	/**
	 * Update all folders meta view at one go
	 */
	this.updateFoldersMetaView = function( _reg, _res ) {
		if( _res.status ) {
			var fkeys = Object.keys( _res.payload );
			for( var i = 0; i < fkeys.length; i++ ) {
				/* Update the message count meta of binder */
				this.binders[ fkeys[i] ].total_message = _res.payload[ fkeys[i] ].total_message;
				this.binders[ fkeys[i] ].total_unread = _res.payload[ fkeys[i] ].total_unread;
				/* Update the lister param */
				if( typeof this.controller.lister.binders[ fkeys[i] ] != 'undefined' ) {
					this.controller.lister.binders[ fkeys[i] ].updatePagerParams();
				}
				/* Update the folder view */
				if( _res.payload[ fkeys[i] ].total_unread ) {
					this.binders[ fkeys[i] ].view.mItem.find( "span.rc-message-count-bubble" ).html( _res.payload[ fkeys[i] ].total_unread ).show();
				} else {
					this.binders[ fkeys[i] ].view.mItem.find( "span.rc-message-count-bubble" ).html( "0" ).hide();
				}
			}
		}
	};
	
	/*
	 * Examine the folder name and try to 
	 * determine the correspoding fontawesome icon class
	 * if nothing matchs it returns 'folder' as default value 
	 */
	this.getFolderIcon = function( _type, _name ) {
		if( _type == "folder" ) {
			var icon = "";
			if( _name.toLowerCase().indexOf( "inbox" ) != -1 ) {
				icon = "inbox";
			} else if( _name.toLowerCase().indexOf( "archive" ) != -1 ) {
				icon = "archive";
			} else if( _name.toLowerCase().indexOf( "draft" ) != -1 ) {
				icon = "edit";
			} else if( _name.toLowerCase().indexOf( "sent" ) != -1 ) {
				icon = "send";
			} else if( _name.toLowerCase().indexOf( "trash" ) != -1 || _name.toLowerCase().indexOf( "delete" ) != -1 ) {
				icon = "trash";
			} else if( _name.toLowerCase().indexOf( "spam" ) != -1 || _name.toLowerCase().indexOf( "junk" ) != -1 ) {
				icon = "envelope-o fa-ban";
			} else {
				icon = "folder";
			}
			return icon;
		} else if( _type == "preset" ) {
			return "filter";
		} else if( _type == "label" ) {
			return "tag";
		} else {
			/* Unlikely */
			return "";
		}
	};
	
	
	/**
	 * Called from confirm box
	 */
	this.onUserConfirmed = function( _task, _option ) {  
		if( this.controller.dialog.task == "create" && _option == "yes" ) {
			this.createFolder();			
		} else if( this.controller.dialog.task == "delete" && _option == "yes" ) {
			this.deleteFolder();			
		} else if( _task == "rename" && _option == "yes" ) {
			this.renameFolder();
		} else {
			/* Unlikely  */
			if( this.controller.dialog.isOn() ) {
				this.controller.dialog.closeDialog();
			}
		}		
	};
	
	/**
	 * End point where all the ajax response ( related to this module )
	 * will be handled
	 */
	this.handleResponse = function( _req, _res ) {
		if( typeof _req.payload.binder == 'undefined' ) {
			if( _req.task == "list" ) {
				/* response for folder fetching */
				this.initFolderBinders( _req, _res );
			} else if( _req.task == "meta_list" ) {  
				this.updateFoldersMetaView( _req, _res );
			} else if( _req.task == "create" ) {
				this.handleFolderCreationResponse( _req, _res );
			} else if( _req.task == "delete" ) {
				this.handleFolderDeleteResponse( _req, _res );
			} else if( _req.task == "rename" ) {
				this.handleFolderRenameResponse( _req, _res );
			} else {
				/* Leave it, as this block is most unlikely */
			}  		 
			
		} else {
			/* The request has been raised from one of the binder object
			 * So forward it to them */
			if( this.binders[ _req.payload.binder ] ) {
				this.binders[ _req.payload.binder ].handleResponse( _req, _res );
			} else {
				/* Unlikely */
			}
		}						
	};
	
};