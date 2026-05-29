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

		// WP-rail overlay ─────────────────────────────────────────────────────
		// Intercept the back (←) link on Woo pages. Instead of navigating to
		// the Dashboard, slide in a panel showing the original WordPress
		// navigation so the user can jump to any WP section without a page load.
		if ( isWooPage ) {
			var $wpRail    = $( '#wc-nav-v2-wp-rail' );
			var $body      = $( 'body' );
			var $adminmenu = $( '#adminmenu' );

			if ( $wpRail.length ) {
				// Size and position the panel to match #adminmenuwrap's viewport
				// bounds. The panel is position:fixed so it slides independently
				// of #adminmenuwrap — no overflow:hidden needed (and it would be
				// harmful: #adminmenuwrap is position:relative, so overflow:hidden
				// clips the position:absolute WC cascade flyouts inside it).
				var wrapEl    = document.getElementById( 'adminmenuwrap' );
				var wrapRect  = wrapEl.getBoundingClientRect();
				var railLeft  = wrapRect.left;             // On-screen position.
				var railHide  = railLeft - wrapRect.width; // Off-screen to the left.

				// Set exact dimensions and start hidden (off-screen left).
				// We animate via `left` not `transform` so position:fixed flyout
				// children remain viewport-relative (a transform on the panel
				// would create a new fixed-positioning containing block).
				$wpRail.css( {
					top:   wrapRect.top + 'px',
					left:  railHide + 'px',
					width: wrapRect.width + 'px',
				} );

				// Copy background and link colors from #adminmenu so the panel
				// matches the active WP admin color scheme without hardcoding.
				var menuBg = window.getComputedStyle( $adminmenu[ 0 ] ).backgroundColor;
				if ( menuBg && menuBg !== 'rgba(0, 0, 0, 0)' && menuBg !== 'transparent' ) {
					$wpRail[ 0 ].style.backgroundColor = menuBg;
				}
				// Top-level link color (inherited as panel default).
				var menuLink = document.querySelector( '#adminmenu > li > a' );
				if ( menuLink ) {
					var linkColor = window.getComputedStyle( menuLink ).color;
					if ( linkColor ) {
						$wpRail[ 0 ].style.color = linkColor;
					}
				}
				// Submenu link color — often different (lighter/gray) from the
				// top-level color in custom schemes. Expose as a CSS custom
				// property so .wp-submenu a can use it independently.
				//
				// Skip .current items: on Woo pages the first .wp-submenu a is
				// the active rail item (e.g. "Home"), which is styled white in
				// most schemes. Using that white as the panel's submenu color
				// makes flyout text invisible on light backgrounds.
				var subLink = document.querySelector( '#adminmenu .wp-submenu li:not(.current) > a' )
					|| document.querySelector( '#adminmenu .wp-submenu a' );
				if ( subLink ) {
					var subColor = window.getComputedStyle( subLink ).color;
					if ( subColor ) {
						$wpRail[ 0 ].style.setProperty( '--wc-rail-submenu-color', subColor );
					}
				}

				// Flyout submenu background — copy from #adminmenu .wp-submenu so
				// the position:fixed flyout isn't transparent over the page content.
				// Stored here in the outer closure and applied per-flyout in openFlyout().
				var subMenuBg = '';
				var subMenuEl = document.querySelector( '#adminmenu .wp-submenu' );
				if ( subMenuEl ) {
					var subBg = window.getComputedStyle( subMenuEl ).backgroundColor;
					if ( subBg && subBg !== 'rgba(0, 0, 0, 0)' && subBg !== 'transparent' ) {
						subMenuBg = subBg;
					}
				}

				// Hover background/text — derive from the active rail root (the
				// current Woo section), which WordPress paints with the active
				// color scheme's highlight. Reading its computed style is
				// scheme-accurate and available synchronously here at DOM-ready, so
				// the panel always inherits the scheme. The previous approach
				// scanned the color-scheme stylesheet on window.load, which fired
				// unreliably and left the panel on the hardcoded CSS fallback —
				// a wrong, scheme-mismatched blue.
				var currentRootLi = document.querySelector( '#adminmenu li.wc-nav-v2-current-root' )
					|| document.querySelector( '#adminmenu li.menu-top.current' )
					|| document.querySelector( '#adminmenu li.menu-top.wp-has-current-submenu' );
				if ( currentRootLi ) {
					var currentRootA = currentRootLi.querySelector( 'a.menu-top' ) || currentRootLi;
					var rootStyle    = window.getComputedStyle( currentRootA );
					var hoverBg      = rootStyle.backgroundColor;
					if ( ! hoverBg || hoverBg === 'rgba(0, 0, 0, 0)' || hoverBg === 'transparent' ) {
						hoverBg = window.getComputedStyle( currentRootLi ).backgroundColor;
					}
					if ( hoverBg && hoverBg !== 'rgba(0, 0, 0, 0)' && hoverBg !== 'transparent' ) {
						$wpRail[ 0 ].style.setProperty( '--wc-rail-hover-bg', hoverBg );
					}
					if ( rootStyle.color ) {
						$wpRail[ 0 ].style.setProperty( '--wc-rail-hover-color', rootStyle.color );
					}
				}

				// Icon mirror — some plugins (e.g. Code Snippets) register their
				// admin menu icon via CSS rules scoped to
				// `#adminmenu .toplevel_page_<slug> .wp-menu-image:before`.
				// Our panel is `#wc-nav-v2-wp-rail`, not `#adminmenu`, so those
				// rules never fire. Scan all stylesheets, rewrite any matching
				// rule to target `#wc-nav-v2-wp-rail #toplevel_page_<slug>`,
				// resolve relative SVG URLs to absolute, and inject a <style>.
				( function () {
					var injected = [];
					for ( var si = 0; si < document.styleSheets.length; si++ ) {
						try {
							var sheet     = document.styleSheets[ si ];
							var sheetBase = sheet.href
								? sheet.href.substring( 0, sheet.href.lastIndexOf( '/' ) + 1 )
								: '';
							var sheetRules = sheet.cssRules;
							for ( var ri = 0; ri < sheetRules.length; ri++ ) {
								var rule = sheetRules[ ri ];
								var sel  = rule.selectorText || '';
								if ( sel.indexOf( '#adminmenu' ) === -1 ||
									sel.indexOf( '.toplevel_page_' ) === -1 ||
									sel.indexOf( 'wp-menu-image' ) === -1 ) {
									continue;
								}
								var newSel = sel.replace(
									/#adminmenu\s+\.toplevel_page_/g,
									'#wc-nav-v2-wp-rail #toplevel_page_'
								);
								if ( newSel === sel ) {
									continue;
								}
								// Resolve relative asset URLs (e.g. mask SVGs) to
								// absolute so the injected rule loads from the
								// original plugin path, not the document root.
								var cssText = rule.style.cssText;
								if ( sheetBase ) {
									cssText = cssText.replace(
										/url\(\s*['"]?(?!data:|https?:\/\/|\/)(.*?)['"]?\s*\)/g,
										function ( m, p ) {
											return 'url("' + sheetBase + p + '")';
										}
									);
								}
								injected.push( newSel + '{' + cssText + '}' );
							}
						} catch ( e ) {}
					}
					if ( injected.length ) {
						var st    = document.createElement( 'style' );
						st.id    = 'wc-nav-v2-rail-icon-mirror';
						st.textContent = injected.join( '\n' );
						document.head.appendChild( st );
					}
				}() );

				// Flyout hover for top-level items. We can't use bindDelayedHover
				// directly because flyouts must be position:fixed (to escape any
				// overflow constraints on the panel) and positioned dynamically
				// from each item's viewport rect. We implement the same 600ms
				// close-delay semantics manually.
				( function () {
					var flyoutTimer = null;
					var openLi      = null;

					function closeFlyout() {
						if ( flyoutTimer ) {
							clearTimeout( flyoutTimer );
							flyoutTimer = null;
						}
						if ( openLi ) {
							$( openLi ).find( '> .wp-submenu' ).css( 'display', 'none' );
							$( openLi ).removeClass( 'opensub' );
							openLi = null;
						}
					}

					function openFlyout( li ) {
						if ( flyoutTimer ) {
							clearTimeout( flyoutTimer );
							flyoutTimer = null;
						}
						if ( openLi && openLi !== li ) {
							$( openLi ).find( '> .wp-submenu' ).css( 'display', 'none' );
							$( openLi ).removeClass( 'opensub' );
						}
						if ( openLi !== li ) {
							// Position the flyout as position:fixed to the right of the
							// sidebar, vertically aligned with the hovered item. We set
							// display:block explicitly rather than relying on the CSS cascade
							// (WP's .wp-submenu hide/show is scoped to #adminmenu and doesn't
							// reach our panel).
							var $sub   = $( li ).find( '> .wp-submenu' );
							var liRect = li.getBoundingClientRect();
							var flyoutCss = {
								display:     'block',
								position:    'fixed',
								left:        ( wrapRect.left + wrapRect.width ) + 'px',
								top:         liRect.top + 'px',
								'min-width': '185px',
								'z-index':   '100000',
							};
							if ( subMenuBg ) {
								flyoutCss.background = subMenuBg;
							}
							$sub.css( flyoutCss );
							$( li ).addClass( 'opensub' );
							openLi = li;
						}
					}

					$wpRail.on( 'mouseover.wcnavv2wprail', function ( e ) {
						var li = e.target.closest ? e.target.closest( 'li.wp-has-submenu' ) : null;
						if ( li && $wpRail[ 0 ].contains( li ) ) {
							openFlyout( li );
						} else if ( openLi && ! openLi.contains( e.target ) ) {
							closeFlyout();
						}
					} );

					$wpRail.on( 'mouseleave.wcnavv2wprail', function () {
						if ( ! flyoutTimer && openLi ) {
							flyoutTimer = setTimeout( function () {
								closeFlyout();
								flyoutTimer = null;
							}, HOVER_CLOSE_DELAY );
						}
					} );

					// Keep the flyout open when the cursor moves into it. The flyout
					// is position:fixed so it visually leaves $wpRail's bounds, meaning
					// mouseover on $wpRail won't re-fire. We listen at the flyout level
					// via delegation on the fixed element itself.
					$wpRail.on( 'mouseover.wcnavv2wprail', '.wp-submenu', function () {
						if ( flyoutTimer ) {
							clearTimeout( flyoutTimer );
							flyoutTimer = null;
						}
					} );
					$wpRail.on( 'mouseleave.wcnavv2wprail', '.wp-submenu', function () {
						if ( ! flyoutTimer && openLi ) {
							flyoutTimer = setTimeout( function () {
								closeFlyout();
								flyoutTimer = null;
							}, HOVER_CLOSE_DELAY );
						}
					} );
				}() );

				function openWpRail() {
					$wpRail.css( 'left', railLeft + 'px' );
					$body.addClass( 'wc-nav-v2-showing-wp-rail' );
					$wpRail.attr( 'aria-hidden', 'false' );
				}

				function closeWpRail() {
					$wpRail.css( 'left', railHide + 'px' );
					$body.removeClass( 'wc-nav-v2-showing-wp-rail' );
					$wpRail.attr( 'aria-hidden', 'true' );
				}

				// Intercept the back link — show the WP rail instead of navigating.
				$( document ).on( 'click.wcnavv2wprail', '#adminmenu > li > a[href$="index.php"]', function ( e ) {
					e.preventDefault();
					e.stopImmediatePropagation();
					openWpRail();
				} );

				// Clicking the WooCommerce entry inside the WP rail just dismisses
				// the overlay (user is already on a Woo page; no navigation needed).
				$wpRail.on( 'click.wcnavv2wprail', '#wc-wp-item-woocommerce > a', function ( e ) {
					e.preventDefault();
					closeWpRail();
				} );

				// Dismiss when clicking outside #adminmenuwrap. The .contains()
				// check uses the DOM tree so flyout ULs (children of items inside
				// the wrapper) correctly count as "inside" even though they
				// render visually to the right of the sidebar.
				$( document ).on( 'click.wcnavv2wprail', function ( e ) {
					if ( ! $body.hasClass( 'wc-nav-v2-showing-wp-rail' ) ) {
						return;
					}
					var wrap = document.getElementById( 'adminmenuwrap' );
					if ( wrap && ! wrap.contains( e.target ) ) {
						closeWpRail();
					}
				} );

				// Dismiss on Escape and return focus to the back link.
				$( document ).on( 'keydown.wcnavv2wprail', function ( e ) {
					if ( e.key === 'Escape' && $body.hasClass( 'wc-nav-v2-showing-wp-rail' ) ) {
						closeWpRail();
						$( '#adminmenu > li > a[href$="index.php"]' ).focus();
					}
				} );
			}
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
