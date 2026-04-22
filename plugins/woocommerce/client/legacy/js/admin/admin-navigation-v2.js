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
			var d = decomposeSlug( slug );
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

		var $li = $( '<li></li>' ).addClass( liClasses.join( ' ' ) );
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
		// WP's expected behaviour: add/remove `opensub` on hover and when
		// focus enters/leaves the flyout. admin-menu.css keys off `opensub`
		// to show the flyout.
		$adminmenu
			.on( 'mouseenter.wcnavv2', '> li.wp-has-submenu, > li.wp-has-current-submenu', function () {
				$( this ).addClass( 'opensub' );
			} )
			.on( 'mouseleave.wcnavv2', '> li.wp-has-submenu, > li.wp-has-current-submenu', function () {
				$( this ).removeClass( 'opensub' );
			} )
			.on( 'focus.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).addClass( 'opensub' );
			} )
			.on( 'blur.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).removeClass( 'opensub' );
			} );
	}

	/**
	 * On non-Woo pages the native flyout shows our curated first-level items.
	 * For entries that have grandchildren in the tree, inject a second-level
	 * cascade <ul> so hovering Settings reveals Payments / WooPayments / Status.
	 */
	function injectNativeCascade() {
		var tree = window.wcNavV2Config.tree;
		if ( ! tree ) {
			return;
		}

		var byParent = buildByParent( tree );

		var $items = $( '#toplevel_page_woocommerce > .wp-submenu > li' ).not( '.wp-submenu-head' );
		$items.each( function () {
			var $li  = $( this );
			var $a   = $li.find( '> a' ).first();
			var href = $a.attr( 'href' ) || '';
			var slug = href.replace( /^[^?#]*\/wp-admin\//, '' ).replace( /#.*$/, '' );
			slug     = slug.replace( /^admin\.php\?page=/, '' );
			var candidates = [ slug, slug.replace( /&amp;/g, '&' ) ];

			var grandkids = null;
			for ( var i = 0; i < candidates.length && ! grandkids; i++ ) {
				if ( byParent[ candidates[ i ] ] ) {
					grandkids = byParent[ candidates[ i ] ];
				}
			}
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
	}

	$( function () {
		if ( ! window.wcNavV2Config ) {
			return;
		}

		var isWooPage = window.wcNavV2Config.isWooPage === '1';
		if ( isWooPage ) {
			injectWooRail();
		} else {
			injectNativeCascade();
		}

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
