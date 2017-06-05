var rcHelper = function( _rc ) {
	
	this.controller = _rc;
	
	this.parseUnixDate = function( _ut_str ) {
		return moment.unix( _ut_str );
	}
	
	this.parseFormatUnixDate = function( _ut_str, _format ) {
		var format = "DD MMM YYYY";
		if( typeof _format != 'undefined' ) {
			format = _format
		}
		return moment.unix( _ut_str ).format( format );
	};
	
	/**
	 * @_d1 	: Actual Date
	 * @_d2		: Date that has to be Compared
	 * @_type	: Could be 'year', 'month', 'day'
	 */
	this.isSame = function( _d1, _d2, _type ) {
		return moment( _d1 ).isSame( _d2, _type );
	}
	
	this.getThisWeekStartEnd = function() {
		return {
			start : moment().startOf('week').toDate(),
			end : moment().endOf('week').toDate()
		}
	};
	
	this.getLastWeekStartEnd = function() {
		var temp1 = this.getThisWeekStartEnd();				
		var temp2 = this.getThisWeekStartEnd();
		return {
			start : new Date( temp1.start.setDate( temp1.start.getDate() - 7 ) ),
			end : new Date( temp2.start.setDate( temp2.start.getDate() - 1 ) )
		}
	};
	
	/**
	 * Find out that the given date is today
	 */
	this.isTodayDate = function( _date ) {
		return moment.unix( _date ).isSame(moment( new Date() ), 'day');
	};
	
	/**
	 * Find out that the given date is today
	 */
	this.isFromCurrentWeek = function( _date ) {
		return moment.unix( _date ).isSame(moment( new Date() ), 'week');
	};
	
	/**
	 * Returns the time difference in hour for the given 'date'
	 * compared with current time
	 */
	this.getHoursDifference = function( _date ) {
		var now = moment( new Date() );
		var endDate = moment.unix( _date );
		var duration = moment.duration( now.diff( endDate ) );
		return duration.asHours();
	};
	
	/**
	 * Returns the time difference in hour for the given 'date'
	 * compared with current time
	 */
	this.getDaysDifference = function( _date ) {
		var now = moment( new Date() );
		var endDate = moment.unix( _date );
		var duration = moment.duration( now.diff( endDate ) );
		return duration.asDays();
	};	
	
	/* Check for any other ajax request is in queue
	 * before initiating a new synchronous request */
	this.isSafeToFetch = function() {
		var res = true;
		var lKeys = Object.keys( this.controller.lister.binders );
		for( var i = 0; i < lKeys.length; i++ ) {
			if( this.controller.lister.binders[ lKeys[i] ].waitingForMeta ) {
				res = false;
				break;
			}
		}
		return res;
	};
	
	/**
	 * convert string to url slug */
	this.sanitizeStr = function( str ) {
		return str.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
	};
	
	/**
	 * 
	 */
	this.escapeHtml = function ( _str ) {                                        
		return _str.replace(/>/g,'&gt;').
				    replace(/</g,'&lt;').
				    replace(/"/g,'');
	};
	
	/**
	 * Returns the key code from the event object ( for browser compatibility ) */
	this.getKeyCode = function( _e ) {		
		_e = ( _e ) ? _e : window.event;
		return ( _e.which ) ? _e.which : _e.keyCode;		
	};
	
	/* Render the full header meta, contains all recipient details ( Usually it will be hidden ) */
	this.getFullHeaderMetaWidget = function( _header, _dPos) {
		var html = "";
		var delimiterPos = ( typeof _dPos != 'undefined' ) ? _dPos : "left`";
		
		/* Load header section */
		if( _header ) {
			var str = "";
			/* Render From address */
			if( _header.from_address && _header.from_address.trim() != "" ) {
				str = _header.from_address;
			} else {
				if( _header.from ) {
					for( var i = 0; i < _header.from.length; i++ ) {
						str += _header.from[i].mailbox + "@" + _header.from[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				html += this.wrapWithTable( "from", str, delimiterPos );
			}
			str = "";
			/* Render To address */
			if( _header.to_address && _header.to_address.trim() != "" ) {
				str = _header.to_address;
			} else {
				if( _header.to ) {
					for( var i = 0; i < _header.to.length; i++ ) {
						str += _header.to[i].mailbox + "@" + _header.to[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				html += this.wrapWithTable( "to", str, delimiterPos );
			}
			str = "";
			/* Render CC address */
			if( _header.cc_address && _header.cc_address.trim() != "" ) {
				str = _header.cc_address;
			} else {
				if( _header.cc ) {
					for( var i = 0; i < _header.cc.length; i++ ) {
						str += _header.cc[i].mailbox + "@" + _header.cc[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				html += this.wrapWithTable( "cc", str, delimiterPos );
			}
			str = "";
			/* Render BCC address */
			if( _header.bcc_address && _header.bcc_address.trim() != "" ) {
				str = _header.bcc_address;
			} else {
				if( _header.bcc ) {
					for( var i = 0; i < _header.bcc.length; i++ ) {
						str += _header.bcc[i].mailbox + "@" + _header.bcc[i].mailbox +", ";
					}
					str = str.substr( 0, str.length - 2 );
				}
			}
			if( str != "" ) {
				html += this.wrapWithTable( "bcc", str, delimiterPos );
			}
			/* Render Mail date */
			if( _header.mail_date ) {
				html += this.wrapWithTable( "date", _header.mail_date, delimiterPos );
			}
			/* Render subject */
			if( _header.subject ) {		
				html += this.wrapWithTable( "subject",  _header.subject, delimiterPos );
			}
				
		}			
		
		return html;
	};
	
	this.getSearchOption = function() {
		return $( "input:radio[name='rc-mail-search-type-item']:checked" ).val();
	};
	
	this.getFilterOption = function() {
		return $( "a.rc-folder-filter-btn.selected" ).attr( "data-filter" );
	};
	
	this.getSortOption = function() {
		return $( "a.rc-folder-sort-btn.selected" ).attr( "data-sort" );
	};
	
	this.wrapWithTable = function( _label, _val, _dPos ) {
		var html = '<table><tr>';
		var leftD = ( _dPos == "left" ) ? " : " : "";
		var rightD = ( _dPos == "right" ) ? " : " : "";
		
		if( _dPos == "" ) {
			leftD = "";
			rightD = "";
		}
		
		html += '<td>'+ _label + leftD +'</td>';
		html += '<td>'+ rightD + rcControllerObj.helper.escapeHtml( _val ) +'</td>';
		html += '</tr></table>';
		return html;
	};
	
	this.isValidAddress = function( _email ) {
		return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test( _email );	
	}
	
	/**
	 * Ajax handler for doing background task like Sync
	 * Use this instead of 'dock' from 'controller', which is a primary Ajax hanlder 
	 * 'dock' is centralized ajax handler, so it prevent concurrent access.
	 * but 'syncDocker' allows concurrency, so use this whenever you need parrallel access  
	 */
	this.syncDock = function( _request, _scontext ) {
		var me = this;
		var res = null; 
		$.ajax({  
			type       : "POST",  
			data       : { rc_param : JSON.stringify( _request )},  
			dataType   : "json",  
			url        : docker,  
			beforeSend : function(){},
			success    : function(data) {
				res = me.controller.prepareResponse( data.status, data.message, data.payload );
				/* Check for session expired error */
				if( ! res.status && res.message == "login" ) {
					document.location.href = "";
				} else {
					rcControllerObj[ _scontext ].handleResponse( _request, res );
				}			
			},
			error      : function(jqXHR, textStatus, errorThrown) {                    
				rcControllerObj[ _scontext ].handleResponse( _request, res );
			}  
		});		
	};
	
};

/**
 * @author  	: Saravana Kumar K
 * @author url  : http://iamsark.com
 * @purpose 	: Dialog Module - Used to show alerts & confirmation boxes for the users    	 
 * @version		: 1.0.0
 */
var rcDialogBox = function( _rc ) {
	
	/* Controller object reference */
	this.controller = _rc;
	/* Holds the current dialog object */
	this.window = null;
	/* Title for confirm box */
	this.title = "";
	/* Message to be displayed */
	this.message = "";
	/**/
	this.fields = [];
	/* Buttons to be placed at the bottom */
	this.buttons = [];
	/* Size of the dialog box ( could be 'tiny', 'small', 'medium' or 'large' ) */
	this.size = "small";
	/* Type of this dialog ( could be 'alert' or 'confirm' ) */
	this.type = "alert";
	/* Custom string to identify the purpose of this dialog */
	this.task = "";
	/* Holds the currently focused button's reference */
	this.currentButton = null;
	/**/
	this.defaultBtn = null;
	/* Used as a temp storage
	 * Mainly used by context objects, before showing the dialog they can store some values here
	 * and can be retrived on 'onUserConfirmed' callback - it could be very usefull for many scenario */
	this.store = null;
	
	this.alert = function( _title, _message, _size ) {
		this.type = "alert";
		this.title = _title;
		this.message = _message;
		this.size = _size;
		this.renderDialogBox();
	};
	
	this.confirm = function( _title, _message, _task, _buttons, _default_focus, _size ) {
		this.type = "confirm";
		this.title = _title;
		this.message = _message;
		this.task = _task;
		this.buttons = _buttons;
		this.defaultBtn = _default_focus;
		this.size = _size;
		this.renderDialogBox();
	};
	
	this.popup = function( _title, _message, _task, _fields, _buttons, _default_focus, _size ) {
		this.type = "popup";
		this.title = _title;
		this.message = "";
		this.task = _task;
		this.fields = _fields;
		this.buttons = _buttons;
		this.defaultBtn = _default_focus;
		this.size = _size;
		this.renderDialogBox();
	};
	
	this.renderDialogBox = function() {
		
		var html = '<div class="rc-dialog-ghost-back">';		
		html += '<div class="rc-dialog rc-dialog-zoom-effect-small rc-dialog-'+ this.type +' rc-dialog-'+ this.size +'">';
		
		/* Title section */
		html += '<div class="rc-dialog-title">';
		if( this.type == "confirm" ) {
			html += '<span><i class="fa fa-bell-o"></i> '+ this.title +'</span>';
		} else if( this.type == "alert" ) {
			html += '<span><i class="fa fa-info"></i> '+ this.title +'</span>';
		} else {
			html += '<span><i class="fa fa-window-maximize"></i> '+ this.title +'</span>';
		}		
		html += '</div>';
		
		if( this.type == "confirm" ||  this.type == "alert" ) {
			/* Message section */
			html += '<div class="rc-dialog-content">';
			html += this.message;
			html += '</div>';
		} else {
			/* This is for the custom popup */
			for( var i = 0; i < this.fields.length; i++ ) {
				html += '<table class="rc-popup-field-table">';
				html += '<tr>';
				html += '<td>';
				html += '<label class="rc-popup-field-label">'+ this.fields[i].label +'</label>';
				html += '</td>';
				html += '<td>';
				html += this.getField( this.fields[i], ( i + 1 ) );
				html += '</td>';
				html += '</tr>';
				html += '</table>';
			}
		}		
		
		/* Buttons section */		
		html += '<div class="rc-dialog-buttons">';		
		if( this.type == "confirm" || this.type == "popup" ) {
			for( var i = 0; i < this.buttons.length; i++ ) {
				html += '<button class="rc-btn rc-dialog-btn" tabindex="'+ ( this.fields.length + ( i + 1 ) ) +'" data-task="'+ this.task +'" data-action="'+ this.buttons[i].action +'">'+ this.buttons[i].title +'</button>';
			}	
		} else {
			html += '<button class="rc-btn rc-dialog-btn" tabindex="-1" data-task="ALERT" data-action="OK">Close</button>';
		}		
		html += '</div>';
		
		/* End of dialog wrapper */
		html += '</div>';
		/* End of dialog ghost back */
		html += '</div>';
		
		this.window = $( html );
		/* Attach to the body */
		$( 'body' ).append( this.window );		
		/* Store 'this' reference so that we can refer it in call backs */
		var me = this;
		/* Time out for Zoom out the dialog frame */
		setTimeout( function() {
			me.window.find( "div.rc-dialog" ).removeClass( "rc-dialog-zoom-effect-small" ).addClass( "rc-dialog-zoom-effect-large" );
		}, 10 );
		/* Vertical align middle - time out needed for Dom to settled down */
		setTimeout( function() {
			var dHeight = me.window.find( "div.rc-dialog" ).height(),
			wHeight = me.window.height(),			
			mTop = ( wHeight / 2 ) - ( dHeight );
			me.window.find( "div.rc-dialog" ).css( "opacity", "1" );
			me.window.find( "div.rc-dialog" ).css( "margin-top", mTop + "px" );
		}, 100 );
		/* Focusing appropriate field */
		setTimeout( function() {			
			var focused = false;
			me.window.find( "div.rc-dialog" ).removeClass( "rc-dialog-zoom-effect-large" );			
			if( me.type == "popup" ) {
				for( var i = 0; i < me.fields.length; i++ ) {
					if( ( typeof me.fields[i]["focus"] != "undefined" ) && me.fields[i]["focus"] ) {
						$( "#" + me.fields[i]["name"] ).trigger( "focus" );
						focused = true;
						break;
					}
				}
			}			
			if( me.type == "confirm" && ( ! focused ) ) {
				if( this.defaultBtn && this.defaultBtn != "" ) {
					me.window.find( "button[data-action="+ this.defaultBtn +"]" ).trigger( "focus" );
				} else {
					me.window.find( "button.rc-dialog-btn:first-child" ).trigger( "focus" );
				}				
				me.currentButton = me.window.find( "button.rc-dialog-btn:first-child" ); 
			} else if( me.type == "alert" ) {
				me.window.find( "button.rc-dialog-btn" ).trigger( "focus" );
				me.currentButton = me.window.find( "button.rc-dialog-btn" ); 
			}
		}, 200 );
	};
	
	this.getField = function( _field, _tIndex ) {
		var html = '',
		checked = '',
		selected = '',
		readonly = '',
		charlength = '',
		attributes = '',
		text_align = '',
		precision = 0,
		placeholder = '';
		
		/* Determine whether this field is readonly */
		if( _field.readonly ) {
			readonly = 'readonly';
		}
		/* Determine the field's align */
		text_align = ( typeof _field.align != 'undefined' ) ? _field.align : "left";
		/* Determine the placeholder text */
		if( typeof _field.placeholder != 'undefined' ) {
			placeholder = _field.placeholder;
		}
		
		for( var i = 0; _field.attributes.length; i++ ) {
			attributes += _field.attributes[i].key + '="'+ _field.attributes[i].value +'"';
		}
		
		if( _field.char_length != -1 ) {
			charlength = 'maxlength="'+ _field.char_length +'"';
		}
		
		if( _field.type == "text" ) {
			html = '<input type="text" id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +' value="'+ _field.value +'" placeholder="'+ placeholder +'" '+ charlength +' style="text-align:'+ text_align +'" />';
		} else if( _field.type == "email" ) {
			html = '<input type="email" id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +' value="'+ _field.value +'" placeholder="'+ placeholder +'" '+ charlength +' style="text-align:'+ text_align +'" />';
		} else if( _field.type == "number" ) {
			html = '<input type="number" id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +' value="'+ _field.value +'" placeholder="'+ placeholder +'" '+ charlength +' style="text-align:'+ text_align +'" />';
		} else if( _field.type == "password" ) {
			html = '<input type="password" id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +' value="'+ _field.value +'" placeholder="'+ placeholder +'" '+ charlength +' style="text-align:'+ text_align +'" />';
		} else if( _field.type == "checkbox" ) {
			if( typeof _field.options == 'undefined' ) {
				if( _field.checked ) {
					checked = 'checked'
				}
				html = '<input type="checkbox" id="' + _field.name +'" name="'+ _field.name +'" '+ checked +' tabindex="'+ ( _tIndex + i ) +'" '+ attributes +' class="rc-mail-popup-field '+ _field.classes +'" value="'+ _field.value +'"/>';
			} else {
				html = '<ul class="rc-mail-popup-option-wrapper">';
				for( var i = 0; i < field.options.length; i++ ) {
					checked = '';
					if( field.options[i].checked ) {
						checked = 'checked'
					}
					html += '<li><label>'+ _field.options[i].key +' <input type="checkbox" name="'+ _field.name +'[]" '+ checked +' tabindex="'+ ( _tIndex + i ) +'" '+ attributes +' class="rc-mail-popup-field '+ _field.classes +'" value="'+ _field.options[i].value +'"/></label></li>';
				}
				html += '</ul>';
			}				
		} else if( _field.type == "radio" ) {
			if( ( _field.options && _field.options.length == 1 ) && ( typeof _field.options == 'undefined' ) ) {
				if( _field.checked ) {
					checked = 'checked'
				}
				html = '<input type="radio" id="'+ _field.name +'" name="'+ _field.name +'" '+ checked +' tabindex="'+ ( _tIndex + i ) +'" '+ attributes +' class="rc-mail-popup-field '+ _field.classes +'" value="'+ _field.value +'"/>';
			} else {
				html = '<ul class="rc-mail-popup-option-wrapper">';
				for( var i = 0; i < _field.options.length; i++ ) {
					checked = '';
					if( _field.options[0].value == _field.value ) {
						checked = 'checked'
					}
					html += '<li><label>'+ _field.options[i].key +' <input type="radio" name="'+ _field.name +'" tabindex="'+ ( _tIndex + i ) +'" '+ attributes +' class="rc-mail-popup-field '+ _field.classes +'" value="'+ _field.options[i].value +'"/></label></li>';
				}
				html += '</ul>';
			}			
		} else if( _field.type == "select" ) {
			html = '<select id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +'">';
			for( var i = 0; i < _field.options.length; i++ ) {
				selected = '';
				if( _field.options[i].value == _field.value ) {
					selected = 'selected="selected"';
				}
				html += '<option value="'+ _field.options[i].key +'" '+ selected +'>'+ _field.options[i].value +'</option>';
			}
			html += '</select>';
		} else if( _field.type == "textarea" ) {
			html = '<textarea id="'+ _field.name +'" name="'+ _field.name +'" tabindex="'+ _tIndex +'" class="rc-mail-popup-field '+ _field.classes +'" '+ readonly +' '+ attributes +' placeholder="'+ placeholder +'" '+ charlength +' style="text-align:'+ text_align +'">'+ _field.value +'</textarea>';
		} else if( _field.type == "label" ) {
			html = '<label data-target-type="field" data-field-type="'+ _field.type +'" data-field="'+ _field.name +'" id="'+ namespace + _field.name +'" name="'+ namespace + _field.name +'" tabindex="'+ _field.tabindex +'" class="ikea-popup-field '+ _field.classes +'" '+ attributes +' data-message="'+ _field.messag +'" style="text-align:'+ text_align +'">'+ _field.value +'</label>';
		} else {
			return "";
		}
		
		return html;		
	};
	
	this.closeDialog = function() {
		if( this.window ) {
			var me = this;
			this.window.fadeOut(function(){
				me.window.remove();
				me.window = null;
			});
		}		
	};
	
	this.isOn = function() {
		return ( this.window !== null ) ? true : false;
	};
};

var rcNotify = function() {
	/* Holds the Notification UI object ref */
	this.window = null;	
	/**
	 * Default Type
	 * Also it could be 'success', 'warning', 'error' or 'info'
	 */
	this.type = "info";	
	/* Default Notification Stay duration in Milli second */
	this.duration = 5000;
	/* Message that is being displayed */
	this.message = "";	
	/* Whether to close the notificaion after some period */
	this.autoClose = true;
	/* Used by the docker, whether to show the notification while Ajax */
	this.silent = false;
	/**
	 * @_msg		: Message that has to be displayed
	 * @_type		: Type of the message
	 * @_stay		: Auto close or not
	 * @_duration	: For how long the notification has to be shown ( Only of @_stay = false )
	 */
	this.show = function( _msg, _type, _stay, _duration ) {
		var me = this;
		this.message = _msg;
		var type = ( typeof _type != 'undefined' ) ? _type : this.type;
		var autoClose = ( typeof _stay != 'undefined' ) ? _stay : this.autoClose; 
		var duration = ( typeof _duration != 'undefined' ) ? _duration : this.duration; 
		if( ! this.isShown() ) {
			this.window = $( '<div class="rc-user-notification rc-user-'+ type +'-notify">'+ this.message +'</div>' );
			$( "body" ).append( this.window );
		} else {
			/* This means the notification is already displayed */
			this.window.html( this.message );
			this.window.attr( "class", "" ).addClass( "rc-user-notification rc-user-"+ type +"-notify" );
		}		
		/* Center align the notification */
		var center = $( window ).width() / 2;	
		this.window.css( "left", ( center - this.window.width() / 2 ) +"px" );
		/* Remove the notification after the duration */
		if( autoClose ) {
			setTimeout(function(){
				me.remove();
			}, duration );
		}		
	};
	
	this.remove = function() {
		if( this.isShown() ) {
			this.window.remove();
			this.window = null;
		}
	};
	
	this.isShown = function() {		
		return ( this.window != null ) ? true : false;		
	}
	
}