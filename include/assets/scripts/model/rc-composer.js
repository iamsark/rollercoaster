var rcComposer = function ( _rc ) {
	/* Reference object of controller */
	this.controller = _rc;
	/**/
	this.mode = "";
	/* Holds the list of composer binding object
	 * Multi composer model not supported now */
	this.binders = {};
	/* Holds the current composer's binder key */
	this.cBinder = null;
	/* Current field that is being in focus - ( Used only for Address Fields ) */
	this.currentAddrField = null;
	/* Composer container */
	this.container = $( "#rc-mail-viewer-compose-container" );		
	
	/* Well kick start the module */
	this.init = function() {
		this.registerEvents();
	};
	/* Highly discouraging this call back though */
	this.registerEvents = function() {
	
	};
	
	this.loadComposer = function( _mode, _folder, _msgno, _uid, _isItDraft ) {
		$( "#rc-mail-welcome-screen" ).hide();
		/* Hide the mail viewing section */
		$( "#rc-mail-viewer-view-container" ).hide();
		/* Show the composer section */
		this.container.show();
		/**/
		var header = {};
		var keys = Object.keys( this.binders );
		var name = "composer_" + ( keys.length + 1 );
		/* Assign a title for this composer */
		var title = "Empty - Subject " + ( keys.length + 1 );
		if( _mode != "new" && _folder != "" && _uid != -1 ) {
			/* in this case this is not 'new' mode, then use UID as the key for Binder */
			name = "composer_"+ _mode +"_"+ _uid;
			header = this.controller.lister.getHeader( _folder, _uid );
			if( header ) {
				if( _mode != "forward" ) {
					title = "Re : " + header.subject;
				} else {
					title = "Fwd : " + header.subject;
				}
			}			
		}
		
		if( _isItDraft && _mode == "new" && _uid != -1 ) {
			/* In this case it is from Draft folder */
			name = "composer_draft_"+ _uid;
			header = this.controller.lister.getHeader( _folder, _uid );
			if( header ) {
				title = header.subject
			}
		}
		
		/* Check whether the the composer is already opened for the same uid ( as well as same mode ) */
		if( this.binders[ name ] && this.binders[ name ].mode == _mode ) { console.log("Already opened");
			this.binders[ name ].view.tabHeader.trigger( "click" );
			/* Update mode switcher status */
			$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "selected" );
			$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "disabled" ).addClass( "selected" );	
			return;
		}
		
		/* Check max tab count exceeds */
		if( Object.keys( this.binders ).length < 15 ) {
			this.container.find( "div.rc-tab" ).find( "button.selected" ).removeClass( "selected" );
			this.container.find( "div.rc-tab-content" ).find( "> div" ).hide();
			
			this.binders[ name ] = new rcComposerBinder( this, _mode, name, title, _isItDraft );
			this.binders[ name ].initBinder( _folder, _msgno, _uid );
			/* Update the current composer property */
			this.cBinder = name;
			/* Update mode switcher status */
			$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "selected" );
			$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "disabled" ).addClass( "selected" );	
		} else {
			this.container.find( "div.rc-tab" ).find( "button:last-child" ).trigger( "click" );
			this.controller.notify.show( "You have too many composer window opened already, please close any opened composer to make some space.", "warning", true, 10000 );
		}	
	};
	
	this.closeComposer = function( _cname ) {
		var nextTab = null;
		var cTab = this.binders[ _cname ].view.tabHeader;
		var tabCount = Object.keys( this.binders ).length;
		var isActive = this.binders[ _cname ].view.tabHeader.hasClass( "selected" );
		/* Determine the next tab which has to be selected */
		if( isActive ) {
			if( tabCount > 1 && cTab.is( ":first" ) ) {
				nextTab = cTab.next();
			} else if( tabCount > 1 && cTab.is( ":last-child" ) ) {
				nextTab = cTab.prev();
			} else if( tabCount > 1 ) {
				nextTab = cTab.next();
			} else {
				nextTab = null;
			}			
		}
		/* Now remove the view */
		this.binders[ _cname ].view.tabHeader.remove();
		this.binders[ _cname ].view.tabContent.remove();
		/* Delete the binding object */
		delete this.binders[ _cname ];
		
		if( nextTab != null ) {
			nextTab.trigger( "click" );
		}
		
		if( $.isEmptyObject( this.binders ) ) {
			this.controller.switchContext( "folder" );
			if( ! $.isEmptyObject( this.controller.viewer.binders ) ) {
				$( "#rc-mail-viewer-compose-container" ).hide();
				$( "#rc-mail-welcome-screen" ).hide();
				$( "#rc-mail-viewer-view-container" ).show();	
				/* Update mode switcher status */
				$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "selected" ).addClass( "disabled" );
				$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "disabled" ).addClass( "selected" );				
			} else {
				$( "#rc-mail-viewer-compose-container" ).hide();
				$( "#rc-mail-viewer-view-container" ).hide();
				$( "#rc-mail-welcome-screen" ).show();				
				/* Update mode switcher status */
				$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "selected" ).addClass( "disabled" );
				$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "selected" ).addClass( "disabled" );				
			}
		}
	};
	
	this.doSend = function( _cbname ) {
		var request = rcControllerObj.prepareRequest( "POST", "email", "composer", "SEND", this.binders[  _cbname  ].getComposed() );
		rcControllerObj.helper.syncDock( request, "composer" );
		this.controller.notify.show( "Sending Mail...!", "info", true );
	};
	
	this.doCancel = function( _cbname ) {
		
	};
	
	this.doAttach = function( _cbname ) {
		
	};	
	
	this.reset = function( _cbname ) {
		
	};
	
	/* Used for 'reply', 'reply-all' & 'forward' mode */
	this.fetchMail = function( _folder, _msgNo, _uid, _name ) {
		this.controller.request = this.controller.prepareRequest( "GET", "email", "composer", "reply_mail_body", { "folder": _folder, "uid": _uid, "msgno": _msgNo, "composer": true, "cname": _name } );
		this.controller.dock( this );
	};
	
	this.fetchSuggestion = function( _field ) {
		this.currentAddrField = _field;
		var request = rcControllerObj.prepareRequest( "GET", "email", "composer", "uslist", { "cname": this.cBinder, "search": this.currentAddrField.val() } );
		rcControllerObj.helper.syncDock( request, "composer" );
	};
	
	this.handleResponse = function( _req, _res ) {		
		if( _req.task == "uslist" ) {
			if( _req.payload.cname && _req.payload.cname != "" ) {
				this.binders[ _req.payload.cname ].view.showSuggestion( _res );
			} else {
				this.controller.notify.show( "Internal error, composer key missing, please try again.!", "error", false );
			}			
		} else if( _req.task == "SEND" ) {
			if( _res.status ) {
				this.controller.notify.show( "Sent Successfully.!", "success", false );
				/* Mail sent successfully so we can close the composer window safely */
				if( _req.payload.cname && _req.payload.cname != "" ) {
					this.closeComposer( _req.payload.cname );
				} else {
					this.controller.notify.show( "Internal error, composer key missing, please try again.!", "error", false );
				}				
			} else {
				this.controller.notify.show( "Sending Failed.!", "error", false );
			}
		} else if( _req.task == "reply_mail_body" ) {
			if( _req.payload.cname && _req.payload.cname != "" ) {
				this.binders[ _req.payload.cname ].parseMail( _res );
			} else {
				this.controller.notify.show( "Internal error, composer key missing, please try again.!", "error", false );
			}
		}
	};
	
	/**
	 * Called from confirm box
	 */
	this.onUserConfirmed = function( _task, _action ) {
		
	};
};