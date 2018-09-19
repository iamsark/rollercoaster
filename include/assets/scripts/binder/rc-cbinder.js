var rcComposerBinder = function( _composer, _mode, _name, _title, _isItDraft ) {
	/**/
	this.name = _name;
	/**/
	this.composer = _composer;
	/**/
	this.mode = _mode;
	/**/
	this.title = _title;
	/**/
	this.isItDraft = _isItDraft;
	/**/
	this.view = null;	
	/* If it is reply ( all ) or forward mode then this property contains the folder name 
	 * ( which the replied mail belongs to ) */	
	this.fname = "";
	/* If it is reply ( all ) or forward mode then this will contains the corresponding Message ID
	 * remain -1 if it is 'new' mode */
	this.uid = -1;
	/**/
	this.msgNo = -1;
	/**/
	this.draftMessage = null;
	/**
	 * Will contains al header related properties
	 * from		: From addrerss
	 * to 		: To address
	 * cc		: Array of CC address
	 * bcc		: Array of BCC address	 * 
	 **/
	this.header = {
		from: {},
		to: {},
		cc: {},
		bcc: {},
		subject: ""
	};	
	/**
	 * Holds the actual message
	 * html		: HTML message part
	 * text		: Plain Text message part
	 */
	this.message = {
		html: "",
		plain: ""
	};	
	/**/
	this.attachments = [];
	
	/**/
	this.initBinder = function( _folder, _msgno, _uid ) {	
		this.fname = ( typeof _folder != undefined ) ? _folder : "";
		this.msgNo = ( typeof _msgno != undefined ) ? _msgno : -1;
		this.uid = ( typeof _uid != undefined ) ? _uid : -1;
		/* Set from user ( Most of the time it's current logged in user ) */
		this.header.from[ rc_user ] = rc_user;
		/* Inflate the composer view */
		this.view = new rcComposerView( this, this.composer.container );
		this.view.initView();
		/* If the mode is not 'new' rthen fetch the mail body */
		if( this.mode != "new" || this.isItDraft ) {
			rcControllerObj.notify.show( "Preparing composer...!", "info", true );
			this.composer.fetchMail( this.fname, this.msgNo, this.uid, this.name );
		}
	};
	
	/* Parse the fetched mail and stripeout unwanted tags
	 * then load it into appropriate properties */
	this.parseMail = function( _res ) {
		if( _res.status ) {
			if( _res.payload.header ) {
				var email = "";
				var person = "";
				/* Prepare the subject */
				this.header.subject = ( this.title != "" ) ? this.title : _res.payload.header.subject;
				this.view.subject.val( this.header.subject );
				/* prepare the address */
				if( this.mode == "reply" || this.mode == "reply-all" || this.isItDraft ) {
					for( var i = 0; i < _res.payload.header.from.length; i++ ) {
						person = "";
						if( _res.payload.header.from[i].personal ) {
							person = _res.payload.header.from[i].personal;
						}
						email = _res.payload.header.from[i].mailbox +"@"+ _res.payload.header.from[i].host;
						this.header.to[ email ] = person;
						this.view.addAddress( "to", person, email );
					}
					if( this.mode == "reply-all" ) {	
						for( var i = 0; i < _res.payload.header.to.length; i++ ) {
							person = "";
							if( _res.payload.header.to[i].personal ) {
								person = _res.payload.header.to[i].personal;
							}
							email = _res.payload.header.to[i].mailbox +"@"+ _res.payload.header.to[i].host;
							this.header.cc[ email ] = person;
							this.view.addAddress( "cc", person, email );
						}
						for( var i = 0; i < _res.payload.header.cc.length; i++ ) {
							person = "";
							if( _res.payload.header.cc[i].personal ) {
								person = _res.payload.header.cc[i].personal;
							}
							email = _res.payload.header.cc[i].mailbox +"@"+ _res.payload.header.cc[i].host;
							this.header.cc[ email ] = person;
							this.view.addAddress( "cc", person, email );
						}
						for( var i = 0; i < _res.payload.header.bcc.length; i++ ) {
							person = "";
							if( _res.payload.header.bcc[i].personal ) {
								person = _res.payload.header.bcc[i].personal;
							}
							email = _res.payload.header.bcc[i].mailbox +"@"+ _res.payload.header.bcc[i].host;
							this.header.bcc[ email ] = person;
							this.view.addAddress( "bcc", person, email );
						}						
						/* If this mail contains any CC address than make it visible */
						if( Object.keys( this.header.cc ).length > 0 ) {
							this.view.cc.toggle();
						}
						/* If this mail contains any BCC address than make it visible */
						if( Object.keys( this.header.bcc ).length > 0 ) {
							this.view.bcc.toggle();
						}						
					}
				} else {
					this.header.to = {};
					this.header.cc = {};
					this.header.bcc = {};
				}
			}	
			if( ! this.isItDraft ) {
				this.view.prepareComposer( _res.payload );
			} else {
				/* This casde this message from draft folder
				 * so load the content to Editor */
				var content = "";
				if( _res.payload.html_body && _res.payload.html_body.trim() != "" ) {
					content = _res.payload.html_body;
				} else {
					content = _res.payload.text_body;
				}
				this.view.ckeditor.setData( content );
				this.view.ckeditor.focus();
			}			
			rcControllerObj.notify.show( "Composer is ready now.!", "info", false, 2000 );			
		} else {
			rcControllerObj.notify.show( "Loading message failed...!", "error", true );
		}
	};
	
	this.onAddrFieldOutFocus = function( _field ) {
		if( _field.val() != "" ) {			
			if( rcControllerObj.helper.isValidAddress( _field.val() ) ) {
				var type = _field.attr( "data-type" );
				this.header[ type ][ _field.val() ] = _field.val();
				this.view.addAddress( type, _field.val(), _field.val() );				
			}
			this.composer.currentAddrField.val( "" );
		}
	};
	
	this.addAddress = function( _mitem ) {		
		var type = this.composer.currentAddrField.attr( "data-type" );
		var name = _mitem.attr( "data-name" );
		var email = _mitem.attr( "data-email" );
		this.header[ type ][ email ] = name;
		this.view.addAddress( type, name, email );
		this.composer.currentAddrField.val( "" );
	};
	
	this.removeAddress = function( _mitem ) {
		var type = _mitem.parent().attr( "data-type" );
		delete this.header[ type ][ _mitem.parent().attr( "data-email" ) ];
		this.view.adjustFrameHeight();
	};

	/* Retriev the composed mail as an Object */
	this.getComposed = function() {	
		this.message.html = this.view.ckeditor.getData();	
		if( this.mode == "new" ) {
			this.message.html = this.view.ckeditor.getData();	
		} else {			
			if( this.mode == "forward" ) {				
				this.message.html = this.view.ckeditor.getData() + ( '<hr/>---------- Forwarded message ----------<br/>' + this.view.replyMeta.html() + '<hr/>' ) + this.view.replyBody;
			} else {
				this.message.html = this.view.ckeditor.getData() + ( '<hr/>' + this.view.replyMeta.html() + '<hr/>' ) + this.view.replyBody;
			}			
		}
		return {
			cname: this.name,
			header: this.header,
			body: this.message,
			attachments: []
		}
	};

};