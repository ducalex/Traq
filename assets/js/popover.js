/*!
 * Popover
 * Copyright (c) 2012 Jack P.
 * All Rights Reserved
 * https://github.com/nirix
 *
 * Released under the BSD 3-clause license
 */
(function($){
	$.fn.popover = function(parent, event, onHide) {
		var event = event || 'click';
		var onHide = onHide || function(){};

		var popover = $(this);

		// Reset popover
		function cleanup() {
			parent.off('.popover');
			popover.off('.popover');
			$(document).off('.popover');
		}

		// Set the position
		popover.css({
			left: ((parent.offset().left + (parent.outerWidth() / 2)) - (popover.outerWidth() / 2)) + 'px',
			top: (parent.offset().top + parent.height()) + 'px',
			height: 'auto'
		});

		// Slide it down
		popover.stop(true, true).slideDown('fast', function(){
			// Click
			if (event == 'click') {
				// Bind a click to the document
				$(document).on('click.popover', function(){
					// Fade it out
					popover.fadeOut('fast');
					onHide();
					cleanup();
				}).not(popover);

				// Bind a click to the popover
				popover.on('click.popover', function(e){
					e.stopPropagation();
				});
			}
			// Hover
			else if (event == 'hover') {
				// Delay the mouse leave event binding for the parent
				parent.delay(2000).mouseleave(function(){
					popover.stop(true, true).fadeOut('fast');
				});

				// Handle the hover of the popover
				popover.hover(
					// Enter
					function(){
						parent.off('mouseleave.popover');
						popover.stop(true, true).show();
					},
					// Leave
					function(){
						cleanup();
						popover.stop(true, true).fadeOut('fast');
						onHide();
					}
				);
			}
		});
	}
})(jQuery);