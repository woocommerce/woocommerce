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

		// Clear our reorder controls cookie.
		setcookie( self::COOKIE_KEY, '', time() - 3600, '/' ); // phpcs:ignore WordPress.Arrays.ArrayKeySpacingRestrictions

		wp_safe_redirect( remove_query_arg( array( 'wc-proto-reset-screen', '_wpnonce', 'screen_id', 'screen_base' ) ) );
		exit;
	}

	/**
	 * Add a "Show reorder controls" checkbox to Screen Options, and inject a
	 * "Reset to defaults" link below the Screen elements heading via JS.
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

	function setVisible( show ) {
		document.querySelectorAll( ARROW_SELECTOR ).forEach( function ( el ) {
			el.style.display = show ? "" : "none";
		} );
		document.querySelectorAll( HANDLE_SELECTOR ).forEach( function ( el ) {
			el.style.pointerEvents = show ? "" : "none";
			el.style.cursor        = show ? "" : "default";
		} );
		document.querySelectorAll( INTERACT_SELECTOR ).forEach( function ( el ) {
			el.style.pointerEvents = show ? "" : "auto";
			el.style.cursor        = show ? "" : "auto";
		} );
		document.cookie = COOKIE + "=" + ( show ? "1" : "0" ) + ";path=/;max-age=86400";
	}

	document.getElementById( "wc-proto-reorder-controls" ).addEventListener( "change", function () {
		setVisible( this.checked );
	} );

	// Replace the WordPress-generated description with updated copy + reset link.
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
			.postbox .handle-order-higher:focus,
			.postbox .handle-order-higher:focus-visible,
			.postbox .handle-order-lower:focus,
			.postbox .handle-order-lower:focus-visible { border-radius: var(--wpds-border-radius-xs, 2px) !important; box-shadow: 0 0 0 2px #2271b1 !important; }
		</style>';

		if ( ! self::is_showing() ) {
			echo '<style id="wc-proto-reorder-hide">
				.postbox-header .handle-order-higher,
				.postbox-header .handle-order-lower { display: none !important; }
				.meta-box-sortables .postbox-header .hndle { pointer-events: none !important; cursor: default !important; }
				.meta-box-sortables .postbox-header .hndle a,
				.meta-box-sortables .postbox-header .hndle button,
				.meta-box-sortables .postbox-header .hndle select,
				.meta-box-sortables .postbox-header .hndle input,
				.meta-box-sortables .postbox-header .hndle label { pointer-events: auto !important; cursor: auto !important; }
			</style>';
		}
	}
}
