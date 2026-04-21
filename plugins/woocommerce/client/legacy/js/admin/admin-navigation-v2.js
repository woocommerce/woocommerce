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
