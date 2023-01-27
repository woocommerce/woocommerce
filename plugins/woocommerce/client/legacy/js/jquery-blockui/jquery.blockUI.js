function block(el, options={}) {
	const css = options.overlayCSS || {};
	const opacity = css.opacity || 0.6;
	const overlays = el.getElementsByClassName('blockOverlay');
	if (overlays.length) {
		overlays[0].style.transitionDuration = '200ms';
		overlays[0].style.opacity = opacity;

		return el;
	}

	const $el = window.jQuery && window.jQuery(el);
	el.datset['blockUI.isBlocked'] = true;
	$el && $el.data('blockUI.isBlocked', true);

	const position = window.getComputedStyle(el).getPropertyValue('position');
	if (position === 'static') el.style.position = 'relative';

	const blockUI = document.createElement('div');
	blockUI.className = 'blockUI';
	blockUI.style.display = 'none';

	el.append(blockUI);

	const blockOverlay = document.createElement('div');
	blockOverlay.className = 'blockUI blockOverlay';

	const style = blockOverlay.style;
	style.zIndex = 1000;
	style.border = 'none';
	style.margin = 0;
	style.padding = 0;
	style.width = '100%';
	style.height = '100%';
	style.top = 0;
	style.left = 0;
	style.background = css.background || '#000';
	style.opacity = 0;
	style.cursor = css.cursor || 'wait';
	style.position = 'absolute';

	el.append(blockOverlay);

	blockOverlay.offsetWidth; // wait a frame
	style.transitionProperty = 'opacity';
	style.transitionDuration = '200ms';
	style.opacity = opacity;

	const transitionend = ev => {
		if (style.opacity === '0') {
			if (position === 'static') el.style.position = position;

			el.datset['blockUI.isBlocked'] = false;
			$el && $el.data('blockUI.isBlocked', false);

			blockOverlay.removeEventListener('transitionend', transitionend);
			blockOverlay.remove();
			blockUI.remove();
		}
	};
	blockOverlay.addEventListener('transitionend', transitionend);

	return el;
}

function unblock(el) {
	const overlays = el.getElementsByClassName('blockOverlay');
	if (overlays.length) {
		overlays[0].style.transitionDuration = '400ms';
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
