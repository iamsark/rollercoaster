var rcListingBinder = function( _lister, _fbinder ) {
	/**/
	this.lister = _lister;
	/**/
	this.fbinder = _fbinder;
	/* Name of the folder which this lister responsible for rendering the headers */
	this.fname = this.fbinder.fname;
	/* Total number of page ( total_header / headerPerPage ) */
	this.totalPage = 0;
	/* Page index that is being fetched */
	this.currentSyncPage = 1;
	/* Number of headers per page */
	this.headerPerPage = 50;
	/* Will be 'true' once all the headers are fetched */
	this.isCacheReady = false;
	/* Will be 'true' as long as the header fetching process is in progress */
	this.syncInProgress = false;
	/* Count property to prevent sync re attempt ( we use this to prevent dead lock ) */
	this.syncTry = 0;
	/*  */
	this.waitingForMeta = false;
	/* Holds the mail header list of the active folder */
	this.headers = [];	
	/* Holds the  */
	this.checkedHeaders = [];
	/* Holds the search result header list */
	this.searchedHeaders = [];
	/* Used as backup buffer while reloading headers
	 * on background sync */
	this.tempHeader = [];
	/* UI layer object, which is responsible for rendering the Header List Viewer */
	this.view = null;		
	
	this.init = function() {
		/* Instanciate the view object
		 * Note, we are not inflating the view yet */
		this.view = new rcListViewer( this, this.lister.headerContainer );
		/* Update the  */
		if( this.fbinder.total_message != -1 && this.fbinder.total_unread != -1 ) {
			this.updatePagerParams();
		}		
		/* Start to fetch the headers from the host */		
		this.fetchHeaders( true );
	};
	
	/* Called whenever user clicked the folder menu item */
	this.fetchHeaders = function( _isDirect ) {
		var isDirect = ( typeof _isDirect != "undefined" ) ? _isDirect : false;
		if( isDirect ) {
			if( ! this.syncInProgress ) {
				if( this.fbinder.total_message != -1 ) { 
					/* Don't bother to update the pager params */
					this.updatePagerParams();
					/* Reset waiting flag */
					this.waitingForMeta = false;
					
					if( this.fbinder.total_message > 0 ) {
						rcControllerObj.request = rcControllerObj.prepareRequest( "GET", "email", "lister", "list", { "folder": this.fbinder.fname, "binder": this.fbinder.fname, "page": this.currentSyncPage, "count": this.fbinder.total_message, "sort": rcControllerObj.folder.sort } );
						rcControllerObj.dock( this.fbinder.folder );
						/* Set the sync flaq to 'true' */
						this.syncInProgress = true;
						/* Show the spinning icon */
						this.fbinder.view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).attr( "class", "" ).addClass( "fa fa-gear fa-spin" );
					} else {						
						/* There is a chance that spinner is still spinning, so restore it to original icon */
						this.fbinder.view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).attr( "class", "" ).addClass( "fa fa-" + this.fbinder.icon );
						/* Display Zero message alert */
						/*Make sure the view has the latest headers */
						this.view.setHeaders();
						/* Inflate the view */
						this.view.loadView();											
					}				
				} else {
					this.syncTry++;
					/* Set waiting flag */
					this.waitingForMeta = true;
					/* So we will try for 180 sec ( approx 3 min ) to load the folder meta */
					if( this.syncTry < 180 ) {
						var fname = this.fbinder.fname;			
						setTimeout( function() {
							rcControllerObj.lister.binders[ fname ].fetchHeaders( true );
						}, 1000 );
					} else {
						this.syncTry = 0;
					}	
				}		
			} else {
				/* There is a chance that spinner is still spinning, so restore it to original icon */
				this.fbinder.view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).removeClass().addClass( "fa fa-" + this.fbinder.icon );
			}
		} else {
			var request = rcControllerObj.prepareRequest( "GET", "email", "lister", "sync", { "folder": this.fbinder.fname, "binder": this.fbinder.fname, "page": this.currentSyncPage, "count": this.fbinder.total_message, "sort": rcControllerObj.folder.sort } );
			rcControllerObj.helper.syncDock( request, "lister" );
		}						
	};
	
	/**
	 * This will be used, only if user clicked the folder menu
	 * For direct Sync
	 */
	this.processSync = function( _req, _res ) {
		if( _res.status && ( _req.payload.sort == rcControllerObj.folder.sort ) ) {
			if( _req.task == "list" ) {
				/* This means this is the initial set of headers ( first page ) has come
				 * Now it's safe to load the listing view */
				this.headers = _res.payload;
				/* Prepare the header object */
				this.view.setHeaders();
				/* Well inflate the view */
				this.view.loadView();
				/* There is a chance that spinner is still spinning, so restore it to original icon */
				this.fbinder.view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).removeClass().addClass( "fa fa-" + this.fbinder.icon );
			} else if( _req.task == "sync" ) {
				/* It's from background sync respons */
				this.appendHeaders( _res.payload );
			} else {
				/* Very unlikely */
				return;
			}			
			/* Well this is the right place to fetching all the pages */					
			if( this.currentSyncPage < this.totalPage ) {
				this.currentSyncPage++;
				this.fetchHeaders( false );
			} else {
				/* reset the sync flaq */
				this.syncInProgress = false;
				/* Set the cache ready flaq */
				this.isCacheReady = true;
				/* reset the current page count */
				this.currentSyncPage = 1;
			}				
		} else {
			/* Clear the mail list view, chances are that it still displaying messages from previous selected folder */
			this.view.container.html( "" );
			/* There is a chance that spinner is still spinning, so restore it to original icon */
			this.fbinder.view.mItem.find( "a.rc-mail-folder-view-menu" ).find( "i" ).removeClass().addClass( "fa fa-" + this.fbinder.icon );
			/* Show the error message to the user */
			rcControllerObj.notify.show( _res.message, "error" );		
		}		
	};
	
	/**/
	this.appendHeaders = function( _headers ) {
		/* Normal order */
		this.headers.push.apply( this.headers, _headers );
		/**/
		this.view.setHeaders();
		/**
		 * This function does one of the important task
		 * Since we are using server side pagination and performance List View
		 * For obivious reason we are starting to render as soon as we get the first page ( which is about 50 headers )
		 * If user tries to do some thing whithin that time itself, when the rest of the headers are still being downloaded.
		 * We need to reapply the user action whenever more headers start to appended
		 **/	
		/* Trigger scrolling event for incomplete data rendering */
		this.view.isEndReached = false;
		/**/
		this.view.prepareGroupLabelMeta();
		
		if( this.fbinder.fname == rcControllerObj.folder.current ) {
			/* Now if the user scrolled down upto bottom, load the new headers */
			if( this.lister.headerContainer.scrollTop() + this.lister.headerContainer.innerHeight() >= this.lister.headerContainer[0].scrollHeight ) {			
				this.view.loadNextPage();
			}
			/* Now if he is in the middle of search operation than do it again */
			var searchTxt = $( "#rc-mail-search-text" );
			if( searchTxt.val() != "" ) {
				this.search( searchTxt );
			}
			/* Select all checkbox is checked than mark newly headers also checked */
			var selAllCheck = $( "#rc-mail-select-all-check" );
			if( selAllCheck.is( ":checked" ) ) {
				this.onHeaderSelectAll( selAllCheck );
			}
		}	
		
	};
	
	/**
	 * This will be used by the Ticker Implementation
	 * To reload the header cahce perioritically ( If any changes detected )
	 */
	this.processBackgroundSync = function( _req, _res ) {
		if( _res.status && ( _req.payload.sort == rcControllerObj.folder.sort ) ) {
			if( _req.task == "BACKGROUND_SYNC_HEADERS" ) {
				this.binder.appendHeaders( _res.payload );
				this.binder.tempHeader.push.apply( this.tempHeader, _res.payload );
				if( this.currentSyncPage < this.totalPage ) {
					this.currentSyncPage++;
					var request = rcControllerObj.prepareRequest( "GET", "email", "folder", "BACKGROUND_SYNC_HEADERS", { "folder": this.fbinder.fname, "page": this.currentSyncPage, "count": this.fbinder.total_message, "sort": rcControllerObj.folder.sort } );
					rcControllerObj.helper.syncDock( request, "folder" );
				} else {
					/* reset the current page count */
					this.currentSyncPage = 1;
					/* Swap the old headers with newly synched headers */
					this.headers = this.tempHeader;
					/* Well reload the View ( if it is currently viewed ) */
					if( this.fbinder.name == rcControllerObj.folder.current ) {
						this.binder.view.reloadView();
					}
				}
			}			
		} else {
			if( _req.payload.sort != rcControllerObj.folder.sort ) {
				/* Sorting option has been changed
				 * re start the sync process */
				this.reloadHeaders();
			}
			// Background syc failed
		}
	};
	
	this.reloadHeaders = function() {
		/* if the cache already not loaded then no need to do the Sync */
		if( this.isCacheReady && ! this.syncInProgress ) {
			/*Reset the sync page property */
			this.currentSyncPage = 1;
			/**/
			this.binder.tempHeader = [];
			/* Get the sort option */
			var sort = ( rcControllerObj.helper.getSortOption() == "newest" ) ? "DSC" : "ASC";
			/* Initiate the sync */
			var request = rcControllerObj.prepareRequest( "GET", "email", "folder", "BACKGROUND_SYNC_HEADERS", { "folder": this.fbinder.name, "page": this.currentSyncPage, "count": this.fbinder.total, "sort": rcControllerObj.folder.sort } );
			rcControllerObj.helper.syncDock( request, "folder" );
		}
	};
	
	/* Used to load the view for this binder */
	this.loadView = function() {
		if( this.isCacheReady ) {
			/*Make sure the view has the latest headers */
			this.view.setHeaders();
			/* Inflate the view */
			this.view.loadView();
		} else {
			/* Well looks like the cache is dirty 
			 * Happens during Bulk delete, Move operation */
			this.fetchHeaders( true );
		}
	};
	
	/* Remove the header entry from cache
	 * and reload the list view */
	this.removeMessage = function( _uid ) {
		var index = -1;
		for( var i = 0; i < this.headers.length; i++ ) {
			if( this.headers[i].uid == _uid ) {
				index = i;
				break;
			}
		}
		if( index != -1 ) {
			this.headers.splice( index, 1 );
			if( this.fname == rcControllerObj.folder.current ) {
				/* Reload the Lister only if it is viewed currently */
				this.view.reloadView();
			}			
		}
	};

	this.search = function( _field ) {
		var sTxt = _field.val();
		if( ! this.lister.waitingForMeta ) {
			if( _field.attr( "type" ) != "text" ) {
				sTxt = $( "#rc-mail-search-text" ).val();
			}
			
			var opt = rcControllerObj.helper.getSearchOption();
			if( ! opt ) {
				opt = "subject";
			}	
			
			this.searchedHeaders = [];
			if( sTxt != "" ) {
				this.lister.searchContext = true;
				for( var i = 0; i < this.headers.length; i++ ) {
					if( this.headers[i][ opt ].toLowerCase().indexOf( sTxt ) != -1 ) {
						this.searchedHeaders.push( this.headers[i] );
					}
				}
			} else {
				this.lister.searchContext = false;
			}		
		
			this.view.setHeaders();
			/* Reload the view */
			this.view.loadView();
		}		
	};
	
	this.onHeaderChecked = function( _field ) {		
		var hIndex = -1;
		var iIndex = -1;
		var uid = _field.attr( "data-uid" );
		for( var i = 0; i < this.headers.length; i++ ) {
			if( this.headers[i].uid == uid ) {
				index = i;
				break;
			}
		}
		if( index >= 0 ) {
			if( _field.is( ":checked" ) ) {
				this.headers[ index ].checked = true;
				this.checkedHeaders.push( uid );
			} else {				
				this.headers[ index ].checked = false;
				for( var i = 0; i < this.checkedHeaders.length; i++ ) {
					if( this.checkedHeaders[i] == uid ) {
						iIndex = i;
						break;
					}
				}
				if( iIndex >= 0 ) {
					this.checkedHeaders.splice( iIndex, 1 );
				}
			}
		}
		if( this.checkedHeaders.length == this.headers.length ) {
			$( "#rc-mail-select-all-check" ).prop( 'checked', true );
		} else {
			$( "#rc-mail-select-all-check" ).prop( 'checked', false );
		}
	};
	
	this.onHeaderSelectAll = function( _field ) {
		if( _field.is( ":checked" ) ) {
			for( var i = 0; i < this.headers.length; i++ ) {
				this.headers[i].checked = true;
				this.checkedHeaders.push( this.headers[i].uid );
			}
		} else {
			for( var i = 0; i < this.headers.length; i++ ) {
				this.headers[i].checked = false;			
			}
			this.checkedHeaders = [];
		}
		this.view.reloadView();
	};
	
	/* 
	 * Will be called whenever folder meta is updated
	 **/
	this.updatePagerParams = function() {		
		if( this.fbinder.total_message > 0 ) {
			this.totalPage =  Math.ceil( this.fbinder.total_message / this.headerPerPage );
		} else {
			this.totalPage = 0;
		}	
		/* Update the view property too */
		this.view.totalNumberOfPages = this.totalPage;
	};
	
	
	this.handleResponse = function( _req, _res ) {		
		if( _req.task == "list" || _req.task == "sync" ) {
			this.processSync( _req, _res );
		}
	};
	
};