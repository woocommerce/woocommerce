/**
 * Rounding details for cart totals.
 *
 * A total that doesn't add up from the prices shown is a button (an info icon and the amount) that opens a
 * popover="auto" tooltip. The browser handles click or tap, Escape and clicking elsewhere. This script also opens it
 * on hover and keyboard focus, keeps it open after a click or tap, places it next to the button, and keeps
 * aria-expanded in sync. Events are delegated, so totals added later (cart updates, the blocks) work too.
 */
( function () {
	if ( window.wcRoundedTotalInfo || ! ( 'popover' in HTMLElement.prototype ) ) {
		return;
	}
	window.wcRoundedTotalInfo = true;

	var BUTTON = '.wc-rounded-total-info__button';
	var TOOLTIP = '.wc-rounded-total-info__tooltip';
	var GAP = 6;
	var EDGE = 4;
	var CLOSE_DELAY = 150;
	var closeTimer;
	var canTransitionOut;

	function getTooltip( button ) {
		return document.getElementById( button.getAttribute( 'popovertarget' ) );
	}

	function getButton( tooltip ) {
		return document.querySelector(
			'[popovertarget="' + CSS.escape( tooltip.id ) + '"]'
		);
	}

	function isOpen( tooltip ) {
		return tooltip.matches( ':popover-open' );
	}

	// Whether a computed colour is fully opaque: "rgb(…)", or "rgba(…)"/"color(… / a)" with an alpha of 1.
	function isOpaque( colour ) {
		if ( ! colour || colour === 'transparent' ) {
			return false;
		}
		if ( colour.indexOf( 'rgba(' ) === 0 ) {
			return parseFloat( colour.slice( colour.lastIndexOf( ',' ) + 1 ) ) >= 1;
		}
		var slash = colour.lastIndexOf( '/' );
		return slash === -1 || parseFloat( colour.slice( slash + 1 ) ) >= 1;
	}

	// The first opaque background behind an element: a table cell, a drawer, or the page.
	function surfaceBehind( element ) {
		for ( var node = element; node && node.nodeType === 1; node = node.parentElement ) {
			var colour = getComputedStyle( node ).backgroundColor;
			if ( isOpaque( colour ) ) {
				return colour;
			}
		}
		return '';
	}

	// Above the button, or below it when there's no room, and inside the viewport. Measure from the top-left corner:
	// left where it sits in the page, the tooltip only gets the width to the screen's edge (e.g. in a narrow drawer).
	// The tooltip paints the surface behind the button, so it matches light, dark and coloured themes.
	function place( button, tooltip ) {
		var anchor = button.getBoundingClientRect();
		tooltip.style.setProperty(
			'--wc-rounded-total-info-surface',
			surfaceBehind( button ) || 'Canvas'
		);
		tooltip.style.top = '0px';
		tooltip.style.left = '0px';
		var size = tooltip.getBoundingClientRect();
		var above = anchor.top - size.height - GAP;
		var left = anchor.left + anchor.width / 2 - size.width / 2;
		tooltip.setAttribute(
			'data-placement',
			above >= EDGE ? 'above' : 'below'
		);
		tooltip.style.top =
			( above >= EDGE ? above : anchor.bottom + GAP ) + 'px';
		tooltip.style.left =
			Math.max(
				EDGE,
				Math.min( left, window.innerWidth - size.width - EDGE )
			) + 'px';
	}

	function show( button ) {
		var tooltip = getTooltip( button );
		clearTimeout( closeTimer );
		if ( ! tooltip ) {
			return;
		}
		// Coming back while it fades out: keep it open.
		tooltip.removeAttribute( 'data-closing' );
		if ( ! isOpen( tooltip ) ) {
			tooltip.showPopover();
			place( button, tooltip );
		}
	}

	// Whether the browser keeps a closing popover in the top layer while it fades (transitions `overlay` and
	// `display`). Firefox parses allow-discrete without transitioning display, so test it on an element.
	function supportsTransitionOut() {
		if ( canTransitionOut === undefined ) {
			var probe = document.createElement( 'div' );
			probe.style.cssText =
				'transition: display 1s allow-discrete !important; display: block;';
			document.body.appendChild( probe );
			getComputedStyle( probe ).display;
			probe.style.display = 'none';
			canTransitionOut =
				CSS.supports( 'overlay', 'auto' ) &&
				getComputedStyle( probe ).display === 'block';
			probe.remove();
		}
		return canTransitionOut;
	}

	// Close with the fade. Where the browser can't keep a closing popover in the top layer, fade it first (the
	// stylesheet treats data-closing as closed), then hide it. Escape and clicking elsewhere close it at once there.
	function hide( tooltip ) {
		if ( ! isOpen( tooltip ) ) {
			return;
		}
		if ( supportsTransitionOut() ) {
			tooltip.hidePopover();
			return;
		}
		tooltip.setAttribute( 'data-closing', '' );
		Promise.race( [
			Promise.allSettled(
				tooltip.getAnimations().map( function ( animation ) {
					return animation.finished;
				} )
			),
			new Promise( function ( resolve ) {
				setTimeout( resolve, 500 );
			} ),
		] ).then( function () {
			if ( tooltip.hasAttribute( 'data-closing' ) && isOpen( tooltip ) ) {
				tooltip.hidePopover();
			}
		} );
	}

	// A short delay lets the pointer move from the button onto the tooltip. A tooltip opened by a click or tap stays.
	function closeSoon( tooltip ) {
		clearTimeout( closeTimer );
		if ( tooltip && ! tooltip.hasAttribute( 'data-pinned' ) ) {
			closeTimer = setTimeout( function () {
				hide( tooltip );
			}, CLOSE_DELAY );
		}
	}

	// The tooltip for an element inside a button or tooltip, if any.
	function tooltipFor( element ) {
		var button = element && element.closest && element.closest( BUTTON );
		if ( button ) {
			return getTooltip( button );
		}
		return element && element.closest ? element.closest( TOOLTIP ) : null;
	}

	document.addEventListener( 'mouseover', function ( event ) {
		var button = event.target.closest && event.target.closest( BUTTON );
		if ( button ) {
			show( button );
		} else if ( tooltipFor( event.target ) ) {
			clearTimeout( closeTimer );
		}
	} );

	document.addEventListener( 'mouseout', function ( event ) {
		var tooltip = tooltipFor( event.target );
		if ( tooltip && tooltipFor( event.relatedTarget ) !== tooltip ) {
			closeSoon( tooltip );
		}
	} );

	document.addEventListener( 'focusin', function ( event ) {
		var button = event.target.closest && event.target.closest( BUTTON );
		if ( button ) {
			show( button );
		}
	} );

	document.addEventListener( 'focusout', function ( event ) {
		var button = event.target.closest && event.target.closest( BUTTON );
		if ( button ) {
			closeSoon( getTooltip( button ) );
		}
	} );

	// event.detail is 0 for Enter and Space, so only a click or tap keeps the tooltip open.
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest && event.target.closest( BUTTON );
		var tooltip = button && getTooltip( button );
		if ( tooltip && event.detail > 0 ) {
			tooltip.setAttribute( 'data-pinned', '' );
		}
	} );

	// Escape closes an open tooltip only, not a drawer or dialog around it (such as the Mini-Cart), so stop the key
	// before their handlers see it. Listening on window in the capture phase runs first.
	window.addEventListener(
		'keydown',
		function ( event ) {
			if ( event.key !== 'Escape' ) {
				return;
			}
			var open = Array.prototype.filter.call(
				document.querySelectorAll( TOOLTIP ),
				isOpen
			);
			if ( open.length ) {
				event.stopPropagation();
				open.forEach( function ( tooltip ) {
					tooltip.hidePopover();
				} );
			}
		},
		true
	);

	// `toggle` doesn't bubble, so listen in the capture phase. Escape, clicking elsewhere or another popover can close
	// the tooltip, so its state is the source of truth for aria-expanded.
	document.addEventListener(
		'toggle',
		function ( event ) {
			var tooltip = event.target;
			if ( ! tooltip.matches || ! tooltip.matches( TOOLTIP ) ) {
				return;
			}
			var button = getButton( tooltip );
			var open = event.newState === 'open';
			if ( button ) {
				button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			}
			if ( open && button ) {
				place( button, tooltip );
			} else {
				tooltip.removeAttribute( 'data-pinned' );
				tooltip.removeAttribute( 'data-closing' );
			}
		},
		true
	);

	function placeOpenTooltips() {
		document.querySelectorAll( TOOLTIP ).forEach( function ( tooltip ) {
			var button = isOpen( tooltip ) && getButton( tooltip );
			if ( button ) {
				place( button, tooltip );
			}
		} );
	}
	window.addEventListener( 'scroll', placeOpenTooltips, {
		passive: true,
		capture: true,
	} );
	window.addEventListener( 'resize', placeOpenTooltips );
} )();
