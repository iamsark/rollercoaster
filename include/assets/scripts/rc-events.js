var rcEvents = function( _rc ) {
	/* Controller object's reference */
	this.controller = _rc;
	/* Register shortcuts ( we are using Mouse Strap lib for this purpose )*/
	this.registerShortcut = function() {
		
	};
	/* Ok let's do the heavy lifting task of registering events for the entire RC life cycle */
	this.registerEvents = function() {
		
		var wHeight = $( window ).height();
		/* Adjust the containers height respect to Window */
		$( "#rc-left-nav-col" ).height( wHeight );
		$( "#rc-mail-list-section" ).height( wHeight );
		$( "#rc-mail-header-container" ).height( wHeight - 199 );
		$( "#rc-mail-viewer-section" ).height( wHeight );
		$( "#rc-mail-welcome-screen" ).height( wHeight - 100 );
				
		/* Re adjust the containers height respect to Browser Window's height */
		$( window ).resize( function() {
			wHeight = $( this ).height();
			$( "#rc-left-nav-col" ).height( $( this ).height() );
			$( "#rc-mail-list-section" ).height( $( this ).height() );
			$( "#rc-mail-header-container" ).height( $( this ).height() - 199 );
			$( "#rc-mail-viewer-section" ).height( $( this ).height() );
			$( "#rc-mail-welcome-screen" ).height( $( this ).height() - 100 );
			/* Resize all viewer's message frame height */
			var vbKeys = Object.keys( rcControllerObj.viewer.binders );
			for( var i = 0; i < vbKeys.length; i++ ) {
				rcControllerObj.viewer.binders[ vbKeys[ i ] ].view.adjustFrameHeight();
			}
			/* Resize all composer's editor height */
			var vcKeys = Object.keys( rcControllerObj.composer.binders );
			for( var i = 0; i < vcKeys.length; i++ ) {
				rcControllerObj.composer.binders[ vcKeys [ i ] ].view.adjustFrameHeight();
			}			
		});

		/**
		 * Click event for folder container accordian
		 */
		$( document ).on( "click", "a.rc-left-nav-accordian-btn", this, function(e) {
			var id = $( this ).attr( "id" );
			$( $( this ).attr( "href" ) ).toggle( "slow", function(){
				if( $( this ).is(':visible') ) {
					$( "#" + id ).find( "i" ).removeClass().addClass( "fa fa-caret-up" );
				} else {
					$( "#" + id ).find( "i" ).removeClass().addClass( "fa fa-caret-down" );
				}
			});
			e.preventDefault();
		});
		
		/**
		 * Registering event handler for folder menu
		 * which will reload the mail list in the middle panel 
		 */
		$( document ).on( "click", "#rc-folders-list li a.rc-mail-folder-view-menu", this, function(e) {	
			if( e.data.controller.ajaxFlaQ && e.data.controller.helper.isSafeToFetch() ) {
				e.data.controller.switchContext( "folder" );
				$( this ).parent().parent().find( "li" ).removeClass( "selected" );
				$( this ).parent().addClass( "selected" );
				e.data.controller.folder.selectFolder( $( this ).attr( "data-folder" ) );
				e.preventDefault();
			} else {
				if( e.data.controller.request.request == "GET" ) {
					e.data.controller.notify.show( "Please wait while Loading...", "info", true );
				} else {
					e.data.controller.notify.show( "Please wait while Processing", "info", true );
				}
			}			
		});
		
		$( document ).on( "click", "a.rc-mail-folder-add-menu", this, function(e) {
			e.data.controller.folder.prepareToCreateFolder( $( this ) );
		});
		
		$( document ).on( "click", "a.rc-mail-folder-refresh-menu", this, function(e) {
			e.data.controller.folder.prepareToReloadFolder( $( this ) );
		});
		
		$( document ).on( "click", "a.rc-mail-folder-delete-menu", this, function(e) {
			e.data.controller.folder.prepareToDeleteFolder( $( this ) );
		});
		
		$( document ).on( "click", "a.rc-mail-folder-edit-menu", this, function(e) {
			e.data.controller.folder.prepareToRenameFolder( $( this ) );
		});
		
		/**
		 * 
		 */
		$( document ).on( "click", "#rc-mail-mode-toggle-container > button", this, function(e) {
			if( $( this ).hasClass( "disabled" ) ) {
				return 
			} 
			$( this ).parent().find( "button" ).removeClass( "selected" );
			$( this ).attr( "class", "selected" );
			if( $( this ).attr( "data-mode" ) == "viewer" ) {
				e.data.controller.switchContext( "folder" );
				$( "#rc-mail-welcome-screen" ).hide();
				$( "#rc-mail-viewer-compose-container" ).hide();
				$( "#rc-mail-viewer-view-container" ).show();				
			} else {
				e.data.controller.switchContext( "composer" );
				$( "#rc-mail-welcome-screen" ).hide();
				$( "#rc-mail-viewer-view-container" ).hide();	
				$( "#rc-mail-viewer-compose-container" ).show();				
			}
		});
		
		/**
		 * 
		 */
		$( document ).on( "click", "a.rc-mail-header-quick-action, a.rc-mail-viewer-action", this, function(e) {
			
			var _folder = $( this ).attr( "data-folder" );
			var _msgno = $( this ).attr( "data-msgno" );
			var _uid = $( this ).attr( "data-uid" );
			var _action = $( this ).attr( "data-action" );
			
			/* Switch to composer context, as three action belongs to composer module */
			e.data.controller.switchContext( "composer" );
			
			if( _action == "reply" ) {			
				e.data.controller.composer.loadComposer( "reply", _folder, _msgno, _uid, false );
			} else if( _action == "reply-all" ) {
				e.data.controller.composer.loadComposer( "reply-all", _folder, _msgno, _uid, false );
			} else if( _action == "forward" ) {
				e.data.controller.composer.loadComposer( "forward", _folder, _msgno, _uid, false );
			} else if( _action == "print" ) {
				/* Need to switch to Viewer context */
				e.data.controller.viewer.oldContext = e.data.controller.context;
				e.data.controller.switchContext( "lister" );
				e.data.controller.viewer.prepareforPrint( _folder, _msgno, _uid );
			} else if( _action == "move" ) {
				/* Make sure it's in 'folder' context */
				e.data.controller.switchContext( "lister" );
				/* Prepare the Move dialog box
				 * in which we will show the list of folder to the user */
				var _options = [];
				for( var i = 0; i < e.data.controller.folder.folders.length; i++ ) {
					if( e.data.controller.folder.current != e.data.controller.folder.folders[i].name ) {
						_options.push({
							"key": e.data.controller.folder.folders[i].name,
							"value": e.data.controller.folder.folders[i].display_name
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
					/* This action is not required to go through context object
					 * Since it involve two context 'lister' as well as 'viewer' */
					e.data.controller.dialog.store = {
						folder: _folder,
						msgno: _msgno,
						uid: _uid
					};
					/* Show the dialog box */
					e.data.controller.dialog.popup( "Where.?", "", "move", fields, buttons, "yes", "medium" );
				}
			} else if( _action == "flag" || _action == "unflag" ) {
				/*  */
				e.data.controller.lister.updateFlag( _folder, _msgno, _uid, _action );
			} else {
				/* Make sure it's in 'folder' context */
				e.data.controller.switchContext( "lister" );
				/* This must be Delete Action
				/* This action also not required to go through context object
				 * Since it involve two context 'lister' as well as 'viewer' */
				e.data.controller.dialog.store = {
					folder: _folder,
					msgno: _msgno,
					uid: _uid
				};
				e.data.controller.dialog.confirm( "Confirm", "Do you want to Delete this Mail.?", "delete",[ { action: "yes", title: "Yes" },{ action: "no", title: "No" } ] );				
			}
			
			if( $( this ).hasClass( "rc-mail-viewer-action" ) && _action != "reply" ) {
				$( this ).parent().hide();
			}
			
			e.preventDefault();
			/* Stop the event bubbling */
			e.stopPropagation();
		});
		
		$( document ).on( "click", "div.rc-mail-viewer-attachment-toggle", this, function(e) {
			var dropBar = $( this ).next();
			var dropIcon = $( this ).find( "a.rc-mail-viewer-attachment-drop-btn" ).find( "i" );
			$( this ).next().toggle( "normal", function() {
				if( dropBar.is( ":visible" ) ) {
					dropIcon.attr( "class", "" ).addClass( "fa fa-caret-up" );
				} else {
					dropIcon.attr( "class", "" ).addClass( "fa fa-caret-down" );
				}
			});
		});
		
		$( document ).on( "click", "div.rc-mail-viewer-attachment-row", this, function(e) {
			var uid = $( this ).attr( "data-uid" );
			var msgno = $( this ).attr( "data-msgno" );
			var fname = $( this ).attr( "data-fname" );
			var folder = $( this ).attr( "data-folder" );
			e.data.controller.viewer.downloadAttachment( "single", folder, uid, msgno, fname );
		});
		
		$( document ).on( "click", "a.rc-mail-viewer-download-all", this, function(e) {
			var uid = $( this ).attr( "data-uid" );
			var msgno = $( this ).attr( "data-msgno" );
			var folder = $( this ).attr( "data-folder" );
			e.data.controller.viewer.downloadAttachment( "all", folder, uid, msgno );
			e.preventDefault();
		});
		
		$( "#rc-mail-header-container" ).on( "scroll", this, function(e) {
			rcControllerObj.lister.binders[ rcControllerObj.folder.current ].view.onHeaderScrolling();
		});
		
		/**
		 * Registering click event for email lists
		 * which will load the clicked message on the right side panel
		 */
		$( document ).on( "click", "#rc-mail-header-container div[type=message]", this, function(e) {
			e.data.controller.switchContext( "folder" );
			$( "#rc-mail-header-container div.rc-header-block.selected" ).removeClass( "selected" );
			$( this ).addClass( "selected" );
			e.data.controller.folder.selectFolder( $( this ).attr( "data-folder" ) );
			e.data.controller.viewer.initMailView( $( this ).attr( "data-uid" ), $( this ).attr( "data-msgno" ) );			
		});
		
		/**
		 * Filter menu item event handler
		 * Usually it feature two options, 'ALL' or 'SEEN'
		 */
		$( document ).on( "click", "a.rc-folder-filter-btn", this, function(e) {
			$( "a.rc-folder-filter-btn.selected" ).removeClass( "selected" );
			$( this ).addClass( "selected" );
			/* Reset the current page index */
			e.data.controller.lister.binders[ e.data.controller.folder.current ].view.currentPage = 1;
			/* Prepare the header list before rendering
			 * setHeaders does the filtering operation */
			e.data.controller.lister.binders[ e.data.controller.folder.current ].view.setHeaders();
			/* Well trigger to render the list */
			e.data.controller.lister.binders[ e.data.controller.folder.current ].view.loadView();
			e.preventDefault();
		});
		
		/**
		 * Message list order options
		 * either it could be ASC or DSC
		 * default is DSC ( Date wise ) 
		 */
		$( document ).on( "click", "a.rc-folder-sort-btn", this, function(e) {
			/* Do only when user select different sorting option */
			if( e.data.controller.folder.sort != $( this ).attr( "data-sort" ) ) {
				$( "a.rc-folder-sort-btn.selected" ).removeClass( "selected" );
				$( this ).addClass( "selected" );
				/* Update the sort option */
				e.data.controller.folder.sort =  $( this ).attr( "data-sort" );
				/* Reset the view */
				e.data.controller.lister.binders[ e.data.controller.folder.current ].view.reset();
				/* Reset the headers list */
				e.data.controller.lister.binders[ e.data.controller.folder.current ].headers = [];
				/* reset the sync page number property */
				e.data.controller.lister.binders[ e.data.controller.folder.current ].currentSyncPage = 1;
				/* Reset the sync in progress flag */
				e.data.controller.lister.binders[ e.data.controller.folder.current ].syncInProgress = false;
				/* Now initiate the fetching process */				
				e.data.controller.lister.binders[ e.data.controller.folder.current ].fetchHeaders( true );
			}			
			e.preventDefault();
		});
		
		/**
		 * 
		 */
		$( document ).on( "click", "a.rc-mail-viewer-action-drop-btn", this, function(e) {
			$( this ).next().toggle();
			e.preventDefault();
			e.stopPropagation();
		});

		/**
		 * Click event for closing mail viewer tab
		 */
		$( document ).on( "click", "a.rc-mail-viewer-close-btn", this, function(e) {
			e.data.controller.viewer.closeMail( $( this ).attr( "href" ) );
			e.preventDefault();
			e.stopPropagation();
		});
		
		/**
		 * Click event for closing mail viewer tab
		 */
		$( document ).on( "click", "a.rc-mail-composer-close-btn", this, function(e) {
			e.data.controller.composer.closeComposer( $( this ).attr( "href" ) );
			e.preventDefault();
			e.stopPropagation();
		});
		
		/**
		 * Registering click event for Compose button
		 */
		$( document ).on( "click", "#rc-compose-btn", this, function(e) {			
			e.data.controller.switchContext( "composer" );	
			/* Load the composer view */
			e.data.controller.composer.loadComposer( "new", "", -1, -1, false );					
		});
		
		$( document ).on( "click", "button.rc-header-meta-popup-btn", this, function(e) {
			var btn = $( this );
			var metaBlock = $( this ).parent().next();
			$( this ).parent().next().toggle( "normal", function() {
				if( metaBlock.is( ":visible" ) ) {
					btn.find( "i" ).attr( "class", "" ).addClass( "fa fa-caret-up" ); 
				} else {
					btn.find( "i" ).attr( "class", "" ).addClass( "fa fa-caret-down" );
				}
				rcControllerObj.viewer.binders[ btn.attr( "data-uid" ) ].view.adjustFrameHeight();
			});
			e.stopPropagation();
		});
		
		$( document ).on( "click", "div.rc-mail-composer-form-row", this, function(e) {
			$( this ).find( "input" ).trigger( "focus" );
		});
		
		$( document ).on( "keydown", "input.rc-composer-addr-field", this, function(e) {			
			if( e.data.controller.helper.getKeyCode( e ) == 27 ) {
				$( "div.rc-composer-addr-list" ).remove();
				e.data.controller.composer.binder[ e.data.controller.composer.cBinder ].view.noSuggestion = true;
				return false;
			}
			if( e.data.controller.helper.getKeyCode( e ) == 9 ) {
				if( ! e.shiftKey ) {
					if( $( this ).attr( "id" ) == "rc-mail-composer-to" ) {						
						if( $( "#rc-mail-composer-cc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-cc" ).trigger( "focus" );
						} else if( $( "#rc-mail-composer-bcc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-bcc" ).trigger( "focus" );
						} else {
							$( "#rc-mail-composer-subject" ).trigger( "focus" );
						}
					} else if( $( this ).attr( "id" ) == "rc-mail-composer-cc" ) {
						if( $( "#rc-mail-composer-bcc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-bcc" ).trigger( "focus" );
						} else {
							$( "#rc-mail-composer-subject" ).trigger( "focus" );
						}
					} else if( $( this ).attr( "id" ) == "rc-mail-composer-bcc" ) {					
						$( "#rc-mail-composer-subject" ).trigger( "focus" );					
					}
				} else {
					if( $( this ).attr( "id" ) == "rc-mail-composer-cc" ) {
						$( "#rc-mail-composer-to" ).trigger( "focus" );
					} else if( $( this ).attr( "id" ) == "rc-mail-composer-bcc" ) {
						if( $( "#rc-mail-composer-cc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-cc" ).trigger( "focus" );
						} else {
							$( "#rc-mail-composer-to" ).trigger( "focus" );
						}
					} else if( $( this ).attr( "id" ) == "rc-mail-composer-subject" ) {						
						if( $( "#rc-mail-composer-bcc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-bcc" ).trigger( "focus" );
						} else if( $( "#rc-mail-composer-cc" ).is( ":visible" ) ) {
							$( "#rc-mail-composer-cc" ).trigger( "focus" );
						} else {
							$( "#rc-mail-composer-to" ).trigger( "focus" );
						}
					}
				}				
				return false;
			}
		});
		
		/**
		 * Keyup event handler for Address Fields
		 * Responsible for showing email address suggestion list
		 */
		$( document ).on( "keyup", "input.rc-composer-addr-field", this, function(e) {
			if( e.data.controller.helper.getKeyCode( e ) != 27 ) {
				if( $( this ).val().length > 2 ) {
					e.data.controller.composer.fetchSuggestion( $( this ) );
				} else {
					/* Close suggestion box */
					$( "div.rc-composer-addr-list" ).hide();
				}
				if( e.data.controller.helper.getKeyCode( e ) == 27 ) {
					$( "div.rc-composer-addr-list" ).remove();
				}
			}			
		});
		
		/**
		 * 
		 */
		$( document ).on( "keyup", "input.rc-mail-composer-subject", this, function(e) {
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].header.subject = $( this ).val();
		});
		
		/**
		 * 
		 */
		$( document ).on( "blur", "input.rc-composer-addr-field", this, function(e) {
			setTimeout( function(){
				$( "div.rc-composer-addr-list" ).remove();
			}, 250 );			
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].view.noSuggestion = false;
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].onAddrFieldOutFocus( $( this ) );
		});
		

		/**
		 * Click event for suggestion address item
		 */
		$( document ).on( "click", "a.rc-us-list-item", this, function(e) {
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].addAddress( $( this ) );
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].view.noSuggestion = false;
			e.preventDefault();
		}); 
		
		/* Click event for address laabel remove action */
		$( document ).on( "click", "a.rc-composer-addr-rm-btn", this, function(e) {
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].removeAddress( $( this ) );
			$( this ).parent().remove();
			e.preventDefault();
		});
		
		/**
		 * Click event for Send Mail button
		 */
		$( document ).on( "click", "button.rc-mail-composer-send-btn", this, function(e) {
			e.data.controller.composer.doSend( $( this ).attr( "data-binder" ) );
		});
		
		/**
		 * Click event for Attachment button
		 */
		$( document ).on( "click", "button.rc-mail-composer-attach-btn", this, function(e) {
			e.data.controller.composer.doAttach();
		});
		
		/**
		 * Click event for adding CC address field
		 */
		$( document ).on( "click", "button.rc-mail-composer-cc-btn", this, function(e) {
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].view.toggleAdditionalField( "cc" );
			e.stopPropagation();
		});

		/**
		 * Click event for adding BCC address field
		 */
		$( document ).on( "click", "button.rc-mail-composer-bcc-btn", this, function(e) {
			e.data.controller.composer.binders[ e.data.controller.composer.cBinder ].view.toggleAdditionalField( "bcc" );
			e.stopPropagation();
		}); 
		
		/**
		 * Click event for composer cancel action
		 */
		$( document ).on( "click", "button.rc-mail-composer-cancel-btn", this, function(e) {
			e.data.controller.composer.closeComposer( $( this ).attr( "data-binder" ) );
		});

		/**
		 * Click event for search type button
		 * Which will show the search type drop down
		 */
		$( document ).on( "click", "#rc-mail-search-type-btn", this, function(e) {
			var sDrop = $( "#rc-mail-search-type-dropdown" );
			if( sDrop.is( ":visible" ) ) {				
				sDrop.css( "display", "none" );
				$( this ).find( "i" ).attr( "class", "" ).addClass( "fa fa-caret-down" );
			} else {
				sDrop.css( "display", "block" );
				$( this ).find( "i" ).attr( "class", "" ).addClass( "fa fa-caret-up" );
			}
			e.preventDefault();
			e.stopPropagation();
		});

		/**
		 * Click event for generic tab header button
		 */		
		$( document ).on( "click", "button.rc-tab-btn", this, function(e) {
			$( this ).parent().find( "button" ).removeClass( "selected" );
			$( this ).addClass( "selected" ); 
			$( "#" + $( this ).parent().attr( "data-tab" ) ).find( "> div" ).hide();
			$( "#" + $( this ).attr( "data-target" ) ).show();		
			if( $( this ).hasClass( "rc-mail-viewer-tab-btn" ) ) {
				/* If it is mail viewer tab - then switch the folder */
				e.data.controller.folder.selectFolder( $( this ).attr( "data-folder" ) );
				e.data.controller.viewer.setCurrentMailView( $( this ).attr( "data-uid" ) );
				$( "#rc-mail-header-container div.rc-header-block.selected" ).removeClass( "selected" );
				$( "#rc-mail-header-container div[data-uid="+ $( this ).attr( "data-uid" ) +"]" ).addClass( "selected" );	
			}
			if( $( this ).hasClass( "rc-mail-composer-tab-btn" ) ) {
				e.data.controller.composer.cBinder = $( this ).attr( "data-binder" );
			}
		});

		/* Confirm box ghost back click handler ( Just to add that shaking effect ) */
		$( document ).on( "click", "div.rc-dialog-ghost-back", this, function(e) {			
			var me = e.data;			
			if( me.controller.dialog.window ) {
				me.controller.dialog.window.find( "div.rc-dialog" ).addClass( "shakeit" );
				setTimeout( function(){
					if( me.controller.dialog.window ) {
						me.controller.dialog.window.find( "div.rc-dialog" ).removeClass( "shakeit" );
					}					
				}, 1000 );
				/* Also put back the focus to the one of the dialog button */
				if( me.controller.dialog.currentButton ) {
					me.controller.dialog.currentButton.trigger( "focus" );
				}
			}			
		});
		
		/* Confirm box action buttons clicked handler */
		$( document ).on( "click", "button.rc-dialog-btn", this, function(e) {
			var task = $( this ).attr( "data-task" );
			var action = $( this ).attr( "data-action" );
			if( e.data.controller.dialog.type != "popup" ) {
				e.data.controller.dialog.closeDialog();
			}			
			if( e.data.controller.contextObj ) {
				e.data.controller.contextObj.onUserConfirmed( task, action );				
			} else {
				$( document ).trigger( "rc_user_confirm", [ task, action ] );
			}						
		});
		
		$( document ).on( "change", "input.rc-mail-header-check", this, function(e) {			
			e.data.controller.lister.binders[ e.data.controller.folder.current ].onHeaderChecked( $( this ) );
		});
		
		$( document ).on( "change", "#rc-mail-select-all-check", this, function(e) {
			e.data.controller.lister.binders[ e.data.controller.folder.current ].onHeaderSelectAll( $( this ) );
		});
		
		$( document ).on( "click", "#rc-mail-search-type-dropdown, input.rc-mail-header-check", function(e) {
			e.stopPropagation();
		});
		
		$( document ).on( "change", "input:radio[name='rc-mail-search-type-item']", this, function(e) {
			e.data.controller.lister.binders[ e.data.controller.folder.current ].search( $( this ) );
		});
		
		$( document ).on( "keyup", "#rc-mail-search-text", this, function(e) {
			e.data.controller.lister.binders[ e.data.controller.folder.current ].search( $( this ) );
		});
		
		$( document ).on( "click", "#rc-mail-bulk-action-drop > a", this, function(e) {
			rcControllerObj.lister.handleBulkAction( $( this ) );
			e.preventDefault();
		});
		
		$( document ).on( "click", "div.rc-dialog", function(e) {
			e.stopPropagation();
		});
		
		$( document ).on( "click", function() {
			$( "div.rc-mail-drop-container" ).hide();
		});
		
		$( document ).on( "keydown", this, function(e) {
			if( e.data.controller.dialog.isOn() ) {
				if( e.data.controller.dialog.type == "popup" ) {				
					if( e.data.controller.helper.getKeyCode(e) == 9 ) {
						if( $( e.target ).hasClass( "rc-dialog-btn" ) && $( e.target ).is( ":last" ) ) {
							$( "#" + e.data.controller.dialog.fields[0].name ).trigger( "focus" );
							return false;
						}
					} else if( e.data.controller.helper.getKeyCode(e) == 13 ) {
						if( $( e.target ).hasClass( "rc-mail-popup-field" ) ) {
							e.data.controller.dialog.window.find( "button[data-action="+ e.data.controller.dialog.defaultBtn +"]" ).trigger( "click" );
						}
					}
				} else {
					if( $( e.target ).hasClass( "rc-dialog-btn" ) && $( e.target ).is( ":last" ) ) {
						$( e.target ).parent().find(":first-child").trigger( "focus" );
						return false;
					}
				}
				if( e.data.controller.helper.getKeyCode(e) == 27 ) {
					/* If it is ESC key, then close the dialog */
					e.data.controller.dialog.closeDialog();
				}
			}
		});
		
		/**
		 * Logout menu event
		 */
		$( document ).on( "click", "#rc-account-user-logout-menu", this, function(e) {
			$.ajax({
				url: docker, 
				data: { LOGOUT: true },
				success: function( _res ) {
					document.location.href = "";
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					document.location.href = "";
				}
			});
			e.preventDefault();
		});
		
	};
};