/**
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @purpose		: Responsible for rendering the mail body
 * 
 **/
var rcViewer = function( _rc ) {
	/* Controller object's reference */
	this.controller = _rc;
	/* Holds the list of viewer binding object */
	this.binders = {};
	/* Holds the container reference where the mails will be rendered */
	this.container = $( "#rc-mail-viewer-view-container" );
	/* Always point to current viewing mail id */
	this.uid = null;
	/**/
	this.oldContext = this.controller.context;
	
	this.init = function() {};
	
	this.initMailView = function( _uid, _msgno ) {	
		var current = this.controller.folder.current;
		if( ! this.controller.folder.binders[ current ].isDraft ) {
			/* Check whether this message has already been loaded */
			if( this.binders[ _uid ] ) {				
				if( this.binders[ _uid ].loaded ) {
					this.binders[ _uid ].view.tabHeader.trigger( "click" );
					$( "#rc-mail-header-container div.rc-header-block.selected" ).find( "div.rc-mail-loader-spinner" ).remove();
					/* Update mode switcher status */
					$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "selected" );
					$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "disabled" ).addClass( "selected" );
				}
			} else {
				/* Check max tab count exceeds */
				if( Object.keys( this.binders ).length < 15 ) {
					/* Hide the current mail view & remove the selected class of from the currently viewed tab header */
					this.container.find( "div.rc-tab" ).find( "button.selected" ).removeClass( "selected" );
					this.container.find( "div.rc-tab-content" ).find( "> div" ).hide();
					/* Now load the fetched mail object into the view */
					this.binders[ _uid ] = new rcViewerBinder( this, this.controller.folder.current );			
					this.binders[ _uid ].loadView( _uid, _msgno );
					/* Initialize fetching operation */
					var request = this.controller.prepareRequest( "GET", "email", "viewer", "load", { "folder": this.controller.folder.current, "uid": _uid, "msgno": _msgno } );
					this.controller.helper.syncDock( request, "viewer" );
				} else {
					this.container.find( "div.rc-tab" ).find( "button:last-child" ).trigger( "click" );
					this.controller.notify.show( "You have too many mails opened already, please close any opened mail to make some space.", "warning", true, 10000 );
				}			
			}
			/* Update mode switcher status */
			$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "selected" );
			$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "disabled" ).addClass( "selected" );
		} else {
			/* Since this message is from Draft folder, just fetch No need to load it into viewer */	
			/* Switch to composer context */
			this.controller.switchContext( "composer" );
			/* Load the composer view */
			this.controller.composer.loadComposer( "new", this.controller.folder.current, _msgno, _uid, true );	
		}
	};
	
	this.loadMail = function( _request, _response ) {
		if( _response.status ) {	
			/* Start initialize the data binding with view */
			this.binders[ _request.payload.uid ].initBinder( _response.payload );
		} else {
			this.controller.notify.show( "Failed to fetch mail.!", "error" );
		}	
	};
	
	this.downloadAttachment = function( _type, _folder, _uid, _msgno, _filename ) {
		var params = {};
		if( _type == "single" ) {
			params = this.controller.prepareRequest( "GET", "email", "folder", "ATTACHMENT", { "type": "single", "folder": _folder, "uid" : _uid, "msgno" : _msgno, "file": _filename } );
		} else {
			params = this.controller.prepareRequest( "GET", "email", "folder", "ATTACHMENT", { "type": "all", "folder": _folder, "uid" : _uid, "msgno" : _msgno } );			
		}
		window.open( docker + "?is_attach_req=true&rc_param=" + JSON.stringify( params ) );
	};
	
	this.prepareforPrint = function( _folder, _msgno, _uid ) {
		if( this.binders[ _uid ] ) {
			this.printMail({
				"html_body": this.binders[ _uid ].htmlBody,
				"text_body": this.binders[ _uid ].plainBody,
				"header": this.binders[ _uid ].header
			});
		} else {
			this.oldContext = this.controller.context;
			this.controller.notify.show( "Please wait while preparing for printing...", "info" );
			this.controller.request = this.controller.prepareRequest( "GET", "email", "viewer", "print_mail_body", { "folder": _folder, "uid": _uid, "msgno": _msgno } );
			this.controller.dock( this );
		}
	};
	
	this.printMail = function( _data ) {
		
		/* Restore the context */
		this.controller.switchContext( this.oldContext );
		
		var message = _data.html_body;
		if( message.trim() == "" ) {
			message = _data.text_body;
		}
		
		var baseUrlComp = window.location.href.split("/");

	    var html = "<!DOCTYPE HTML>";
	    html += '<html lang="en-us">';
	    html += '<head>';
	    html += '<title>'+ _data.header.subject +'</title>';
	    html += '<style type="text/css">';
	    html += 'body {margin: 10px;}';
	    html += 'table.rc-mail-print-banner {width: 100%;} table.rc-mail-print-banner td {width: 50%;vertical-align: middle;} table.rc-mail-print-banner td h4 {font-size: 16px;text-align: right;}';
	    html += '.rc-mail-print-header-meta table {width: 100%;border-collapse: collapse;border-spacing: 0px;} .rc-mail-print-header-meta table td {vertical-align: top;padding: 2px 20px;text-align: left;} .rc-mail-print-header-meta table td:first-child{font-weight: bold;width: 100px;padding: 1px 0px;}';
	    html += '</style>';
	    html += '</head>';
	    html += "<body>";
	    
	    html += '<table class="rc-mail-print-banner">';
	    html += '<tr>';
	    html += '<td><img src="'+ baseUrlComp[0] +'//'+ window.location.host + rc_print_logo +'" alt="RollerCoaster" /></td>';
	    html += '<td><h4>'+ rc_user +'</h3></td>';
	    html += '</table>';
	    html += '<hr />';
	    html += '<div class="rc-mail-print-header-meta">'+ this.controller.helper.getFullHeaderMetaWidget( _data.header, "" ) +'</div>';
	    html += '<hr />';
	    html += message;
	    html += "</body>";

	    var pWindow = window.open( "", "_blank" );
	    if( pWindow ) {
	    	pWindow.document.write( html );
		    pWindow.addEventListener('load', function(){
		    	this.print();
		    }, false );
		    pWindow.document.close();
	    } else {
	    	this.controller.notify.show( "Seems failed, Have you blocked Popup Window.?", "error" );
	    }	    		
	};
	
	this.closeMail = function( _uid ) {
		var nextTab = null;
		var tabCount = Object.keys( this.binders ).length;
		var isActive = this.binders[ _uid ].view.tabHeader.hasClass( "selected" );
		/* Determine the next tab which has to be selected */
		if( isActive ) {
			if( tabCount > 1 && this.binders[ _uid ].view.tabHeader.is( ":first" ) ) {
				nextTab = this.binders[ _uid ].view.tabHeader.next();
			} else if( tabCount > 1 && this.binders[ _uid ].view.tabHeader.is( ":last-child" ) ) {
				nextTab = this.binders[ _uid ].view.tabHeader.prev();
			} else if( tabCount > 1 ) {
				nextTab = this.binders[ _uid ].view.tabHeader.next();
			} else {
				nextTab = null;
			}			
		}
		/* Now remove the view */
		this.binders[ _uid ].view.tabHeader.remove();
		this.binders[ _uid ].view.tabContent.remove();
		/* Delete the binding object */
		delete this.binders[ _uid ];
		
		if( nextTab != null ) {
			nextTab.trigger( "click" );
			$( "#rc-mail-header-container div.rc-header-block.selected" ).removeClass( "selected" );
			var headerDiv = $( "#rc-mail-header-container div[data-uid="+ nextTab.attr( "data-uid" ) +"]" );
			//headerDiv[0].scrollIntoView();		
			headerDiv.addClass( "selected" );
		}
		/* If no mails are viewed - then reset the selected class from Header List */
		if( $.isEmptyObject( this.binders ) ) {
			$( "#rc-mail-header-container div.rc-header-block.selected" ).removeClass( "selected" );
			if( ! $.isEmptyObject( this.controller.composer.binders ) ) {
				$( "#rc-mail-welcome-screen" ).hide();
				$( "#rc-mail-viewer-view-container" ).hide();
				$( "#rc-mail-viewer-compose-container" ).show();				
				$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "disabled" ).addClass( "selected" );
				$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "selected" ).addClass( "disabled" );
			} else {
				$( "#rc-mail-viewer-compose-container" ).hide();
				$( "#rc-mail-viewer-view-container" ).hide();
				$( "#rc-mail-welcome-screen" ).show();
				$( "#rc-mail-mode-toggle-container > button[data-mode='viewer']" ).removeClass( "selected" ).addClass( "disabled" );
				$( "#rc-mail-mode-toggle-container > button[data-mode='composer']" ).removeClass( "selected" ).addClass( "disabled" );		
			}
		}  
	};
	
	this.renderMailHeader = function() {
		var label, addr, addr_type = [ "to", "cc", "bcc" ];
		this.header = null;
		for( var i = 0; i < this.rc.lastArchive.length; i++ ) {
			if( this.rc.uid == this.rc.lastArchive[i].uid ) {
				this.header = this.rc.lastArchive[i];
			}
		}
		
		if( this.header ) {
			
			$("table.rc-mail-read-user-meta-table span[meta=date]").html( this.header.date );
			$("table.rc-mail-read-user-meta-table span[meta=from]").html( this.header.fromaddress );
			$("table.rc-mail-read-user-meta-table span[meta=subject]").html( this.header.subject );			
					
			for( var i = 0; i < addr_type.length; i++ ) {
				label = "";
				addr = "";
				if( this.header[ addr_type[i] ].length > 0 ) {
					for( var j = 0; j < this.header.to.length; j++ ) {				
						addr = this.header[ addr_type[i] ][j].mailbox + "@" + this.header[ addr_type[i] ][j].host;
						
						if( this.header[ addr_type[i] ][j].personal != 'undefined' ) {
							label += '<span email="'+ addr +'">'+ this.header[ addr_type[i] ][j].personal +'</span>';
						} else {
							label += '<span email="'+ addr +'">'+ this.header[ addr_type[i] ][j].mailbox +'</span>';
						}						
										
						if( j + 1 != this.header[ addr_type[i] ].length ) {
							label += ', ';
						}
					}				
					$("#rc-mail-read-"+ addr_type[i] +"-bar").html( label );
				} else {
					$("#rc-mail-read-"+ addr_type[i] +"-bar").parent().hide();
				}
			}			
			
		} 		
	};
	
	this.setCurrentMailView = function( _uid ) {
		this.uid = _uid;
		/* Clear other tabs icon */
		var keys = Object.keys( this.binders );
		for( var i = 0; i < keys.length; i++ ) {
			this.binders[ keys[i] ].view.tabHeader.find( "i.fa-envelope-open" ).attr( "class", "" ).addClass( "fa fa-envelope-open-o" );
		}
		this.binders[ this.uid ].view.tabHeader.find( "i.fa-envelope-open-o" ).attr( "class", "" ).addClass( "fa fa-envelope-open" );
	};
	
	this.handleResponse = function( _req, _res ) {
		if( _req.task == "load" ) {
			this.loadMail( _req, _res );
		} else if( _req.task == "print_mail_body" ) {
			this.controller.notify.show( "Ready to print", "info", false );
			if( _res.status ) {
				this.printMail( _res.payload );
			} else {
				this.controller.notify.show( "Printing mail failed", "error" );
			}
		} else {
			/* Unlikely */
		}		
	};
	
	/**
	 * Called from confirm box
	 */
	this.onUserConfirmed = function( _task, _action ) {
		
	};
	
};