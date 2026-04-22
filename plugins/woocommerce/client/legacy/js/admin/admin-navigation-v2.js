/* global wcNavV2Config, jQuery */
/**
 * Nested admin navigation v2.
 *
 * Two surfaces, one shared DOM:
 *
 * - On non-Woo pages: WP's native rail shows a single `WooCommerce` item.
 *   Hovering it opens the native flyout — we inject a second-level cascade
 *   into first-level flyout items that have grandchildren in the tree
 *   (Settings → Payments / WooPayments / Status, etc.).
 *
 * - On Woo pages: we REPLACE the contents of #adminmenu with our tree's
 *   top-level items (plus a `← WordPress` back link at the top). Because
 *   we re-use WP's native markup (menu-top / wp-menu-image / wp-menu-name /
 *   wp-submenu), the native admin-menu.css styles apply automatically —
 *   fonts, icons, hover, current-page highlighting, color schemes, RTL,
 *   folded mode all come for free.
 *
 * - Tracks: emit the design-spec events on hover / click / back.
 */
( function ( $ ) {
	'use strict';

	var tracks = ( window.wcTracks && window.wcTracks.recordEvent ) || function () {};

	function cssSlug( slug ) {
		return ( slug || 'generic' ).replace( /[^A-Za-z0-9_-]/g, '-' );
	}

	function toAdminUrl( target ) {
		if ( ! target ) {
			return '#';
		}
		if ( target.indexOf( '?' ) >= 0 ) {
			return '/wp-admin/' + target;
		}
		return '/wp-admin/admin.php?page=' + target;
	}

	/**
	 * Split a tree slug into ( pagenow, params ). Mirrors Context::decompose_slug().
	 */
	function decomposeSlug( slug ) {
		var path, query;
		if ( slug.indexOf( '?' ) >= 0 ) {
			var parts = slug.split( '?' );
			path  = parts[ 0 ];
			query = parts.slice( 1 ).join( '?' );
		} else {
			path  = 'admin.php';
			query = 'page=' + slug;
		}
		var params = {};
		query.split( '&' ).forEach( function ( pair ) {
			if ( ! pair ) { return; }
			var kv = pair.split( '=' );
			params[ decodeURIComponent( kv[ 0 ] ) ] = kv.length > 1 ? decodeURIComponent( kv.slice( 1 ).join( '=' ) ) : '';
		} );
		return { path: path, params: params };
	}

	/**
	 * Find the tree slug whose (pagenow, params) expectations are all
	 * satisfied by the current request. Most-specific match wins.
	 */
	function currentSlug( tree ) {
		var pagenow = window.location.pathname.replace( /^.*\//, '' ) || 'admin.php';
		var current = {};
		new URLSearchParams( window.location.search ).forEach( function ( v, k ) {
			current[ k ] = v;
		} );

		var best       = null;
		var bestSpecs  = -1;
		Object.keys( tree ).forEach( function ( slug ) {
			if ( ! tree[ slug ].parent ) {
				return;
			}
			// Decompose against the URL override when one is declared (e.g.
			// `action-scheduler` slug → `tools.php?page=action-scheduler` URL),
			// so the path match works for pages that don't live at admin.php.
			var target = tree[ slug ].url || slug;
			var d = decomposeSlug( target );
			if ( d.path !== pagenow ) {
				return;
			}
			var keys    = Object.keys( d.params );
			var matched = keys.every( function ( k ) {
				return current[ k ] !== undefined && String( current[ k ] ) === String( d.params[ k ] );
			} );
			if ( ! matched ) {
				return;
			}
			if ( keys.length > bestSpecs ) {
				best      = slug;
				bestSpecs = keys.length;
			}
		} );
		return best;
	}

	/**
	 * Walk the parent chain starting from `slug` and return a Set of all
	 * ancestor slugs (inclusive of `slug` itself).
	 */
	function ancestorSet( tree, slug ) {
		var set = new Set();
		var walk = slug;
		while ( walk && tree[ walk ] ) {
			set.add( walk );
			walk = tree[ walk ].parent;
		}
		return set;
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
	 * Build a WP-native rail <li> for one tree node. Returns a jQuery element.
	 *
	 * @param node      Tree node (with .slug added).
	 * @param byParent  Parent-indexed children map.
	 * @param current   Current-page slug or null.
	 * @param ancestors Set of slugs on the current-page chain.
	 */
	function buildRailItem( node, byParent, current, ancestors ) {
		var kids         = byParent[ node.slug ] || [];
		var icon         = node.icon || 'dashicons-admin-generic';
		var isCurrent    = node.slug === current;
		var hasCurrent   = ! isCurrent && ancestors.has( node.slug );
		var hasKids      = kids.length > 0;

		var liClasses = [ 'menu-top', 'menu-icon-' + cssSlug( node.slug ), 'wc-nav-v2-item' ];
		var aClasses  = [ 'menu-top' ];

		if ( isCurrent || hasCurrent ) {
			liClasses.push( 'wp-has-current-submenu', 'wp-menu-open' );
			aClasses.push( 'wp-has-current-submenu' );
			if ( isCurrent ) {
				liClasses.push( 'current' );
				aClasses.push( 'current' );
			}
		} else if ( hasKids ) {
			liClasses.push( 'wp-has-submenu', 'wp-not-current-submenu' );
			aClasses.push( 'wp-has-submenu', 'wp-not-current-submenu' );
		}

		// wc-admin's React router calls wpNavMenuClassChange( page, url ) when
		// routing between pages and queries `#<wpOpenMenu>` to highlight the
		// active top-level. Each wc-admin page declares a `wpOpenMenu` like
		// `toplevel_page_woocommerce-marketing`. Give our rail items the same
		// WP-native `toplevel_page_<slug>` id so those lookups succeed and
		// wc-admin's highlighting works across navigation.
		var liId = 'toplevel_page_' + cssSlug( node.slug );

		var $li = $( '<li></li>' )
			.attr( 'id', liId )
			.addClass( liClasses.join( ' ' ) );
		var $a  = $( '<a></a>' )
			.attr( 'href', toAdminUrl( node.url || node.slug ) )
			.addClass( aClasses.join( ' ' ) );
		$a.append(
			$( '<div></div>' )
				.addClass( 'wp-menu-image dashicons-before ' + icon )
				.attr( 'aria-hidden', 'true' )
				.append( '<br>' )
		);
		$a.append( $( '<div></div>' ).addClass( 'wp-menu-name' ).text( node.title ) );
		$li.append( $a );

		if ( hasKids ) {
			var $ul = $( '<ul></ul>' ).addClass( 'wp-submenu wp-submenu-wrap' );
			$ul.append(
				$( '<li></li>' )
					.addClass( 'wp-submenu-head' )
					.attr( 'aria-hidden', 'true' )
					.text( node.title )
			);
			kids.forEach( function ( kid, idx ) {
				if ( kid.hidden ) {
					return;
				}
				var kidIsCurrent = kid.slug === current;
				var $kLi         = $( '<li></li>' );
				var $kA          = $( '<a></a>' )
					.attr( 'href', toAdminUrl( kid.url || kid.slug ) )
					.text( kid.title );
				if ( 0 === idx ) {
					$kLi.addClass( 'wp-first-item' );
					$kA.addClass( 'wp-first-item' );
				}
				if ( kidIsCurrent ) {
					$kLi.addClass( 'current' );
					$kA.addClass( 'current' );
				}
				$kLi.append( $kA );
				$ul.append( $kLi );
			} );
			$li.append( $ul );
		}

		return $li;
	}

	/**
	 * Build the back-to-WordPress rail item (first item in the Woo rail).
	 */
	function buildBackItem() {
		var $li = $( '<li></li>' ).addClass(
			'menu-top menu-icon-generic menu-top-first wc-nav-v2-item wc-nav-v2-back-item'
		);
		var $a = $( '<a></a>' )
			.attr( 'href', '/wp-admin/index.php' )
			.attr( 'id', 'wc-nav-v2-back' )
			.addClass( 'menu-top' );
		$a.append(
			$( '<div></div>' )
				.addClass( 'wp-menu-image dashicons-before dashicons-arrow-left-alt' )
				.attr( 'aria-hidden', 'true' )
				.append( '<br>' )
		);
		$a.append(
			$( '<div></div>' ).addClass( 'wp-menu-name' ).text( 'Back' )
		);
		$li.append( $a );
		return $li;
	}

	/**
	 * Replace #adminmenu's contents with our tree on Woo pages.
	 */
	function injectWooRail() {
		var $adminmenu = $( '#adminmenu' );
		if ( ! $adminmenu.length ) {
			return;
		}
		var tree = window.wcNavV2Config.tree;
		if ( ! tree ) {
			return;
		}

		var byParent  = buildByParent( tree );
		var current   = currentSlug( tree );
		var ancestors = ancestorSet( tree, current );
		var roots     = byParent.woocommerce || [];

		// Preserve the collapse-menu button at the end if present.
		var $collapse = $adminmenu.find( '#collapse-menu' ).detach();

		$adminmenu.empty();
		$adminmenu.append( buildBackItem() );
		roots.forEach( function ( node ) {
			$adminmenu.append( buildRailItem( node, byParent, current, ancestors ) );
		} );

		// Mark first/last rail items so WP styling (.menu-top-first/.menu-top-last) applies.
		$adminmenu.find( '> li.wc-nav-v2-item' ).first().addClass( 'menu-top-first' );
		$adminmenu.find( '> li.wc-nav-v2-item' ).last().addClass( 'menu-top-last' );

		if ( $collapse.length ) {
			$adminmenu.append( $collapse );
		}

		// WP's common.js hoverIntent was bound to the native rail before we
		// replaced it, so our injected items have no hover handler. Rebind
		// WP's expected behaviour: add/remove `opensub` on hover (with a
		// close delay so a small off-menu overshoot doesn't snap the flyout
		// shut) and when focus enters/leaves the flyout.
		bindDelayedHover(
			$adminmenu,
			'> li.wp-has-submenu, > li.wp-has-current-submenu',
			'opensub'
		);
		$adminmenu
			.on( 'focus.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).addClass( 'opensub' );
			} )
			.on( 'blur.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).removeClass( 'opensub' );
			} );
	}

	/**
	 * How long to wait before collapsing a flyout after the cursor leaves it.
	 * Gives users time to overshoot and come back without the menu snapping shut.
	 */
	var HOVER_CLOSE_DELAY = 450;

	/**
	 * Bind mouseenter/mouseleave handlers that toggle `className` on delegated
	 * target items, with a HOVER_CLOSE_DELAY timeout before removing the class.
	 * Re-entering any matched target (or any descendant's flyout) during the
	 * delay cancels the close.
	 *
	 * @param {jQuery} $root           Root element to delegate from.
	 * @param {string} targetSelector  Matched items that can open the flyout.
	 * @param {string} className       Class added to open, removed on close.
	 */
	function bindDelayedHover( $root, targetSelector, className ) {
		var closeTimers = new Map();

		function open( el ) {
			var t = closeTimers.get( el );
			if ( t ) {
				clearTimeout( t );
				closeTimers.delete( el );
			}
			el.classList.add( className );
		}

		function scheduleClose( el ) {
			if ( closeTimers.has( el ) ) {
				return;
			}
			closeTimers.set(
				el,
				setTimeout( function () {
					el.classList.remove( className );
					closeTimers.delete( el );
				}, HOVER_CLOSE_DELAY )
			);
		}

		$root
			.on( 'mouseenter.wcnavv2delay', targetSelector, function () {
				open( this );
			} )
			.on( 'mouseleave.wcnavv2delay', targetSelector, function () {
				scheduleClose( this );
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

		// Build a canonical-URL → tree-slug map. When two tree nodes canonicalize
		// to the same URL (e.g. Marketing parent's `url` override and its
		// Overview child's slug both produce the same URL), prefer the node
		// that has grandchildren so the cascade shows up on the right row.
		var urlToSlug = {};
		Object.keys( tree ).forEach( function ( slug ) {
			var target = tree[ slug ].url || slug;
			var key    = canonicalUrl( toAdminUrl( target ) );
			var existing = urlToSlug[ key ];
			var thisHasKids = ( ( byParent[ slug ] || [] ).length ) > 0;
			var prevHasKids = existing && ( ( byParent[ existing ] || [] ).length ) > 0;
			if ( ! existing || ( thisHasKids && ! prevHasKids ) ) {
				urlToSlug[ key ] = slug;
			}
		} );

		var $items = $( '#toplevel_page_woocommerce > .wp-submenu > li' ).not( '.wp-submenu-head' );
		$items.each( function () {
			var $li  = $( this );
			var $a   = $li.find( '> a' ).first();
			var href = canonicalUrl( $a.attr( 'href' ) || '' );

			var treeSlug = urlToSlug[ href ];
			if ( ! treeSlug ) {
				return;
			}

			var grandkids = byParent[ treeSlug ];
			if ( ! grandkids || ! grandkids.length ) {
				return;
			}

			$li.addClass( 'wc-nav-v2-has-subflyout' );
			var $nested = $( '<ul class="wc-nav-v2-subflyout"></ul>' );
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

		// JS-driven hover open/close on the second-level cascade, with the
		// same HOVER_CLOSE_DELAY forgiveness used by the rail submenus.
		// The SCSS now keys show/hide on `.wc-nav-v2-subopen` (+ :focus-within
		// for keyboard).
		bindDelayedHover(
			$( '#toplevel_page_woocommerce' ),
			'.wp-submenu li.wc-nav-v2-has-subflyout',
			'wc-nav-v2-subopen'
		);
	}

	$( function () {
		if ( ! window.wcNavV2Config ) {
			return;
		}

		var isWooPage = window.wcNavV2Config.isWooPage === '1';
		// Wrap in try/catch so any bug in our rail injection can't take down
		// wc-admin's React app (which also runs at DOM-ready). Errors log to
		// the console but never bubble.
		try {
			if ( isWooPage ) {
				injectWooRail();
			} else {
				injectNativeCascade();
			}
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'navigation_v2: rail injection failed', err );
		}

		// Reveal the rail now that injection has completed (or failed). The
		// SCSS hides #adminmenu on body.wc-nav-v2-active to prevent a flash
		// of the native rail before we replace its contents; adding
		// wc-nav-v2-rail-ready flips visibility back on. Unconditional so a
		// partial failure doesn't leave users staring at an empty rail.
		$( 'body' ).addClass( 'wc-nav-v2-rail-ready' );

		// Tracks — clicks.
		$( '#adminmenu' ).on( 'click.wcnavv2', 'a', function () {
			var $a      = $( this );
			var slug    = $a.attr( 'href' ) || '';
			var depth   = $a.parents( 'li.wp-has-submenu' ).length;
			var surface = isWooPage ? 'rail' : 'hover';
			tracks( 'navigation_v2_item_clicked', { slug: slug, depth: depth, surface: surface } );
		} );

		// Tracks — back link.
		$( document ).on( 'click.wcnavv2', '#wc-nav-v2-back', function () {
			tracks( 'navigation_v2_back_clicked' );
		} );
	} );
} )( jQuery );
