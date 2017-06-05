/* 
 * @author 		: Saravana Kumar K
 * @copyright	: Sarkware Pvt Ltd
 * @purpose		: Masking object ( used to mask any container whichever being refreshed ) */
var suMask = function() {
	this.Top = 0;
	this.Left = 0;
	this.Bottom = 0;
	this.Right = 0;
	
	this.Target = null;
	this.mask = null;
	
	this.getPosition = function(targetID) {
		this.Target = jQuery("#"+targetID);		
		
		var position = this.Target.position();
		var offset = this.Target.offset();
	
		this.Top = offset.top;
		this.Left = offset.left;
		this.Bottom = jQuery(window).width() - position.left - this.Target.width();
		this.Right = jQuery(window).height() - position.right - this.Target.height();
	};

	this.doMask = function(targetID, message) {
		this.mask = jQuery('<div class="su-mask">'+message+'</div>');		
		this.Target = jQuery("#"+targetID);			
		this.Target.append(this.mask);

		this.mask.css("left", "0px");
		this.mask.css("top", "0px");
		this.mask.css("right", this.Target.width()+"px");
		this.mask.css("bottom", this.Target.height()+"px");

		this.mask.css("line-height", this.Target.height()+"px");
	};

	this.doUnMask = function() {				
		this.mask.remove();
	};
}