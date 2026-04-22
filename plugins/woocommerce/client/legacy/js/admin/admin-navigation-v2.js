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

	function currentSlug() {
		var params = new URLSearchParams( window.location.search );
		var page   = params.get( 'page' ) || '';
		var path   = params.get( 'path' ) || '';
		if ( page && path ) {
			return page + '&path=' + path;
		}
		return page;
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
	 */
	function buildRailItem( node, byParent, current ) {
		var kids       = byParent[ node.slug ] || [];
		var icon       = node.icon || 'dashicons-admin-generic';
		var isCurrent  = node.slug === current;
		var hasKids    = kids.length > 0;

		var liClasses = [ 'menu-top', 'menu-icon-' + cssSlug( node.slug ), 'wc-nav-v2-item' ];
		var aClasses  = [ 'menu-top' ];

		if ( isCurrent ) {
			liClasses.push( 'current', 'wp-has-current-submenu', 'wp-menu-open' );
			aClasses.push( 'current', 'wp-has-current-submenu' );
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
			$( '<div></div>' ).addClass( 'wp-menu-name' ).text( 'WordPress' )
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

		var byParent = buildByParent( tree );
		var current  = currentSlug();
		var roots    = byParent.woocommerce || [];

		// Preserve the collapse-menu button at the end if present.
		var $collapse = $adminmenu.find( '#collapse-menu' ).detach();

		$adminmenu.empty();
		$adminmenu.append( buildBackItem() );
		roots.forEach( function ( node ) {
			$adminmenu.append( buildRailItem( node, byParent, current ) );
		} );

		// Mark last rail item so WP styling (.menu-top-last) applies.
		$adminmenu.find( '> li.wc-nav-v2-item' ).last().addClass( 'menu-top-last' );

		if ( $collapse.length ) {
			$adminmenu.append( $collapse );
		}
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
