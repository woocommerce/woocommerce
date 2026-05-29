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
		add_filter( 'screen_settings', array( self::class, 'add_screen_option' ), 10, 2 );
		add_action( 'admin_head', array( self::class, 'maybe_hide_controls' ) );
	}

	/**
	 * Check whether the user has opted in to showing reorder controls via cookie.
	 */
	private static function is_showing(): bool {
		return ! empty( $_COOKIE[ self::COOKIE_KEY ] ) && '1' === $_COOKIE[ self::COOKIE_KEY ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * Add a "Show reorder controls" checkbox to Screen Options.
	 * Toggles arrow visibility instantly via JS; cookie persists state across page loads.
	 *
	 * @param string     $settings Existing screen settings HTML.
	 * @param \WP_Screen $screen   Current screen.
	 * @return string
	 */
	public static function add_screen_option( string $settings, \WP_Screen $screen ): string {
		if ( 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return $settings;
		}

		$checked    = self::is_showing() ? 'checked' : '';
		$cookie_key = self::COOKIE_KEY;

		$settings .= '<fieldset><legend>' . esc_html__( 'Reorder controls', 'woocommerce' ) . '</legend>';
		$settings .= '<label for="wc-proto-reorder-controls">';
		$settings .= '<input type="checkbox" id="wc-proto-reorder-controls" ' . esc_attr( $checked ) . ' />';
		$settings .= ' ' . esc_html__( 'Show reorder controls', 'woocommerce' );
		$settings .= '</label></fieldset>';
		$settings .= '<script>
( function () {
	var SELECTOR = ".postbox-header .handle-order-higher, .postbox-header .handle-order-lower";
	var COOKIE   = "' . esc_js( $cookie_key ) . '";

	function setVisible( show ) {
		document.querySelectorAll( SELECTOR ).forEach( function ( el ) {
			el.style.display = show ? "" : "none";
		} );
		document.cookie = COOKIE + "=" + ( show ? "1" : "0" ) + ";path=/;max-age=86400";
	}

	document.getElementById( "wc-proto-reorder-controls" ).addEventListener( "change", function () {
		setVisible( this.checked );
	} );
}() );
</script>';

		return $settings;
	}

	/**
	 * Inject initial CSS to hide reorder arrows on page load when cookie is not set.
	 * JS takes over from there without requiring a reload.
	 */
	public static function maybe_hide_controls(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}

		if ( ! self::is_showing() ) {
			echo '<style id="wc-proto-reorder-hide">.postbox-header .handle-order-higher, .postbox-header .handle-order-lower { display: none !important; }</style>';
		}
	}
}
