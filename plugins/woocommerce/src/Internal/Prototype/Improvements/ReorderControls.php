<?php
/**
 * ReorderControls prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Hides metabox reorder arrows by default and exposes them via a Screen Options checkbox.
 * State is stored in a cookie so no AJAX or user meta is needed.
 */
class ReorderControls {

	const COOKIE_KEY = 'wc_proto_reorder_controls';

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'reorder_controls' ) ) {
			return;
		}
		add_action( 'admin_init', array( self::class, 'maybe_handle_reset' ) );
		add_filter( 'screen_settings', array( self::class, 'add_screen_option' ), 10, 2 );
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'replace_arrow_buttons' ) );
	}

	/**
	 * Check whether the user has opted in to showing reorder controls via cookie.
	 */
	private static function is_showing(): bool {
		return ! empty( $_COOKIE[ self::COOKIE_KEY ] ) && '1' === $_COOKIE[ self::COOKIE_KEY ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Handle the "Reset to defaults" GET request.
	 * Deletes WordPress screen option user meta (visibility, order, layout) and
	 * our reorder controls cookie, then redirects back to the clean page.
	 */
	public static function maybe_handle_reset(): void {
		if ( ! isset( $_GET['wc-proto-reset-screen'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		check_admin_referer( 'wc-proto-reset-screen' );

		$user_id     = get_current_user_id();
		$screen_id   = sanitize_key( wp_unslash( $_GET['screen_id'] ?? '' ) );
		$screen_base = sanitize_key( wp_unslash( $_GET['screen_base'] ?? '' ) );

		if ( $screen_id ) {
			delete_user_meta( $user_id, 'metaboxhidden_' . $screen_id );
			delete_user_meta( $user_id, 'meta-box-order_' . $screen_id );
			delete_user_meta( $user_id, 'closedpostboxes_' . $screen_id );
		}
		if ( $screen_base ) {
			delete_user_meta( $user_id, 'screen_layout_' . $screen_base );
		}

		setcookie( self::COOKIE_KEY, '', time() - 3600, '/' ); // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions

		wp_safe_redirect( remove_query_arg( array( 'wc-proto-reset-screen', '_wpnonce', 'screen_id', 'screen_base' ) ) );
		exit;
	}

	/**
	 * Add a "Show reorder controls" checkbox to Screen Options.
	 * Also replaces the WordPress-generated description with updated copy and a reset link.
	 *
	 * @param string     $settings Existing screen settings HTML.
	 * @param \WP_Screen $screen   Current screen.
	 * @return string
	 */
	public static function add_screen_option( string $settings, \WP_Screen $screen ): string {
		if ( ! DevPanel::is_supported_screen() ) {
			return $settings;
		}

		$checked    = self::is_showing() ? 'checked' : '';
		$cookie_key = self::COOKIE_KEY;
		$reset_url  = esc_url(
			wp_nonce_url(
				add_query_arg(
					array(
						'wc-proto-reset-screen' => '1',
						'screen_id'             => $screen->id,
						'screen_base'           => $screen->base,
					)
				),
				'wc-proto-reset-screen'
			)
		);

		$settings .= '<fieldset><legend>' . esc_html__( 'Reorder controls', 'woocommerce' ) . '</legend>';
		$settings .= '<label for="wc-proto-reorder-controls">';
		$settings .= '<input type="checkbox" id="wc-proto-reorder-controls" ' . esc_attr( $checked ) . ' />';
		$settings .= ' ' . esc_html__( 'Show reorder controls', 'woocommerce' );
		$settings .= '</label></fieldset>';
		$settings .= '<script>
( function () {
	var ARROW_SELECTOR    = ".postbox-header .handle-order-higher, .postbox-header .handle-order-lower";
	var HANDLE_SELECTOR   = ".meta-box-sortables .postbox-header .hndle";
	var INTERACT_SELECTOR = ".meta-box-sortables .postbox-header .hndle a, .meta-box-sortables .postbox-header .hndle button, .meta-box-sortables .postbox-header .hndle select, .meta-box-sortables .postbox-header .hndle input, .meta-box-sortables .postbox-header .hndle label";
	var COOKIE            = "' . esc_js( $cookie_key ) . '";
	var RESET_URL         = "' . esc_js( $reset_url ) . '";

	function toggleSortable( enable ) {
		if ( ! window.jQuery ) { return; }
		try { window.jQuery( ".meta-box-sortables" ).sortable( enable ? "enable" : "disable" ); } catch ( e ) {}
	}

	function setVisible( show ) {
		var hideStyle = document.getElementById( "wc-proto-reorder-hide" );
		if ( show ) {
			if ( hideStyle ) { hideStyle.remove(); }
		} else {
			if ( ! hideStyle ) {
				var s = document.createElement( "style" );
				s.id = "wc-proto-reorder-hide";
				s.textContent = ".postbox-header .handle-order-higher, .postbox-header .handle-order-lower { display: none !important; } .meta-box-sortables .postbox > .postbox-header, .meta-box-sortables .postbox > .postbox-header .hndle { cursor: default !important; }";
				document.head.appendChild( s );
			}
		}
		toggleSortable( show );
		document.cookie = COOKIE + "=" + ( show ? "1" : "0" ) + ";path=/;max-age=86400";
	}

	document.getElementById( "wc-proto-reorder-controls" ).addEventListener( "change", function () {
		setVisible( this.checked );
	} );

	/* On load, sync sortable state with the cookie (postbox.js initialises sortable after our script). */
	window.addEventListener( "load", function () {
		var checkbox = document.getElementById( "wc-proto-reorder-controls" );
		toggleSortable( !! ( checkbox && checkbox.checked ) );
	} );

	var descP = document.querySelector( ".metabox-prefs-container > p, .metabox-prefs > p" );
	if ( descP ) {
		descP.innerHTML = "' . esc_js( __( 'Use checkboxes to show or hide screen elements, and click headings to expand or collapse them. Reorder controls are off by default and can be enabled below.', 'woocommerce' ) ) . ' <a href=\"" + RESET_URL + "\">' . esc_js( __( 'Reset to defaults', 'woocommerce' ) ) . '</a>";
	}
}() );
</script>';

		return $settings;
	}

	/**
	 * Output styles in admin_head.
	 * Visual polish always applies; hide + drag-lock only when cookie is not set.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}

		echo '<style id="wc-proto-reorder-style">
			.postbox-header .handle-actions { padding-right: var(--wpds-dimension-padding-sm, 8px); }
			.postbox .handle-order-higher,
			.postbox .handle-order-lower { display: inline-flex; align-items: center; justify-content: center; }
		</style>';

		if ( ! self::is_showing() ) {
			echo '<style id="wc-proto-reorder-hide">
				.postbox-header .handle-order-higher,
				.postbox-header .handle-order-lower { display: none !important; }
				.meta-box-sortables .postbox > .postbox-header { cursor: default !important; }
				.meta-box-sortables .postbox > .postbox-header .hndle { cursor: default !important; }
			</style>';
		}
	}

	/**
	 * Replace the WordPress-generated arrow button contents with inline SVG chevrons
	 * and wire focus/blur handlers directly so we fully own the focus ring appearance.
	 * Runs in admin_footer so all metabox HTML is in the DOM.
	 */
	public static function replace_arrow_buttons(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
		<script>
		( function () {
			var UP_SVG   = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 3.9 6.5 9.5l1 1 3.8-3.7V20h1.5V6.8l3.7 3.7 1-1z"/></svg>';
			var DOWN_SVG = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="m16.5 13.5-3.7 3.7V4h-1.5v13.2l-3.8-3.7-1 1 5.5 5.6 5.5-5.6z"/></svg>';

			function replaceButton( btn, svgIcon ) {
				var srText    = btn.querySelector( '.screen-reader-text' );
				var srContent = srText ? srText.innerHTML : '';

				btn.innerHTML = svgIcon + '<span class="screen-reader-text">' + srContent + '</span>';

				btn.style.background   = 'none';
				btn.style.border       = '0';
				btn.style.cursor       = 'pointer';
				btn.style.width        = '1.62rem';
				btn.style.height       = '1.62rem';
				btn.style.color        = '#787c82';
				btn.style.borderRadius = '2px';
				btn.style.padding      = '0';
			}

			document.querySelectorAll( '.handle-order-higher' ).forEach( function ( btn ) {
				replaceButton( btn, UP_SVG );
			} );
			document.querySelectorAll( '.handle-order-lower' ).forEach( function ( btn ) {
				replaceButton( btn, DOWN_SVG );
			} );

			/* Block metabox drag at the event level when reorder controls are disabled.
			   Capture-phase listener runs before jQuery UI sortable's bubble-phase handler. */
			function isReorderEnabled() {
				return /(?:^|; )wc_proto_reorder_controls=1(?:;|$)/.test( document.cookie );
			}
			document.addEventListener( 'mousedown', function ( e ) {
				if ( isReorderEnabled() ) { return; }
				var header = e.target.closest && e.target.closest( '.meta-box-sortables .postbox > .postbox-header' );
				if ( ! header ) { return; }
				/* Let interactive controls inside the header work normally. */
				if ( e.target.closest( '.handlediv, .handle-actions, button, a, input, select, label' ) ) { return; }
				e.stopPropagation();
				e.preventDefault();
			}, true );
		}() );
		</script>
		<?php
	}
}
