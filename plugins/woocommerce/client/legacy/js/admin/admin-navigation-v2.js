/* global wcNavV2Config, jQuery */
/**
 * Nested admin navigation v2 — rail + cascade behavior.
 *
 * - Rail replacement: toggled via body.wc-nav-v2-active (set server-side on
 *   Woo pages; we trust PHP and just run with what's in the DOM).
 * - Flyout cascade: hover-intent via the `opensub` class on li.wp-has-submenu,
 *   mirroring WP's own common.js behavior. The aliased admin-menu.css then
 *   handles the show/hide visuals.
 * - Keyboard navigation: native WP behavior still applies because we use WP's
 *   class names; we only add depth-aware arrow-right/left handling.
 * - Tracks: emit the 5 events specified in the design doc.
 */
( function ( $ ) {
	'use strict';

	var tracks = ( window.wcTracks && window.wcTracks.recordEvent ) || function () {};

	$( function () {
		if ( ! window.wcNavV2Config ) {
			return;
		}

		// Rail mode is decided server-side.
		if ( window.wcNavV2Config.isWooPage === '1' ) {
			$( 'body' ).addClass( 'wc-nav-v2-active' );
		}

		// -----------------------------------------------------------------------
		// Native-rail cascade: the `woocommerce` top-level in WP's native rail
		// shows our curated first-level items from $submenu['woocommerce'].
		// For first-level items that have grandchildren in the tree, inject a
		// nested <ul> as a second-level cascade. CSS handles show/hide on
		// hover.
		// -----------------------------------------------------------------------
		( function injectNativeCascade() {
			var tree = window.wcNavV2Config.tree;
			if ( ! tree ) {
				return;
			}

			// Build a parent-indexed map once.
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

			// Match visible first-level items in the native Woo flyout against
			// their tree slug, then attach a nested <ul> if grandchildren exist.
			var $items = $( '#toplevel_page_woocommerce > .wp-submenu > li' ).not( '.wp-submenu-head' );
			$items.each( function () {
				var $li = $( this );
				var $a  = $li.find( '> a' ).first();
				var href = $a.attr( 'href' ) || '';
				// Recover the slug: strip admin_url prefix and trailing # fragments.
				var slug = href.replace( /^[^?#]*\/wp-admin\//, '' ).replace( /#.*$/, '' );
				slug = slug.replace( /^admin\.php\?page=/, '' );
				// Normalize common variations.
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
					var kidHref = '/wp-admin/' + (
						kid.slug.indexOf( '?' ) >= 0 || kid.slug.indexOf( '&' ) >= 0
							? ( kid.slug.indexOf( '?' ) >= 0 ? kid.slug : 'admin.php?page=' + kid.slug )
							: 'admin.php?page=' + kid.slug
					);
					$nested.append(
						$( '<li></li>' ).append(
							$( '<a></a>' ).attr( 'href', kidHref ).text( kid.title )
						)
					);
				} );
				$li.append( $nested );
			} );
		} )();

		// -----------------------------------------------------------------------
		// Flyout cascade — hover-intent open/close.
		// -----------------------------------------------------------------------
		var $menu = $( '#wc-nav-v2-adminmenu' );

		$menu.find( 'li.wp-has-submenu' )
			.on( 'mouseenter.wcnavv2', function () {
				var $li = $( this );
				var depth = $li.parents( 'li.wp-has-submenu' ).length;
				$menu.find( 'li.opensub' ).not( $li ).not( $li.parents() ).removeClass( 'opensub' );
				$li.addClass( 'opensub' );
				tracks( 'navigation_v2_hover_opened', { depth_reached: depth + 1 } );
			} )
			.on( 'mouseleave.wcnavv2', function () {
				$( this ).removeClass( 'opensub' );
			} );

		// Keep opensub when focus moves into a submenu (keyboard nav).
		$menu
			.on( 'focus.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).addClass( 'opensub' );
			} )
			.on( 'blur.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).removeClass( 'opensub' );
			} );

		// -----------------------------------------------------------------------
		// Keyboard: Escape closes open flyout; arrow keys move focus.
		// -----------------------------------------------------------------------
		$menu.on( 'keydown.wcnavv2', 'a', function ( e ) {
			var key = e.key;
			if ( key === 'Escape' ) {
				$menu.find( 'li.opensub' ).removeClass( 'opensub' );
				return;
			}
			if ( key === 'ArrowDown' ) {
				e.preventDefault();
				$( this ).closest( 'li' ).next( 'li' ).find( 'a' ).first().focus();
			}
			if ( key === 'ArrowUp' ) {
				e.preventDefault();
				$( this ).closest( 'li' ).prev( 'li' ).find( 'a' ).first().focus();
			}
			if ( key === 'ArrowRight' ) {
				var $submenu = $( this ).closest( 'li' ).find( '> .wp-submenu a' ).first();
				if ( $submenu.length ) {
					e.preventDefault();
					$submenu.focus();
				}
			}
			if ( key === 'ArrowLeft' ) {
				var $parent = $( this ).closest( '.wp-submenu' ).prev( 'a' );
				if ( $parent.length ) {
					e.preventDefault();
					$parent.focus();
				}
			}
		} );

		// -----------------------------------------------------------------------
		// Tracks — leaf clicks.
		// -----------------------------------------------------------------------
		$menu.on( 'click.wcnavv2', 'a', function () {
			var $a      = $( this );
			var slug    = $a.attr( 'href' ) || '';
			var depth   = $a.parents( 'li.wp-has-submenu' ).length;
			var surface = $( 'body' ).hasClass( 'wc-nav-v2-active' ) ? 'rail' : 'hover';
			tracks( 'navigation_v2_item_clicked', { slug: slug, depth: depth, surface: surface } );
		} );

		// Back link.
		$( '#wc-nav-v2-back' ).on( 'click.wcnavv2', function () {
			tracks( 'navigation_v2_back_clicked' );
		} );
	} );
} )( jQuery );
