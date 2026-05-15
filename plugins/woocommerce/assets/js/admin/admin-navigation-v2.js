/* global wcNavV2Config, jQuery */
/**
 * Nested admin navigation v2.
 *
 * The rail itself (Woo and non-Woo pages) is rendered natively by WordPress
 * via PHP-side menu splicing. This script's only job is to inject a
 * second-level cascade into native flyout items that have grandchildren in
 * the tree (Settings → Payments / WooPayments / Status, etc.), and to emit
 * the design-spec Tracks events on click.
 */
( function ( $ ) {
	'use strict';

	var tracks = ( window.wcTracks && window.wcTracks.recordEvent ) || function () {};

	// Server-localized base. Falls back to `/wp-admin/` if the localize step
	// somehow runs before the script (shouldn't happen in WP, but defensive).
	function adminBase() {
		var base = ( window.wcNavV2Config && window.wcNavV2Config.adminUrl ) || '/wp-admin/';
		return base.charAt( base.length - 1 ) === '/' ? base : base + '/';
	}

	function toAdminUrl( target ) {
		if ( ! target ) {
			return '#';
		}
		if ( target.indexOf( '?' ) >= 0 ) {
			return adminBase() + target;
		}
		return adminBase() + 'admin.php?page=' + target;
	}

	function buildByParent( tree ) {
		var byParent = {};
		Object.keys( tree ).forEach( function ( slug ) {
			var parent = tree[ slug ].parent;
			if ( ! parent ) {
				return;
			}
			byParent[ parent ] = byParent[ parent ] || [];
			byParent[ parent ].push( Object.assign( {}, tree[ slug ], { slug: slug } ) );
		} );
		Object.keys( byParent ).forEach( function ( p ) {
			byParent[ p ].sort( function ( a, b ) {
				return ( a.position || 0 ) - ( b.position || 0 );
			} );
		} );
		return byParent;
	}

	/**
	 * How long to wait before collapsing a flyout after the cursor leaves it.
	 * Gives users time to overshoot and come back without the menu snapping shut.
	 */
	var HOVER_CLOSE_DELAY = 600;

	/**
	 * Hover-intent-style open/close for flyouts inside `$root`.
	 *
	 * Behaviour:
	 * - Inside $root, responses are instant: hovering a target opens it and
	 *   closes any previously-open target; hovering a non-target (or blank
	 *   space) instantly closes whatever was open.
	 * - Hovering within the currently-open target's subtree keeps it open
	 *   (so moving from a parent into its cascade doesn't blink).
	 * - Only when the cursor actually leaves $root do we wait
	 *   HOVER_CLOSE_DELAY before collapsing — that's the forgiveness window
	 *   for accidental off-menu overshoots. Re-entering $root during the
	 *   delay cancels the close.
	 *
	 * @param {jQuery} $root           Bounds of the menu subtree.
	 * @param {string} targetSelector  Items that can hold the open class.
	 * @param {string} className       Class toggled to open/close.
	 */
	function bindDelayedHover( $root, targetSelector, className ) {
		var timer  = null;
		var openEl = null;

		function clearTimer() {
			if ( timer ) {
				clearTimeout( timer );
				timer = null;
			}
		}

		function closeNow() {
			clearTimer();
			if ( openEl ) {
				openEl.classList.remove( className );
				openEl = null;
			}
		}

		function openTarget( el ) {
			clearTimer();
			if ( openEl && openEl !== el ) {
				openEl.classList.remove( className );
			}
			el.classList.add( className );
			openEl = el;
		}

		function scheduleClose() {
			if ( timer || ! openEl ) {
				return;
			}
			timer = setTimeout( function () {
				if ( openEl ) {
					openEl.classList.remove( className );
					openEl = null;
				}
				timer = null;
			}, HOVER_CLOSE_DELAY );
		}

		$root.on( 'mouseover.wcnavv2delay', function ( e ) {
			clearTimer(); // cursor is inside $root somewhere
			var target = e.target.closest ? e.target.closest( targetSelector ) : null;

			if ( target && $root[ 0 ].contains( target ) ) {
				if ( openEl !== target ) {
					openTarget( target );
				}
				return;
			}

			// Not on a target. If hovering something outside the open target's
			// subtree, close immediately.
			if ( openEl && ! openEl.contains( e.target ) ) {
				closeNow();
			}
		} );

		$root.on( 'mouseleave.wcnavv2delay', function () {
			scheduleClose();
		} );
	}

	/**
	 * Canonicalize either a rendered-in-DOM href or a toAdminUrl() output to
	 * the same form (an `admin.php?page=…` or `edit.php?…` fragment with no
	 * protocol, host, or leading `/wp-admin/`). Also decodes `&#038;` / `&amp;`
	 * that esc_url emits on rendered hrefs.
	 */
	function canonicalUrl( href ) {
		if ( ! href ) {
			return '';
		}
		return href
			.replace( /^https?:\/\/[^/]+/, '' )
			.replace( /^\/+wp-admin\//, '' )
			.replace( /&#038;/g, '&' )
			.replace( /&amp;/g, '&' );
	}

	/**
	 * On non-Woo pages the native flyout shows our curated first-level items.
	 * For entries that have grandchildren in the tree, inject a second-level
	 * cascade <ul> so hovering Settings / Marketing / etc. reveals their
	 * sub-items.
	 */
	function injectNativeCascade() {
		var tree = window.wcNavV2Config.tree;
		if ( ! tree ) {
			return;
		}

		var byParent = buildByParent( tree );

		// Build a canonical-URL → tree-slug map (unchanged from prior version).
		var urlToSlug = {};
		Object.keys( tree ).forEach( function ( slug ) {
			var target      = tree[ slug ].url || slug;
			var key         = canonicalUrl( toAdminUrl( target ) );
			var existing    = urlToSlug[ key ];
			var thisHasKids = ( ( byParent[ slug ] || [] ).length ) > 0;
			var prevHasKids = existing && ( ( byParent[ existing ] || [] ).length ) > 0;
			if ( ! existing || ( thisHasKids && ! prevHasKids ) ) {
				urlToSlug[ key ] = slug;
			}
		} );

		// Map a rail-root <li>'s DOM id to its tree slug. Used below to
		// veto cascade injections whose resolved treeSlug is the same as
		// the containing rail root (would duplicate the rail's own
		// flyout — happens when a child's URL collides with the
		// rail-root's `url` override, e.g. Marketing's Overview row
		// resolves back to `woocommerce-marketing`).
		var rootIdToSlug = {};
		Object.keys( tree ).forEach( function ( slug ) {
			if ( tree[ slug ].parent !== 'woocommerce' ) {
				return;
			}
			var cssSlug = slug.replace( /[^A-Za-z0-9_-]/g, '-' );
			rootIdToSlug[ 'toplevel_page_' + cssSlug ] = slug;
		} );

		// Find every rail-root flyout: any #adminmenu top-level <li> whose id
		// starts with `toplevel_page_` and has a `.wp-submenu`. This covers both
		// the legacy single-Woo-rail-item case (non-Woo pages) and the new
		// native-multi-root rail (Woo pages).
		var $rootSubmenuItems = $( '#adminmenu > li.menu-top[id^="toplevel_page_"] > .wp-submenu > li' )
			.not( '.wp-submenu-head' );

		$rootSubmenuItems.each( function () {
			var $li  = $( this );
			var $a   = $li.find( '> a' ).first();
			var href = canonicalUrl( $a.attr( 'href' ) || '' );

			var treeSlug = urlToSlug[ href ];
			if ( ! treeSlug ) {
				return;
			}

			// Skip when the row resolves to its own containing rail-root's
			// tree slug. The cascade's job is to inject grandkids; when
			// the resolved treeSlug IS the rail root, byParent[treeSlug]
			// is the rail's already-rendered children — injecting them
			// produces a side-by-side duplicate flyout. (Happens when
			// `urlToSlug` collides: rail-root has a `url` override that
			// equals a leaf node's URL, e.g. Marketing's Overview row.)
			var rootId   = $li.closest( 'li.menu-top' ).attr( 'id' ) || '';
			var rootSlug = rootIdToSlug[ rootId ];
			if ( rootSlug && treeSlug === rootSlug ) {
				return;
			}

			var grandkids = byParent[ treeSlug ];
			if ( ! grandkids || ! grandkids.length ) {
				return;
			}

			if ( $li.find( '> .wc-nav-v2-subflyout' ).length ) {
				return;
			}
			$li.addClass( 'wc-nav-v2-has-subflyout' );
			var $nested = $( '<ul class="wp-submenu wc-nav-v2-subflyout"></ul>' );
			grandkids.forEach( function ( kid ) {
				if ( kid.hidden ) {
					return;
				}
				$nested.append(
					$( '<li></li>' ).append(
						$( '<a></a>' )
							.attr( 'href', toAdminUrl( kid.url || kid.slug ) )
							.text( kid.title )
					)
				);
			} );
			$li.append( $nested );
		} );

		// Apply the existing hover behaviour to every rail-root flyout, not
		// just woocommerce.
		$( '#adminmenu > li.menu-top[id^="toplevel_page_"]:has(.wc-nav-v2-has-subflyout)' ).each( function () {
			var $root = $( this );
			// Remove WP's native hoverIntent so our 600ms close-delay is the
			// sole handler for these items. On non-Woo pages this loop only
			// visits #toplevel_page_woocommerce (the only item with cascade
			// children), so other top-level items and third-party plugin
			// hover bindings are left untouched.
			$root.off( 'mouseenter mouseleave mouseover mouseout' );
			bindDelayedHover(
				$( '#adminmenu' ),
				'#' + $root.attr( 'id' ),
				'opensub'
			);
			bindDelayedHover(
				$root,
				'.wp-submenu li.wc-nav-v2-has-subflyout',
				'wc-nav-v2-subopen'
			);
		} );

		// Signal that JS hover management is in place so the CSS fallback
		// (:hover immediate show) can be suppressed in favour of the 600ms
		// close-delay path.
		document.getElementById( 'adminmenu' ) &&
			document.getElementById( 'adminmenu' ).classList.add( 'wc-nav-v2-js-ready' );
	}

	$( function () {
		if ( ! window.wcNavV2Config ) {
			return;
		}

		var isWooPage = window.wcNavV2Config.isWooPage === '1';
		try {
			injectNativeCascade();
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'navigation_v2: cascade injection failed', err );
		}

		// Tracks — clicks. The rail is now native WP markup either way; scope
		// to the woocommerce-named entries on non-Woo pages and to all rail
		// items on Woo pages.
		var clickScope = isWooPage ? '#adminmenu a' : '#toplevel_page_woocommerce a';
		$( document ).on( 'click.wcnavv2', clickScope, function () {
			var $a      = $( this );
			var href    = $a.attr( 'href' ) || '';
			var depth   = $a.parents( 'li.wp-has-submenu' ).length;
			var surface = isWooPage ? 'rail' : 'hover';
			tracks( 'navigation_v2_item_clicked', { href: href, depth: depth, surface: surface } );
		} );

		// Tracks — back link. Only on Woo pages, where the splicer has relabeled
		// the native Dashboard entry to serve as the rail's back link.
		if ( isWooPage ) {
			$( document ).on( 'click.wcnavv2', '#adminmenu > li > a[href$="index.php"]', function () {
				tracks( 'navigation_v2_back_clicked' );
			} );
		}

		// wc-admin's React router runs `window.wpNavMenuClassChange( page, url )`
		// on every navigation. That function strips
		// `wp-has-current-submenu`/`wp-menu-open` from every menu item and then
		// re-applies them to `#<page.wpOpenMenu>` — a per-route ID hard-coded
		// in controller.js that points at the pre-nav-v2 top-level slugs the
		// splicer hides (e.g. `toplevel_page_woocommerce`). On nav-v2 the
		// active rail-root LI ends up stripped of its in-rail expansion
		// classes after every navigation.
		//
		// Wrap the function so that after the controller runs we:
		// 1. Determine the correct rail root for the new URL (not the stale
		//    PHP-set wc-nav-v2-current-root which was only right on page load).
		// 2. Re-apply expansion classes to that root and update the marker.
		// 3. Mark the correct sub-item as `current`.
		if ( typeof window.wpNavMenuClassChange === 'function' ) {
			var origClassChange = window.wpNavMenuClassChange;
			window.wpNavMenuClassChange = function () {
				var result = origClassChange.apply( this, arguments );
				var url    = arguments[ 1 ] || '';

				// --- Step 1: find the correct rail root for the new URL --- //
				var newRootEl = null;
				var tree = window.wcNavV2Config && window.wcNavV2Config.tree;

				if ( url && url !== '/' && tree ) {
					var pathKey = 'path=' + url;

					// Primary: search tree for a slug whose slug or `url` field
					// contains the path fragment, then walk up to the rail root
					// (the ancestor whose parent === 'woocommerce').
					Object.keys( tree ).forEach( function ( slug ) {
						if ( newRootEl ) {
							return;
						}
						var item    = tree[ slug ];
						var itemUrl = item.url || slug;
						if ( itemUrl.indexOf( pathKey ) === -1 ) {
							return;
						}
						// Walk up to the rail root.
						var cur   = slug;
						var steps = 0;
						while ( tree[ cur ] && tree[ cur ].parent && tree[ cur ].parent !== 'woocommerce' && steps < 10 ) {
							cur = tree[ cur ].parent;
							steps++;
						}
						var railSlug = ( tree[ cur ] && tree[ cur ].parent === 'woocommerce' ) ? cur : null;
						if ( railSlug ) {
							var cssSlug = railSlug.replace( /[^A-Za-z0-9_-]/g, '-' );
							newRootEl = document.getElementById( 'toplevel_page_' + cssSlug );
						}
					} );

					// Fallback: scan submenu links in the DOM (covers analytics
					// sub-items and other paths not directly in the tree).
					if ( ! newRootEl ) {
						var pathEnc = 'path=' + encodeURIComponent( url );
						$( '#adminmenu li.menu-top[id^="toplevel_page_"]' ).each( function () {
							var found = $( this ).find( '.wp-submenu a' ).filter( function () {
								var h = canonicalUrl( $( this ).attr( 'href' ) || '' );
								return h.indexOf( pathKey ) !== -1 || h.indexOf( pathEnc ) !== -1;
							} ).length > 0;
							if ( found ) {
								newRootEl = this;
								return false;
							}
						} );
					}
				}

				// --- Step 2: apply expansion classes to the correct root --- //
				var markedRoot = document.querySelector( '#adminmenu .wc-nav-v2-current-root' );
				if ( newRootEl ) {
					// Move the marker class when the section changed.
					if ( markedRoot && markedRoot !== newRootEl ) {
						markedRoot.classList.remove(
							'wc-nav-v2-current-root',
							'wp-has-current-submenu',
							'wp-menu-open'
						);
						markedRoot.classList.add( 'wp-not-current-submenu' );
					}
					newRootEl.classList.remove( 'wp-not-current-submenu' );
					newRootEl.classList.add(
						'wc-nav-v2-current-root',
						'wp-has-current-submenu',
						'wp-menu-open'
					);
				} else if ( markedRoot ) {
					// No URL match (e.g. non-path-based page): keep the current root.
					markedRoot.classList.remove( 'wp-not-current-submenu' );
					markedRoot.classList.add( 'wp-has-current-submenu', 'wp-menu-open' );
				}

				// --- Step 3: mark the correct sub-item as current --- //
				// controller.js step 4 selector uses encodeURIComponent() which
				// produces %2F-encoded slashes, but PHP renders hrefs with literal
				// slashes — match both forms.
				if ( url && url !== '/' ) {
					var subLit = 'page=wc-admin&path=' + url;
					var subEnc = 'page=wc-admin&path=' + encodeURIComponent( url );
					$( '#adminmenu .wp-submenu li' ).each( function () {
						var href = canonicalUrl( $( this ).find( '> a' ).attr( 'href' ) || '' );
						if ( href.indexOf( subLit ) !== -1 || href.indexOf( subEnc ) !== -1 ) {
							$( this ).addClass( 'current' );
							return false;
						}
					} );
				}

				return result;
			};
		}
	} );
} )( jQuery );
