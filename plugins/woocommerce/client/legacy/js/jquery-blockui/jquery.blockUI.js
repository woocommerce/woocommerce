function block(el, options={}) {
	const styles = options.overlayCSS || {};
	const opacity = styles.opacity || 0.6; // 0.6 is the default overlay opacity

	const overlays = el.getElementsByClassName('blockOverlay');
	if (overlays.length) {
		overlays[0].style.transitionDuration = '200ms'; // 200 is the default fadeIn time in millis
		overlays[0].style.opacity = opacity;

		return el;
	}

	const position = window.getComputedStyle(el).getPropertyValue('position');
	if (position === 'static') el.style.position = 'relative';

	const $el = window.jQuery && window.jQuery(el);
	el.dataset['blockUI.isBlocked'] = true;
	$el && $el.data('blockUI.isBlocked', true);

	const lyr1 = document.createElement('div');
	lyr1.className = 'blockUI';
	lyr1.style.display = 'none';

	el.append(lyr1);

	// layer2 is the overlay layer which has opacity and a wait cursor (by default)
	const lyr2 = document.createElement('div');
	lyr2.className = 'blockUI blockOverlay';

	const style = lyr2.style;
	style.zIndex = 1000;
	style.border = 'none';
	style.margin = 0;
	style.padding = 0;
	style.width = '100%';
	style.height = '100%';
	style.top = 0;
	style.left = 0;
	style.background = styles.background || '#000'; // '#000' is the default overlay background
	style.opacity = 0;
	style.cursor = styles.cursor || 'wait'; // 'wait' is the default overlay cursor
	style.position = 'absolute';

	el.append(lyr2);

	lyr2.offsetWidth; // wait a frame
	style.transitionProperty = 'opacity';
	style.transitionDuration = '200ms'; // 200 is the default fadeIn time in millis
	style.opacity = opacity;

	const transitionend = ev => {
		if (style.opacity === '0') {
			if (position === 'static') el.style.position = 'static';

			el.dataset['blockUI.isBlocked'] = false;
			$el && $el.data('blockUI.isBlocked', false);

			lyr2.removeEventListener('transitionend', transitionend);
			lyr2.remove();
			lyr1.remove();
		}
	};
	lyr2.addEventListener('transitionend', transitionend);

	return el;
}

function unblock(el) {
	const overlays = el.getElementsByClassName('blockOverlay');
	if (overlays.length) {
		overlays[0].style.transitionDuration = '400ms'; // 400 is the default fadeOut time in millis
		overlays[0].style.opacity = 0;
	}

	return el;
}

(function($) {
	$.fn.block = function(opts) {
		return this.each(function() {
			block(this, opts);
		});
	};

	$.fn.unblock = function(opts) {
		return this.each(function() {
			unblock(this);
		});
	};
})(jQuery);
