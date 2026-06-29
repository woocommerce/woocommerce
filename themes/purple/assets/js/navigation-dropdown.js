/**
 * Navigation dropdown helpers.
 *
 * 1. Desktop (>= 600px): align top-level dropdowns with the header's bottom
 *    edge (see --purple-nav-dropdown-offset usage in style.css).
 * 2. Mobile overlay (< 600px): turn the always-expanded submenus into a
 *    collapsible accordion (desktop keeps hover-to-open, so WordPress's single
 *    open-on-click setting can't give us a mobile-only accordion on its own).
 */
( function () {
	var DESKTOP = '(min-width: 600px)';
	var MOBILE = '(max-width: 599.98px)';

	/* --- 1. Align the desktop dropdown to the header bottom --- */
	function updateOffset() {
		if ( ! window.matchMedia( DESKTOP ).matches ) {
			return;
		}

		document.querySelectorAll( '.wp-block-navigation' ).forEach(
			function ( nav ) {
				// Overlay-only nav blocks (mobile hamburger) have no top-level
				// container; the desktop header nav does.
				var item = nav.querySelector(
					':scope > .wp-block-navigation__container > .wp-block-navigation-item.has-child'
				);

				if ( ! item ) {
					return;
				}

				var header =
					nav.closest( 'header.wp-block-template-part' ) ||
					nav.closest( '.wp-block-template-part' );

				if ( ! header ) {
					return;
				}

				// Land the panel top on the header bottom. Pull up 1px so the
				// hairline reads flush on retina without a hero seam.
				var offset =
					Math.round( header.getBoundingClientRect().bottom ) -
					item.getBoundingClientRect().bottom -
					1;

				header.style.setProperty(
					'--purple-nav-dropdown-offset',
					Math.max( 0, offset ).toFixed( 2 ) + 'px'
				);
			}
		);
	}

	var frame;
	function scheduleOffset() {
		window.cancelAnimationFrame( frame );
		frame = window.requestAnimationFrame( updateOffset );
	}

	/* --- 2. Mobile submenu accordion --- */
	function initAccordion() {
		document
			.querySelectorAll( '.wp-block-navigation__responsive-container' )
			.forEach( function ( container ) {
				if ( container.dataset.purpleAccordion ) {
					return;
				}
				container.dataset.purpleAccordion = '1';

				// Capture phase + stopPropagation so we own the toggle and
				// WordPress's own submenu handler doesn't also fire.
				container.addEventListener(
					'click',
					function ( event ) {
						if ( ! window.matchMedia( MOBILE ).matches ) {
							return;
						}

						// Toggle when the parent row itself is tapped (label or
						// chevron). Tapping a real link inside an *open* submenu
						// must still navigate, so ignore clicks that land within
						// this item's own submenu container.
						var item = event.target.closest(
							'.wp-block-navigation-item.has-child'
						);
						if ( ! item || ! container.contains( item ) ) {
							return;
						}

						var submenu = item.querySelector(
							':scope > .wp-block-navigation__submenu-container'
						);
						if ( submenu && submenu.contains( event.target ) ) {
							return;
						}

						event.preventDefault();
						event.stopPropagation();
						item.classList.toggle( 'is-submenu-open' );
					},
					true
				);

				// Reset to all-collapsed whenever the overlay is opened.
				var opener = container.parentNode
					? container.parentNode.querySelector(
							'.wp-block-navigation__responsive-container-open'
					  )
					: null;
				if ( opener ) {
					opener.addEventListener( 'click', function () {
						container
							.querySelectorAll( '.is-submenu-open' )
							.forEach( function ( el ) {
								el.classList.remove( 'is-submenu-open' );
							} );
					} );
				}
			} );
	}

	function init() {
		updateOffset();
		initAccordion();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.addEventListener( 'load', updateOffset );
	window.addEventListener( 'resize', scheduleOffset );
} )();
