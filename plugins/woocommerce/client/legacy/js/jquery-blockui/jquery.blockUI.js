function block(el, options={}) {
	const styles = options.overlayCSS || {};
	const opacity = styles.opacity || 0.6; // 0.6 is the default overlay opacity

	const overlays = el.getElementsByClassName('blockOverlay');
	if (overlays.length) {
		overlays[0].style.transitionDuration = '200ms'; // 200ms is the default fadeIn time
		overlays[0].style.opacity = opacity; // animate a fadeIn

		return el;
	}

	const position = window.getComputedStyle(el).getPropertyValue('position');
	if (position === 'static') el.style.position = 'relative';

	const $el = window.jQuery && window.jQuery(el);
	$el && $el.data('blockUI.isBlocked', true);
	el.dataset.isBlocked = true;

	// layer1 is the iframe layer which is used to supress bleed through of underlying content
	// it was only used for IE; now we keep layer1 as an empty div for backwards compatibility
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
	style.transitionDuration = '200ms'; // 200ms is the default fadeIn time
	style.opacity = opacity; // animate a fadeIn

	// add a fadeOut end event listener
	const transitionend = ev => {
		// check if finishing a fadeOut (we don't care for a fadeIn finishing)
		if (style.opacity === '0') {
			if (position === 'static') el.style.position = 'static';

			$el && $el.data('blockUI.isBlocked', false);
			el.dataset.isBlocked = false;

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
		overlays[0].style.transitionDuration = '400ms'; // 400ms is the default fadeOut time
		overlays[0].style.opacity = 0; // animate a fadeOut
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
