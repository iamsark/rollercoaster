var rcComposerView = function( _binder, _container ) {
	
	this.binder = _binder;
	
	this.container = _container;
	/**/
	this.tabHeader = null;
	/**/
	this.tabContent = null;
	/* CKEditor object */
	this.ckeditor = null;
	/* Used to load the mail body which is being replied */
	this.htmlFrame = null;
	/**/
	this.plainFrame = null;
	/* Holds the from address container */
	this.from = null;
	/* Holds the to address container */
	this.to = null;
	/* Holds the cc address container */
	this.cc = null;
	/* Holds the bcc address container */
	this.bcc = null;
	/**/
	this.subject = null;
	/**/
	this.body = null;
	/**/
	this.noSuggestion = false;
	/* Holds the ref of meta section ( for reply, reply-all & forward modes ) */
	this.replyMeta = null;
	/**/
	this.replyBody = "";
	
	this.initView = function() {
		var me = this;
		var textAreaName = "rc-composer-"+ this.binder.name +"-"+ this.binder.mode +"-body-text";
		this.renderComposerSkeletton();	
		this.to.trigger( "focus" );
		/* Calculate height for Message Editor */
		setTimeout( function() {
			var config = {};
			config[ "removePlugins" ] = "elementspath";	
			config[ "resize_enabled" ] = false;
			config[ "extraPlugins" ] = "colorbutton";
			config[ "placeholder" ] = "Type your message here ...";
			
			config[ "toolbar" ] = [
				{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
				{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
				{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Scayt' ] },
				{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
				{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
				{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },				
				{ name: 'styles', items: [ 'Styles', 'Format' ] },
				{ name: 'colors' },
				{ name: 'tools', items: [ 'Maximize' ] }
			];
			if( me.binder.mode == "new" ) { 
				config[ "height" ] = $( "#rc-mail-viewer-section" ).height() - ( me.tabContent.find( "div.rc-mail-composer-header" ).height() + me.tabContent.find( "div.rc-mail-composer-footer" ).height() ) - 107;					
			} else {
				config[ "height" ] = 200;
			}
			me.ckeditor = CKEDITOR.replace( textAreaName, config );
		}, 250 );		
	};
	
	this.renderComposerSkeletton = function() {
		
		this.tabHeader = $( '<button class="rc-tab-btn rc-mail-composer-tab-btn selected" data-folder="'+ this.binder.fname +'" data-binder="'+ this.binder.name +'" data-uid="'+ this.binder.uid +'" data-target="rc-composer-'+ this.binder.name  +'-'+ this.binder.mode + '-view" title="'+ this.binder.title +'">'+ this.binder.title +' <a href="'+ this.binder.name +'" class="rc-mail-composer-close-btn"><i class="fa fa-times"></i></a></button>' );
		
		this.tabContent = $( '<div id="rc-composer-'+ this.binder.name +'-'+ this.binder.mode +'-view" class="rc-mail-message-composer" data-uid="'+ this.binder.uid +'" ></div>' );
		
		var skeletton = '';	
		skeletton += '<div class="rc-mail-composer-header">';
		
		skeletton += '<div class="rc-mail-composer-form-row rc-composer-to-container">';
		skeletton += '<input type="text" data-type="to" class="rc-mail-composer-form-field rc-mail-composer-to rc-composer-addr-field" placeholder="To" />';
		skeletton += '<div class="rc-mail-composer-additional-field-bar">';
		skeletton += '<button class="rc-mail-composer-cc-btn"><i class="fa fa-plus"></i> CC</button>';
		skeletton += '<button class="rc-mail-composer-bcc-btn"><i class="fa fa-plus"></i> BCC</button>';
		skeletton += '</div>';
		skeletton += '</div>';
		
		skeletton += '<div class="rc-mail-composer-form-row rc-composer-cc-container" style="display: none">';
		skeletton += '<input type="text" data-type="cc" class="rc-mail-composer-form-field rc-mail-composer-cc rc-composer-addr-field" placeholder="CC" />';
		skeletton += '</div>';
		
		skeletton += '<div class="rc-mail-composer-form-row rc-composer-bcc-container" style="display: none">';
		skeletton += '<input type="text" data-type="bcc" class="rc-mail-composer-form-field rc-mail-composer-bcc rc-composer-addr-field" placeholder="BCC" />';
		skeletton += '</div>';
		
		skeletton += '<div class="rc-mail-composer-form-row rc-mail-composer-subject-row">';
		skeletton += '<input type="text" class="rc-mail-composer-form-field rc-mail-composer-subject" placeholder="Subject" />';
		skeletton += '</div>';
		
		skeletton += '</div>';
		
		skeletton += '<div class="rc-mail-composer-content">';
		skeletton += '<textarea name="rc-composer-'+ this.binder.name +'-'+ this.binder.mode +'-body-text"></textarea>';		
		skeletton += '</div>';
		
		skeletton += '<div class="rc-mail-composer-footer rc-mail-composer-footer-'+ this.binder.mode +'">';
		skeletton += '<button class="rc-btn rc-btn-primary rc-mail-composer-send-btn" data-binder="'+ this.binder.name +'"><i class="fa fa-send"></i> Send</button>';
		skeletton += '<button class="rc-btn rc-btn-secondary rc-mail-composer-attach-btn" data-binder="'+ this.binder.name +'"><i class="fa fa-paperclip"></i></button>';				
		skeletton += '<button class="rc-btn rc-brn-secondary rc-mail-composer-cancel-btn" data-binder="'+ this.binder.name +'"><i class="fa fa-trash"></i></button>';
		skeletton += '</div>';
		
		if( this.binder.mode != "new" ) {
			this.replyMeta = $( '<div id="rc-mail-'+ this.binder.mode +'-'+ this.binder.name +'-meta-section" class="rc-mail-composer-reply-meta-section" style="margin: 15px; padding: 20px; background: #f5f5f5; font-size: 13px;"></div>' );
			this.htmlFrame = $( '<iframe name="rc-mail-'+ this.binder.mode +'-'+ this.binder.name +'-message-body" id="rc-mail-'+ this.binder.name +'-message-body" class="rc-mail-composer-reply-message-body-frame" style="height: 0px;" sandbox="allow-same-origin"></iframe>' );
		}
			
		skeletton = $( skeletton ); 
		
		//this.from = skeletton.find( "div.rc-composer-from-container" );		
		this.to = skeletton.find( "div.rc-composer-to-container" );
		this.cc = skeletton.find( "div.rc-composer-cc-container" );
		this.bcc = skeletton.find( "div.rc-composer-bcc-container" );
		this.subject = skeletton.find( "input.rc-mail-composer-subject" );
		this.body = skeletton.find( "textarea[name='rc-mail-"+ this.binder.name +"-body-text']" );
		
		this.tabContent.append( skeletton );
		
		if( this.replyMeta ) {
			this.tabContent.append( this.replyMeta );
		}		
		if( this.htmlFrame ) {
			this.tabContent.append( this.htmlFrame );
		}
		
		/* Before append set the width for the tab header */
		var width = 0;
		var totalTabs = Object.keys( rcControllerObj.composer.binders ).length;
		var totalWidth = parseInt( this.container.find( "div.rc-tab" ).width() );
		
		if( totalTabs > 1 ) {
			width = this.container.find( "div.rc-tab" ).find( "button:first-child" ).outerWidth( true );
			if( ( totalTabs * width ) >= totalWidth ) {
				width = Math.floor( totalWidth / totalTabs );
				this.container.find( "div.rc-tab" ).find( "button" ).innerWidth( width - 2 );
			}			
			this.tabHeader.innerWidth( width - 2 );
		}
		
		this.container.find( "div.rc-tab" ).append( this.tabHeader );
		this.container.find( "div.rc-tab-content" ).append( this.tabContent );
		
	};
	
	/* This will be used only for 'reply', 'reply-all' & 'forward' Modes */
	this.prepareComposer = function( _data ) {		
		/* Load header section */
		if( _data.header ) {
			/* Render original subject */
			if( _data.header.subject ) {		
				this.replyMeta.append( $( this.metaHeaderWidget( "Subject", _data.header.subject ) ) );
			}
			/* Render original Mail date */
			if( _data.header.mail_date ) {
				this.replyMeta.append( $( this.metaHeaderWidget( "Sent Date", _data.header.mail_date ) ) );
			}			
			
			var str = "";
			/* Render original From address */
			if( _data.header.from_address && _data.header.from_address.trim() != "" ) {
				str = _data.header.from_address;
			} else {
				if( _data.header.from ) {
					for( var i = 0; i < _data.header.from.length; i++ ) {
						str += _data.header.from[i].mailbox + "@" + _data.header.from[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				this.replyMeta.append( $( this.metaHeaderWidget( "From", str ) ) );
			}
			str = "";
			/* Render original To address */
			if( _data.header.to_address && _data.header.to_address.trim() != "" ) {
				str = _data.header.to_address;
			} else {
				if( _data.header.to ) {
					for( var i = 0; i < _data.header.to.length; i++ ) {
						str += _data.header.to[i].mailbox + "@" + _data.header.to[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				this.replyMeta.append( $( this.metaHeaderWidget( "To", str ) ) );
			}
			str = "";
			/* Render original CC address */
			if( _data.header.cc_address && _data.header.cc_address.trim() != "" ) {
				str = _data.header.cc_address;
			} else {
				if( _data.header.cc ) {
					for( var i = 0; i < _data.header.cc.length; i++ ) {
						str += _data.header.cc[i].mailbox + "@" + _data.header.cc[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				this.replyMeta.append( $( this.metaHeaderWidget( "CC", str ) ) );
			}
			str = "";
			/* Render original BCC address */
			if( _data.header.bcc_address && _data.header.bcc_address.trim() != "" ) {
				str = _data.header.bcc_address;
			} else {
				if( _data.header.bcc ) {
					for( var i = 0; i < _data.header.bcc.length; i++ ) {
						str += _data.header.bcc[i].mailbox + "@" + _data.header.bcc[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				this.replyMeta.append( $( this.metaHeaderWidget( "BCC", str ) ) );
			}
		}
		
		if( _data.html_body && _data.html_body.trim() != "" ) {
			var me = this;
			var document = this.htmlFrame[0].contentDocument || this.htmlFrame[0].contentWindow.document;
			document.open();
			document.write( _data.html_body );
			document.close();
			this.htmlFrame.ready(function(){
				me.adjustFrameHeight();
			});
			/* Store reply mail */
			this.replyBody = _data.html_body;
		} else {
			this.htmlFrame.hide();
			this.plainFrame = $( '<div class="rc-composer-plain-text-body"></div>' );
			this.htmlFrame.before( this.plainFrame );
			this.plainFrame.html( _data.text_body );
			this.adjustFrameHeight();
			/* Store reply mail */
			this.replyBody = _data.text_body;
		}		
	};
	
	this.toggleAdditionalField = function( _type ) {		
		this[ _type ].toggle();
		if( this[ _type ].is( ":visible" ) ) {
			this[ _type ].find( "input" ).trigger( "focus" );
		} else {
			this.to.find( "input" ).trigger( "focus" );
		}
		this.adjustFrameHeight();
	};
	
	this.addAddress = function( _type, _name, _email ) {
		var me = this;
		var html = '';
		var label = _name;
		if( label == "" ) {
			label = _email;
		}
		html = '<span class="rc-composer-addr-label" data-type="'+ _type +'" data-email="'+ _email +'" title="'+ _email +'">'+ label +' <a href="#" class="rc-composer-addr-rm-btn"><i class="fa fa-times"></i></a></span>';
		if( _type == "to" ) {
			this.to.find( "input.rc-composer-addr-field" ).before( html );
		} else if( _type == "cc" ) {
			this.cc.find( "input.rc-composer-addr-field" ).before( html );
		} else {
			this.bcc.find( "input.rc-composer-addr-field" ).before( html );
		}
		setTimeout( function(){
			me.adjustFrameHeight();
		}, 500 );		
	};
	
	this.showSuggestion = function( _res ) {
		if( _res.status && ! this.noSuggestion ) {
			var us_list_container = this.binder.composer.currentAddrField.parent().find( "div.rc-composer-addr-list" );
			if( us_list_container.length == 0 ) {
				us_list_container = $( '<div class="rc-composer-addr-list"></div>' );
				this.binder.composer.currentAddrField.parent().append( us_list_container );
			} else {
				us_list_container.css( "display", "table" );
			}
			var name = "";	
			var flag = true;
			var keys = Object.keys( _res.data );
			var type = this.binder.composer.currentAddrField.attr( "data-type" );
			var emails = [];
			
			if( ! $.isEmptyObject( this.binder.header ) && ! $.isEmptyObject( this.binder.header[ type ] ) ) {
				emails = Object.keys( this.binder.header[ type ] );
			}
			/* CLear the suggestion container */
			us_list_container.html( "" );			
			for( var i = 0; i < keys.length; i++ ) {
				if( _res.data[ keys[i] ] == "" ) {
					name = keys[i];
				}
				flag = true;
				for( var j = 0; j < emails.length; j++ ) {
					if( emails[j] == keys[i] ) {
						flag = false;				
						break;
					}
				}
				if( flag ) {
					us_list_container.append( $( '<a href="#" class="rc-us-list-item" data-email="'+ keys[i] +'" data-name="'+ name +'">'+ name +'</a>' ) );
				}				
			}			
		}
	};	
	
	this.metaHeaderWidget = function( _label, _val ) {
		var html = '<table style="width: 100%;"><tr>';
		html += '<td style="width: 100px;font-weight: bold; padding: 2px 0px;">'+ _label +'</td>';
		html += '<td style="padding: 2px 0px;">'+ rcControllerObj.helper.escapeHtml( _val ) +'</td>';
		html += '</tr></table>';
		return html;
	};
	
	this.adjustFrameHeight = function() {
		if( this.binder.mode == "new" ) {
			/* Calculate height for Message Editor */
			var editorHeight = $( "#rc-mail-viewer-section" ).height() - ( this.container.find( "div.rc-mail-composer-header" ).height() + this.container.find( "div.rc-mail-composer-footer" ).height() ) - 63;
			console.log( "Editor Height : " + editorHeight );
			this.ckeditor.resize( "100%", editorHeight );			
		} else {  			
			var parentCHeight = $( "#rc-mail-viewer-section" ).outerHeight();
			var tabHeight = this.tabHeader.outerHeight();
			var composerHeaderHeight = this.tabContent.find( "div.rc-mail-composer-header" ).outerHeight();
			var composerContentHeight = this.tabContent.find( "div.rc-mail-composer-content" ).outerHeight();
			var composerFooterHeight = this.tabContent.find( "div.rc-mail-composer-footer" ).outerHeight();
			var metaHeight = this.tabContent.find( "div.rc-mail-composer-reply-meta-section" ).outerHeight();
			var containerHeight = parentCHeight - ( tabHeight + composerHeaderHeight + composerContentHeight + composerFooterHeight + metaHeight + 50 );
			this.htmlFrame.height( containerHeight );
			if( this.plainFrame ) {
				this.plainFrame.height( containerHeight - 30 );
			}
		}		
	};
	
};