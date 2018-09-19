var rcListViewer = function( _binder, _container ) {
	/* Holds the binding object for this lister */	
	this.binder = _binder;
	/* Header listing container ref */
	this.container = _container;
	/* Holds the Mail's Header */
	this.headers = [];
	/* Number of headers per page ( Used for pagination ) */
	this.totalNumberOfPages = this.binder.totalPage;	
	/* How much headers per page - used for Lazy Loading Pagination */
	this.recordsPerPage = 50;	
	/* CUrrent page, which is being viewed */
	this.currentPage = 1;
	/* Holds the starting index of the given page */
	this.startIndex = 0;
	/* Holds the ending index of the given page */
	this.endIndex = this.recordsPerPage;
	/* Holds the last scrolling position, to determine the current scrolling direction */	
	this.lastScrollPos = 0;
	/* Holds the height of a single page */
	this.pageHeight = 1;
	/* Page index which is being viewed currently */
	this.currentVisiblePage = 1;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of Todays ) */
	this.todayLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of Yesterday ) */
	this.yesterdayLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of this Week ) */
	this.thisWeekLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of last Week ) */
	this.lastWeekLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of this Month ) */
	this.thisMonthLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of this Month ) */
	this.lastMonthLabel = false;
	/* Flaq for Today Label ( will be true if the lister started to render the headers of Older than a month ) */
	this.olderLabel = false;
	/* Used to denote whether 'older' rendered or not, if it is then we can skip the check for subsequent header */
	this.olderLabelRendered = false;
	/* Holds the current selected header's uid
	 * When reloading the view - we use this property to reselect the header */
	this.currentSelected = -1;
	/* Group meta - Holds the positions of each date labels */
	this.gMeta = [];
	
	this.loadView = function() {
		this.container.scrollTop( 0 );
		/* Clear the List Container before begin */
		this.container.html( "" );
		this.startIndex = 0;
		this.currentSelected = -1;
		this.endIndex = this.recordsPerPage;
		
		/* Make sure the header list offset not exceeding the total header length */
		if( this.endIndex > this.headers.length ) {
			this.endIndex = this.headers.length;
		}		
		/* Before start to render firsrt prepare the Group ( Date ) Label mata */
		this.prepareGroupLabelMeta();
		
		if( this.headers.length > 0 ) {
			/* Well start the rendering */
			var headerPage = $( '<div class="rc-record-page" data-loaded="yes"></div>' );
			this.container.append( headerPage );
			this.renderHeaders( headerPage );
			this.pageHeight = headerPage.height();
		} else {
			/* Display the empty folder message */
			this.showEmptyFolderMessage();	
		}		
	};
	
	/**
	 * Set the source header list
	 */
	this.setHeaders = function() {
		
		var headers = [];
		this.headers = [];
		var filter = rcControllerObj.helper.getFilterOption();
		
		if( rcControllerObj.lister.searchContext ) {			
			headers = this.binder.searchedHeaders;
		} else {			
			headers = this.binder.headers;				
		}		
		
		if( filter == "all" ) {
			this.headers = headers;
		} else {
			for( var i = 0; i < headers.length; i++ ) {
				if( ! headers[i].seen ) {
					this.headers.push( headers[i] );
				}
			}
		}		
		
		/* Recalculate the total number of page count */
		this.totalNumberOfPages = Math.ceil( this.headers.length / this.recordsPerPage );
		
	};
	
	this.reset = function() {
		this.currentPage = 1;
		this.startIndex = 0;
		this.endIndex = this.recordsPerPage;
		this.headers = [];
		this.lastScrollPos = 0;
		this.pageHeight = 1;
		this.currentVisiblePage = 1;
		this.currentSelected = -1;
		/* Show the empty message - since we have cleared virtually everything */
		this.showEmptyFolderMessage();
	}
	
	this.reloadView = function() {
		var me = this;
		/* reset the Label flags */
		this.todayLabel = false;
		this.yesterdayLabel = false;
		this.thisWeekLabel = false;
		this.lastWeekLabel = false;
		this.thisMonthLabel = false;
		this.lastMonthLabel = false;
		this.olderLabel = false;
		/* Load the latest headers from binder */
		this.setHeaders();
	
		/* Get the current selected header item - so that we can restore it back after reloading */
		var selected = this.container.find( "div.rc-header-block.selected" );
		if( selected.length > 0 ) {
			this.currentSelected = this.container.find( "div.rc-header-block.selected" ).attr( "data-uid" );
		} else {
			this.currentSelected = -1;
		}		
		
		if( this.headers.length < this.startIndex ) {
			/* This means there were a bulk deletion happended
			 * So reset start index to Zero */
			this.startIndex = 0;
			this.endIndex = this.recordsPerPage;
			if( this.endIndex > this.headers.length ) {
				this.endIndex = this.headers.length;
			}
		}		
		if( this.headers.length > 0 ) {
			this.container.find( "div.rc-record-page" ).each(function(){			
				if( $( this ).attr( "data-loaded" ) == "yes" || ( $( this ).attr( "data-loaded" ) == "no" && $( this ).index() == 0 && this.headers.length > 0 ) ) {
					$( this ).attr( "data-loaded", "no" );
					$( this ).height( $( this ).height() );
					$( this ).html( "" );
					me.renderHeaders( $( this ) );
				}
			});
		} else {
			/* Display the empty folder message */
			this.showEmptyFolderMessage();	
			this.currentSelected = -1;
		}
	};
	
	this.loadNextPage = function() {
		
		/* Load the latest headers from binder */
		this.setHeaders();
		
		if( this.headers.length <= this.recordsPerPage || ( this.endIndex == this.headers.length ) ) {
			return;
		}
		
		/* Increment the page number */
		++this.currentPage;  console.log(this.currentPage +" <= "+ this.totalNumberOfPages);
		/* Compare whether it exceeds total number of page */
		if( this.currentPage <= this.totalNumberOfPages ) {				
			/* Update start and end index of records that has to be rendered */
			this.startIndex = ( this.currentPage - 1 ) * this.recordsPerPage;
			this.endIndex = this.startIndex + this.recordsPerPage;
			
			/* Make sure the end index doesn't exceeds the total number of records */
			if( this.endIndex > this.headers.length ) {	
				this.currentPage = this.currentPage - 1;
				this.endIndex = this.headers.length;
			}

			/* Well render the records and append to the main grid table */
			if( this.headers.length > 0 ) {
				var headerPage = $( '<div class="rc-record-page" data-loaded="yes"></div>' );
				this.container.append( headerPage );
				this.renderHeaders( headerPage );
			} else {
				this.showEmptyFolderMessage();
			}							
		}
		
	};
	
	this.clearPageHeaders = function( _dir ) {
		var page;		
		var sIndex = 1;
		var eIndex = 0;
		var labels = null;
		var labelsMeta = [];
		var currentPage = 1;
		this.currentSelected = -1;
		
		if( this.container.scrollTop() > this.pageHeight && this.pageHeight > 0 ) {
			currentPage = Math.ceil( this.container.scrollTop() / this.pageHeight );
		}	
		
		if( currentPage > 2 && _dir == "down" ) {
			sIndex = 1;
			eIndex = ( currentPage - 2 );
		} else if( ( ( currentPage + 1 ) < this.currentPage ) && _dir == "up" ) {
			sIndex = ( currentPage + 1 );
			eIndex = this.currentPage;
		}	
		
		if( sIndex >= 0 && eIndex >= 0 ) {
			for( var i = sIndex; i <= eIndex; i++ ) {
				labels = [];
				page = this.container.find( "div.rc-record-page:nth-child("+ i +")" );
				if( page.attr( "data-loaded" ) == "yes" ) {
					page.height( page.height() );
					page.attr( "data-loaded", "no" );
					/* Before clear, collect the labels if it conatins any */
					labels = page.find( "div.rc-header-list-group-label" ).each(function(){
						labelsMeta.push(  );
					});					
					page.html( "" );
				}
			}
		}
	};
	
	this.fillPageHeaders = function( _dir ) {
		var page;
		var currentPage = 1;
		
		if( this.container.scrollTop() > this.pageHeight ) {
			currentPage = Math.ceil( this.container.scrollTop() / this.pageHeight );
		}	
		
		if( currentPage > 0 ) {
			page = this.container.find( "div.rc-record-page" ).eq( currentPage - 1 );
			if( currentPage != this.totalNumberOfPages && _dir == "down" ) {
				if( page.attr( "data-loaded" ) == "no" ) {
					this.loadPageRecord( currentPage );
				}
				if( page.next().attr( "data-loaded" ) == "no" ) {
					this.loadPageRecord( currentPage + 1 );
				}
			} else if( _dir == "up" ) {
				if( page.attr( "data-loaded" ) == "no" ) {					
					this.loadPageRecord( currentPage );
				}
				if( currentPage > 1 && page.prev().attr( "data-loaded" ) == "no" ) {					
					this.loadPageRecord( currentPage - 1 );
				}
			}
		}	
	};
	
	this.loadPageRecord = function( index ) {
		index = parseInt( index );
		/* Update  the current visible page index */
		this.currentVisiblePage = index;
		var page = this.container.find( "div.rc-record-page" ).eq( index - 1 );
		this.startIndex = ( ( index - 1 ) * this.recordsPerPage );
		this.endIndex = ( index * this.recordsPerPage );
		this.renderHeaders( page );		
	};
	
	this.renderHeaders = function( _parent ) {			
		for( var i = this.startIndex; i < this.endIndex; i++ ) {	
			if( ! this.olderLabelRendered && ( this.binder.fbinder.isInbox || this.binder.fbinder.isSent ) ) {
				for( var j = 0; j < this.gMeta.length; j++ ) {				
					if( this.headers[i].uid == this.gMeta[j].uid ) {
						_parent.append( this.renderGroupLabel( this.gMeta[j].label ) );
						if( this.gMeta[j].label == "Older" ) {
							this.olderLabelRendered = true;
						}
					}
				}		
			}						
			_parent.append( this.renderHeader( this.headers[i] ) );
		}
		/* Mark that the record page is loaded */
		_parent.attr( "data-loaded", "yes" );
		_parent.css( "height", "" );
	};
	
	this.renderHeader = function( _header ) {
		var html = "",
		checked = "",
		isFlagged = "",
		isForwarded = "",
		isAnswered = "",		
		headerDiv = null,
		unseenClass = "";
		
		if( ! _header.seen ) {
			unseenClass = "rc-list-unseen";
		}
		
		if( this.currentSelected == _header.uid ) {
			unseenClass += " selected";
		}
		
		if( _header.checked ) {
			checked = "checked";
		}
		
		html = '<div type="message" data-folder="'+ this.binder.fbinder.fname +'" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-context="rcSingle" data-view="read" class="rc-header-block '+ unseenClass +'">';
		
		if( _header.answered ) {
			isAnswered = 'style="display:inline-block;"';	
		}
		
		if( _header.flagged ) {
			isFlagged = 'style="display:inline-block;"';	
		}	
		
		var formatedDate = "";
		if( rcControllerObj.helper.isFromCurrentWeek( _header.date ) ) {
			if( rcControllerObj.helper.isTodayDate( _header.date ) ) {
				formatedDate = rcControllerObj.helper.parseFormatUnixDate( _header.date, "h:mm A" );
			} else {
				formatedDate = rcControllerObj.helper.parseFormatUnixDate( _header.date, "ddd h:mm A" );
			}
		} else {
			formatedDate = rcControllerObj.helper.parseFormatUnixDate( _header.date, "DD MMM YYYY : hA" );
		}
		
		html += '<table class="rc-mail-list-table">';
		html += '<tbody><tr>';
		
		html += '<td class="check"><input type="checkbox" class="rc-mail-header-check" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" '+ checked +' /></td>';
		
		html += '<td>';		
		html += '<table class="inner">';
		html += '<tbody><tr>';
		html += '<td class="from">'+ _header.from +'</td>';
		html += '<td class="flaqs">';
		html += '<span data-flaq="attachment"><i class="fa fa-paperclip"></i></span>';
		html += '<span '+ isAnswered +' data-flaq="answered"><i class="fa fa-mail-reply"></i></span>';		
		html += '<span data-flaq="answered"><i class="fa fa-mail-forward"></i></span>';
		html += '<span '+ isFlagged +' data-flaq="flagged"><i class="fa fa-flag-o"></i></span>';
		html += '</td>';
		html += '</tr>';
		html += '<tr>';
		html += '<td class="subject">'+ _header.subject +'</td>';
		html += '<td class="date">'+ formatedDate +'</td>';
		html += '</tr></tbody>';
		html += '</table>';
		
		html += '</td>';		
		html += '</tr>';
		html += '</tbody></table>';
		
		html += '<div class="rc-mail-header-quick-tool-bar">';
		html += '<a href="#" title="Reply to Sender" class="rc-mail-header-quick-action" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-folder="'+ this.binder.fname +'" data-action="reply"><i class="fa fa-mail-reply"></i></a>';
		html += '<a href="#" title="Reply to All" class="rc-mail-header-quick-action" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-folder="'+ this.binder.fname +'" data-action="reply-all"><i class="fa fa-mail-reply-all"></i></a>';
		html += '<a href="#" title="Forward this mail" class="rc-mail-header-quick-action" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-folder="'+ this.binder.fname +'" data-action="forward"><i class="fa fa-mail-forward"></i></a>';
        html += '<a href="#" title="Print this mail" class="rc-mail-header-quick-action" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-folder="'+ this.binder.fname +'" data-action="print"><i class="fa fa-print"></i></a>';
        if( ! this.binder.fbinder.isTrash ) {
        	html += '<a href="#" title="Delete this mail" class="rc-mail-header-quick-action" data-msgno="'+ _header.msg_no +'" data-uid="'+ _header.uid +'" data-folder="'+ this.binder.fname +'" data-action="delete"><i class="fa fa-trash"></i></a>';
        }
		html += '</div>';
		
		html += '</div>';
		return html;		
	};
	
	this.prepareGroupLabelMeta = function() {
		
		var mdate = null,
		tday = new Date(),
		yday = new Date(),
		lmonth = new Date();
		
		yday = 	yday.setDate( tday.getDate() - 1 );
		lmonth = lmonth.setMonth( lmonth.getMonth() - 1 );
		
		var thisWeek = rcControllerObj.helper.getThisWeekStartEnd(),
		lastWeek = rcControllerObj.helper.getLastWeekStartEnd();
		
		if( ! this.olderLabel ) {
			/* reset the Label flags */
			this.gMeta = [];
			for( var i = 0; i < this.headers.length; i++ ) {
				mdate = rcControllerObj.helper.parseUnixDate( this.headers[i].date );
				if( rcControllerObj.helper.isSame( tday, mdate, "day" ) ) {
					if( ! this.todayLabel ) {
						this.todayLabel = true;
						this.gMeta.push( { uid: this.headers[i].uid, label: "Today" } );					
					}				
				} else if( rcControllerObj.helper.isSame( yday, mdate, "day" ) ) {
					this.todayLabel = true;
					if( ! this.yesterdayLabel ) {
						this.yesterdayLabel = true;
						this.gMeta.push( { uid: this.headers[i].uid, label: "Yesterday" } );					
					}				
				} else if( moment( mdate ).isBetween( thisWeek.start, thisWeek.end, 'day', '[]' ) ) {
					this.todayLabel = true;
					this.yesterdayLabel = true;
					if( ! this.thisWeekLabel ) {	
						this.thisWeekLabel = true;					
						this.gMeta.push( { uid: this.headers[i].uid, label: "This Week" } );					
					}				
				} else if( rcControllerObj.helper.isSame( tday, mdate, "month" ) ) {
					this.todayLabel = true;
					this.yesterdayLabel = true;				
					if( ! this.thisWeekLabel && moment( mdate ).isBetween( lastWeek.start, lastWeek.end, 'day', '[]' ) ) {
						/* Time to insert 'Last Week' label
						 * Since 'This Week' label is not inserted ( As this is the case if it is the start of the week ) */					
						if( ! this.lastWeekLabel ) {
							this.lastWeekLabel = true;	
							this.gMeta.push( { uid: this.headers[i].uid, label: "Last Week" } );		
						}					
					} else {
						this.thisWeekLabel = true;
						if( ! this.thisMonthLabel ) {
							this.thisMonthLabel = true;
							this.gMeta.push( { uid: this.headers[i].uid, label: "This Month" } );		
						}					
					}			
				} else {				
					this.todayLabel = true;
					this.yesterdayLabel = true;
					this.thisWeekLabel = true;
					if( ! this.thisMonthLabel && rcControllerObj.helper.isSame( lmonth, mdate, "month" ) ) {
						/* Time to insert 'Last Month' label
						 * Since 'This Month' label is not inserted ( As this is the case if it is the start of the month ) */
						if( !this.thisMonthLabel ) {
							this.thisMonthLabel = true;
							this.gMeta.push( { uid: this.headers[i].uid, label: "Last Month" } );		
						}					
					} else {
						this.thisMonthLabel = true;
						if( ! this.olderLabel ) {
							this.olderLabel = true;
							this.gMeta.push( { uid: this.headers[i].uid, label: "Older" } );		
						}					
					}				
				}
			}
		}	
		
	};
	
	this.renderGroupLabel = function( _label ) {
		return $( '<div class="rc-header-list-group-label">'+ _label +'</div>' );
	};
	
	this.showEmptyFolderMessage = function() {
		this.container.html( '<table class="rc-empty-folder-info"><tr><td><h3><i class="fa fa-info-circle"></i> Folder is Empty.!</td></tr></table></h3>' );
	};
	
	this.onHeaderScrolling = function() {
		var dir = "down",
		pos = this.container.scrollTop();
		
		if ( pos > this.lastScrollPos ){
			dir = "down";
		} else {
			dir = "up";
		}
		
		this.lastScrollPos = pos;		
		/* Clear the loaded page, which won't be visible for the user ( since it may scrolled down or up ) */
		this.clearPageHeaders( dir );
		/* Load the empty page ( which was cleared while scrolling ) */
		this.fillPageHeaders( dir );
		
		if( dir == "down" ) {
			/* This will load the next new page */
			if( Math.ceil( this.container.scrollTop() + this.container.innerHeight() ) >= this.container[0].scrollHeight ) {
				this.loadNextPage();
			}
		}
	};
	
}