var rcViewerBinder = function( _viewer, _folder ) {
	
	this.viewer = _viewer;
	/**/
	this.fname = _folder;
	/* UID of the message that is being viewed */
	this.uid = null;
	/**/
	this.msgno = null;
	/* UI layer object, which is responsible for rendering the Viewer */
	this.view = null;	
	/* Header object of the mail that is being viewed */
	this.header = null;
	/* HTML body of the mail that is being viewed */
	this.htmlBody = null;
	/* Text body of the mail that is being viewed */
	this.plainBody = null;
	/* Attachment list of the mail that is being viewed */
	this.attachments = null;
	/**/
	this.loaded = false;
	
	/* Load the viewer object */
	this.loadView = function( _uid, _msgno ) {
		this.uid = _uid;
		this.msgno = _msgno;
		this.view = new rcViewerView( this, this.viewer.container );
		this.view.initView();
	};
	
	/* Initialize the binder */
	this.initBinder = function( _body ) {
		this.header = _body.header;
		this.htmlBody = _body.html_body;
		this.plainBody = _body.text_body;
		this.attachments = _body.attachments;
		this.view.loadMail();
		this.loaded = true;
	};
	
	/* Remove the mail viewer for the given uid */
	this.closeMail = function() {
		this.view.closeMail();
	};
	
};