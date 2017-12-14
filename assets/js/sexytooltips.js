/*!
 * Sexy tooltips
 * Copyright (c) 2012 Jack P.
 * All Rights Reserved
 * https://github.com/nirix
 *
 * Released under the BSD 3-clause lisence.
 */
(function($){
	$.fn.sexyTooltip = function(position) {
		var e = $(this);

		// Set default position
		var position = position || 'right';

		// Check if the tooltip container exists...
		if (!$('#sexytooltip').length) {
			$('body').append('<div id="sexytooltip"></div>');
		}
		else {
			$('#sexytooltip').stop(true, true).hide();
			$('#sexytooltip').removeClass('sexytooltip-right sexytooltip-top');
		}

		// Shortcut for the tooltip container
		var tip = $('#sexytooltip');

		if (e.attr('title')) {
			e.attr('data-tooltip', e.attr('title'));
			e.attr('title', null);
		}

		tip.html(e.attr('data-tooltip'));

		if (position == 'right') {
			tip.addClass('sexytooltip-right').css({
				left: (e.offset().left + e.width() + parseInt(e.css('padding-left')) + parseInt(e.css('padding-right')) + parseInt(tip.css('padding-left')) + parseInt(tip.css('padding-right'))) + 'px',
				top: (e.offset().top - (tip.height() / 2) + (e.height() / 2) - 2) + 'px',
			});
		}
		else if (position == 'top') {
			tip.addClass('sexytooltip-top').css({
				left: e.offset().left + 'px',
				top: (e.offset().top - e.height() - tip.height()) + 'px'
			});
		}
		
		tip.fadeIn('fast');
		e.mouseleave(function(){ tip.stop(true, true).fadeOut('fast', function(){ e.off('mouseleave'); }); });
	}
})(jQuery);