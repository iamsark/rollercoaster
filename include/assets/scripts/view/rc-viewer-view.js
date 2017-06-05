var rcViewerView = function( _binder, _parent ) {
	/**/
	this.binder = _binder;
	/**/
	this.parent = _parent;
	/**/
	this.msgFrame = null;
	/**/
	this.plainFrame = null;
	/**/
	this.tabHeader = null;
	/* Tab content object, where all entire  */
	this.tabContent = null;
	/* Container object, which is used to holds the mail header section */
	this.headerPart = null;
	/* Container object, which is used to holds the mail body */
	this.contentPart = null;
	/* Container object, which is used to holds the mail footer section ( usually Attachments ) */
	this.footerPart = null;
	
	/**/
	this.initView = function() {
		/* Make sure viewer container is visible */
		$( "#rc-mail-viewer-compose-container" ).hide();
		$( "#rc-mail-welcome-screen" ).hide();
		$( "#rc-mail-viewer-view-container" ).show();
		/* Render the viewer skeletton */
		this.renderSkeletton();	
		/* Update current UID property of Viewer */
		rcControllerObj.viewer.uid = this.binder.uid;
	};
	
	/* Responsible for rendering Tab Header & Tab Content */
	this.renderSkeletton = function() {
		/* Mail viewer tab header */
		this.tabHeader = $( '<button class="rc-tab-btn rc-mail-viewer-tab-btn selected" data-folder="'+ this.binder.fname +'" data-uid="'+ this.binder.uid +'" data-target="rc-mail-'+ this.binder.uid +'-view" title=""><i class="fa fa-spin fa-gear"></i> Loading <a href="'+ this.binder.uid +'" class="rc-mail-viewer-close-btn"><i class="fa fa-times"></i></a></button>' );
		/* Mail content container */
		this.tabContent = $( '<div id="rc-mail-'+ this.binder.uid +'-view" class="rc-mail-message-viewer" data-uid="'+ this.binder.uid +'" ></div>' );
		
		this.tabContent.height( $( "#rc-mail-viewer-section" ).height() - 50 );
		
		var loadSpinner = '<div class="cssload-thecube">';
		loadSpinner += '<div class="cssload-cube cssload-c1"></div>';
		loadSpinner += '<div class="cssload-cube cssload-c2"></div>';
		loadSpinner += '<div class="cssload-cube cssload-c4"></div>';
		loadSpinner += '<div class="cssload-cube cssload-c3"></div>';
		loadSpinner += '</div>';		
		this.tabContent.html( loadSpinner );
		
		/* Before append set the width for the tab header */
		var width = 0;
		var totalTabs = Object.keys( rcControllerObj.viewer.binders ).length;
		var totalWidth = parseInt( this.parent.find( "div.rc-tab" ).width() );
		
		if( totalTabs > 1 ) {
			width = this.parent.find( "div.rc-tab" ).find( "button:first-child" ).outerWidth( true );
			if( ( totalTabs * width ) >= totalWidth ) {
				width = Math.floor( totalWidth / totalTabs );
				this.parent.find( "div.rc-tab" ).find( "button" ).innerWidth( width - 2 );
			}			
			this.tabHeader.innerWidth( width - 2 );
		}
		
		/* Attach tab header with Parent */
		this.parent.find( "div.rc-tab" ).append( this.tabHeader );
		/* Attach tab content with Parent */
		this.parent.find( "div.rc-tab-content" ).append( this.tabContent );		
		/* Recalculate the width of the each tab header to prevent wrapping */
	};
	
	/* Responsible for initiating data loading */
	this.loadMail = function() {
		this.htmlBody = this.binder.htmlBody;
		this.plainBody = this.binder.plainBody;
		this.attachments = this.binder.attachments;
		this.renderViewer();
	}
	
	/* Responsible for rendering the entire mail ( header, content, footer, attachments ) */
	this.renderViewer = function() {
		this.tabContent.html( "" );
		this.tabHeader.attr( "title", this.binder.header.subject );
		if( this.binder.uid == rcControllerObj.viewer.uid ) {
			/* Clear other tabs icon */
			var keys = Object.keys( rcControllerObj.viewer.binders );
			for( var i = 0; i < keys.length; i++ ) {
				rcControllerObj.viewer.binders[ keys[i] ].view.tabHeader.find( "i.fa-envelope-open" ).attr( "class", "" ).addClass( "fa fa-envelope-open-o" );
			}
			this.tabHeader.html( '<i class="fa fa-envelope-open"></i> ' + this.binder.header.subject + ' <a href="'+ this.binder.uid +'" class="rc-mail-viewer-close-btn"><i class="fa fa-times"></i></a>' );
		} else {
			this.tabHeader.html( '<i class="fa fa-envelope-open-o"></i> ' + this.binder.header.subject + ' <a href="'+ this.binder.uid +'" class="rc-mail-viewer-close-btn"><i class="fa fa-times"></i></a>' );
		}
				
		/* Render and inject mail header section */
		this.tabContent.append( this.renderHeader() );
		/* Render and inject mail content section */
		this.tabContent.append( this.renderContent() );
		/* Render and inject mail footer section */
		this.tabContent.append( this.renderFooter() );
		/**/
		if( this.binder.header.unseen.toUpperCase() == "U" ) {
			/* Reload folders meta ( for all folders ) */
			rcControllerObj.folder.binders[ this.binder.fname ].syncMeta();
			/* Update the 'seen' flag in header on the local cache */
			rcControllerObj.lister.updateCahceFlag( this.binder.fname, this.binder.uid, "seen", true );
		}
		/* Load the actual mail body inside iframe */
		this.loadUnSafeBody();
	};
	
	/* Responsible for rendering Mail Header Section */
	this.renderHeader = function() {		
		var to = "";
		var toName = "";
		var cc = "";
		var bcc = "";
		var from = "";
		var fromName = "";
		var date = "";
		var flaQ = "";
		var fullHeader = "";
		
		var answeredDisplay = ( this.binder.header.answered.toUpperCase() == 'A' ) ? "display: inline-block" : "display: none";
		var flaggedDisplay = ( this.binder.header.flagged.toUpperCase() == 'F' ) ? "display: inline-block" : "display: none";;
		
		this.headerPart = '<div class="rc-mail-viewer-header">';
		this.headerPart += '<h3 class="rc-mail-viewer-subject" title="'+ this.binder.header.subject +'">'+ this.binder.header.subject +'</h3>';
		this.headerPart += '<table class="rc-mail-viewer-meta-table">';
		this.headerPart += '<tr>';
		this.headerPart += '<td class="rc-mail-viewer-user-thumb">';
		this.headerPart += '<span class="rc-mail-viewer-user-placeholder"><i class="fa fa-user"></i></span>';
		this.headerPart += '</td>';
		this.headerPart += '<td>';
		this.headerPart += '<a href="" class="rc-mail-viewer-from">'+ this.getFromDetail() +'</a>';
		this.headerPart += '<div class="rc-mail-viewer-to-block"><span>To</span> : '+ this.getToDetails() +' <button class="rc-header-meta-popup-btn" data-uid="'+ this.binder.uid +'" title="Show Details"><i class="fa fa-caret-down"></i></button>';
		this.headerPart += '<div class="rc-mail-viewer-flag-container"><span class="rc-mail-viewer-flag-answered" style="'+ answeredDisplay +'"><i class="fa fa-mail-reply"></i></span><span class="rc-mail-viewer-flag-flagged" style="'+ flaggedDisplay +'"><i class="fa fa-flag"></i></span></div></div>';
		this.headerPart += '<div class="rc-mail-viewer-header-detail-block">'+ rcControllerObj.helper.getFullHeaderMetaWidget( this.binder.header, "left" ) +'</div>';
		this.headerPart += '</td></tr></table>';
		
		this.headerPart += '<div class="rc-mail-viewer-header-right-bar">';		
		
		this.headerPart += '<table><tr><td>';
		this.headerPart += '<div class="rc-mail-viewer-date-block">'+ this.getDate() +'</div>';
		this.headerPart += '</td></tr><tr><td>';
		
		if( this.binder.attachments.length > 0 ) {		
			this.headerPart += '<div class="rc-mail-viewer-attachment-section">';
			this.headerPart += '<div class="rc-mail-viewer-attachment-toggle"><i class="fa fa-paperclip"></i> '+ ( this.binder.attachments.length ) +' Attachment'+ ( ( this.binder.attachments.length > 1 ) ? "s" : "" );
			this.headerPart += '<a href="#" class="rc-mail-viewer-attachment-drop-btn"><i class="fa fa-caret-up"></i></a>';
			this.headerPart += '</div>';
			
			this.headerPart += '<div class="rc-mail-attachment-list-drop">';
			
			for( var i = 0; i < this.binder.attachments.length; i++ ) {
				this.headerPart += '<div class="rc-mail-viewer-attachment-row '+ ( i == ( this.binder.attachments.length - 1 ) ? "rc-mail-attach-last-row" : "" ) +'" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-fname="'+ this.binder.attachments[i].filename +'">';
				this.headerPart += '<table><tr><td>';
				this.headerPart += '<span><i class="fa fa-'+ this.getIconForFileType( this.binder.attachments[i].extension ) +'"></i></span>';
				this.headerPart += '</td><td>';
				this.headerPart += '<p>'+ this.binder.attachments[i].filename +' <i class="fa fa-eject fa-rotate-180"></i></p>';
				this.headerPart += '</td></tr></table>';
				this.headerPart += '</div>';
			}
			
			if( this.binder.attachments.length > 1 ) {
				this.headerPart += '<a href="#" class="rc-mail-viewer-download-all" data-folder="'+ this.binder.fname +'" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'">Download All</a>';
			}
			
			this.headerPart += '</div>';			
			this.headerPart += '</div>';
		}
		
		this.headerPart += '<div class="rc-mail-viewer-action-bar">';		
		this.headerPart += '<a href="#" class="rc-mail-viewer-action" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="reply"><i class="fa fa-mail-reply"></i></a>';
		this.headerPart += '<a class="rc-mail-viewer-action-drop-btn"><i class="fa fa-ellipsis-v"></i></a>';
		this.headerPart += '<div class="rc-viewer-extra-action-drop rc-mail-drop-container">';
		this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-reply" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="reply-all"><i class="fa fa-mail-reply-all"></i> Reply All</a>';
		this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-reply-all" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="forward"><i class="fa fa-mail-forward"></i> Forward</a>';
		this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-print" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="print"><i class="fa fa-print"></i> Print</a>';
		this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-trash" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="delete"><i class="fa fa-trash"></i> Delete</a>';
		this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-move" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="move"><i class="fa fa-exchange"></i> Move</a>';
		if( this.binder.header.flagged.toUpperCase() == 'F' ) {
			this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-flag" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="unflag"><i class="fa fa-flag-o"></i> Unflagged</a>';
		} else {
			this.headerPart += '<a href="#" class="rc-mail-viewer-action rc-mail-viewer-action-flag" data-msgno="'+ this.binder.msgno +'" data-uid="'+ this.binder.uid +'" data-folder="'+ this.binder.fname +'" data-action="flag"><i class="fa fa-flag"></i> Flagged</a>';
		}
		
		this.headerPart += '</div>';
		this.headerPart += '</div>';
		
		this.headerPart += '</td></tr></table>';
		
		this.headerPart += '</div>';
		this.headerPart += '</div>';
		
		this.headerPart = $( this.headerPart );
		return this.headerPart;		
	};
	
	/* Responsible for rendering Mail Content Section */
	this.renderContent = function() {		
		this.contentPart = '<div class="rc-mail-viewer-content">';
		this.contentPart += '<iframe name="rc-mail-'+ this.binder.uid +'-message-body" class="rc-mail-message-body-frame"></iframe>';
		this.contentPart += '</div>';
		
		this.contentPart = $( this.contentPart );
		this.msgFrame = this.contentPart.find( "iframe" );
		return this.contentPart;		
	};
	
	/* Responsible for rendering Mail Footer Section */
	this.renderFooter = function() {		
		this.footerPart = $( '<div class="rc-mail-viewer-footer"></div>' );
		return this.footerPart;		
	};
	
	/* Load all contents except all external resources */
	this.loadSafeBody = function() {
		
	};
	
	/* Load all contents including images */
	this.loadUnSafeBody = function() {	
		var me = this;
		if( this.binder.htmlBody && this.binder.htmlBody.trim() != "" ) {
			var document = this.msgFrame[0].contentDocument || this.msgFrame[0].contentWindow.document;
			document.open();
			document.write( this.binder.htmlBody );
			document.close();
			/* To close the drop down when clicking on iframe */
			document.body.onclick = function() {
				$( "div.rc-mail-drop-container" ).hide();
			};	
			this.msgFrame.ready(function(){
				/* Calculate and set the height */
				me.adjustFrameHeight();
			});
		} else if( this.binder.plainBody && this.binder.plainBody.trim() != "" ) {
			/* Since this message contains only plain text message
			 * No need to show it on iframe */
			this.msgFrame.hide();
			this.plainFrame = $( '<div class="rc-viewer-plain-text-body"></div>' );
			this.msgFrame.before( this.plainFrame );
			this.plainFrame.html( this.binder.plainBody );
			this.adjustFrameHeight();
		}		
	};
	
	/**/
	this.closeMail = function() {
		if( this.tabHeader ) {
			this.tabHeader.remove();
		}
		if( this.tabContent ) {
			this.tabContent.remove();
		}
	};
	
	this.getFromDetail = function() {
		if( this.binder.header.from_address && this.binder.header.from_address != "" ) {
			return rcControllerObj.helper.escapeHtml( this.binder.header.from_address );
		} else {
			var email = "";
			for( var i = 0; i < this.binder.header.from.length; i++ ) {
				email = this.binder.header.from[i].mailbox +"@"+ this.binder.header.from[i].host;
			}
			return email;
		}
	}; 
	
	this.getToDetails = function() {		
		var email = "";
		var rCount = 0;
		var isSent = false;
		var allRecipient = [];	
		
		if( typeof this.binder.header.to != 'undefined' && $.isArray( this.binder.header.to ) ) {
			allRecipient.push.apply( allRecipient, this.binder.header.to );
		}
		if( typeof this.binder.header.cc != 'undefined' && $.isArray( this.binder.header.cc ) ) {
			allRecipient.push.apply( allRecipient, this.binder.header.cc );
		}
		if( typeof this.binder.header.bcc != 'undefined' && $.isArray( this.binder.header.bcc ) ) {
			allRecipient.push.apply( allRecipient, this.binder.header.bcc );
		}
		
		/* Get the total recipient count */
		rCount = allRecipient.length;
		
		/* Determine whether this is a Sent mail */
		for( var i = 0; i < this.binder.header.from.length; i++ ) {
			email = this.binder.header.from[i].mailbox +"@"+ this.binder.header.from[i].host;
			if( email == rc_user ) {
				isSent = true;
				break;
			}
		}
		
		if( rCount == 1 ) {
			if( isSent ) {
				return ( typeof allRecipient[0].personal != 'undefined' && allRecipient[0].personal != "" ) ? allRecipient[0].personal : allRecipient[0].mailbox;
			} else {
				return "me";
			}	
		} else if( rCount == 2 ) {			
			if( ! isSent ) {
				for( var i = 0; i < allRecipient.length; i++ ) {
					email = allRecipient[i].mailbox +"@"+ allRecipient[i].host;
					if( email != rc_user ) {
						email = ( ( typeof allRecipient[i].personal != 'undefined' ) && allRecipient[i].personal != "" ) ? allRecipient[i].personal : allRecipient[i].mailbox;	
						return "me & " + email;		
					}
				}		
				return "me & 1 other";
			} else {
				//for( var i = 0; i < allRecipient.length; i++ ) {
				email = ( typeof allRecipient[0].personal != 'undefined' && allRecipient[0].personal != "" ) ? allRecipient[0].personal : allRecipient[0].mailbox;
				email += " & ";
				email += ( typeof allRecipient[1].personal != 'undefined' && allRecipient[1].personal != "" ) ? allRecipient[1].personal : allRecipient[1].mailbox;	
				return email;
				
			}		
		} else {
			if( ! isSent ) {
				return "me & " + ( rCount - 1 ) + " others";
			} else {
				email = ( typeof allRecipient[0].personal != 'undefined' && allRecipient[0].personal != "" ) ? allRecipient[0].personal : allRecipient[0].mailbox;
				email += " & " + ( rCount - 1 ) + " others ";
				return email;
			}			
		}		
	};
	
	this.getDate = function() {
		return rcControllerObj.helper.parseFormatUnixDate( this.binder.header.date, "DD MMM YYYY : hA" );
	};
	
	this.adjustFrameHeight = function() {
		var tabHeaderHeight = 50;
		var containerHeight = $( "#rc-mail-viewer-section" ).height();
		var mailheaderHeight = this.msgFrame.parent().prev().outerHeight();
		var frameHeight = containerHeight - ( tabHeaderHeight + mailheaderHeight );
		/* Set the iframe height */
		this.msgFrame.height( frameHeight );
		if( this.plainFrame ) {
			/* Includes padding top, bottom which is additional 30px */
			this.plainFrame.height( frameHeight - 30 );
		}	
		this.tabContent.height( $( "#rc-mail-viewer-section" ).height() - tabHeaderHeight );
	};
	
	this.getIconForFileType = function( _ext ) {
		/* Convert to lowercase */
		_ext = _ext.toLowerCase();
		/* List of popular extensions */
		var pExtensions = [ "pdf", "ppdf" ];
		var msWExtensions = [ "doc", "docx", "docm" ];
		var msEExtensions = [ "xls", "xlsx", "xlsm" ];
		var msPExtensions = [ "ppt", "pptx", "pptm" ];
		var sExtensions = [ "c", "cpp", "java", "class", "php", "js", "html", "xml", "css", "asp", "awk", "asm", "csv", "sql", "h", "jsp", "log", "pl", "ps", "sh", "bat", "", "", "", "" ];
		var iExtensions = [ "jpg", "jpeg", "png", "gif", "bmp", "tiff", "exif", "ico" ];
		var vExtensions = [ "webm", "flv", "vob", "ogg", "avi", "mov", "wmv", "mp4", "mpg", "mpeg", "m4v", "3gp", "f4v" ];
		var aExtensions = [ "aac", "mp3", "wav", "wma" ];
		var zExtensions = [ "ar", "cpio", "shar", "iso", "tar", "bz2", "7z", "apk", "cab", "ear", "jar", "rar", "tar.gz", "tgz", "tar.Z", "tar.bz2", "zip" ];
		
		if( pExtensions.indexOf( _ext ) != -1 ) {
			return "file-pdf-o";
		} else if( msWExtensions.indexOf( _ext ) != -1 ) {
			return "file-word-o";
		} else if( msEExtensions.indexOf( _ext ) != -1 ) {
			return "file-excel-o";
		} else if( msPExtensions.indexOf( _ext ) != -1 ) {
			return "file-powerpoint-o";
		} else if( sExtensions.indexOf( _ext ) != -1 ) {
			return "file-code-o";
		} else if( iExtensions.indexOf( _ext ) != -1 ) {
			return "file-image-o";
		} else if( vExtensions.indexOf( _ext ) != -1 ) {
			return "file-video-o";
		} else if( aExtensions.indexOf( _ext ) != -1 ) {
			return "file-audio-o";
		} else if( zExtensions.indexOf( _ext ) != -1 ) {
			return "file-archive-o";
		} else {
			return "file";
		}
	};
	
	/* Fetch the gravatar image from 'https://www.gravatar.com/avatar/HASH' */
	this.getGravatar = function() {
		
	};
	
};