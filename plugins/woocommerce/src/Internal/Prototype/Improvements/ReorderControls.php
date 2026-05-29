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
 */
class ReorderControls {

	const USER_META_KEY = 'wc_proto_show_reorder_controls';

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
		add_filter( 'set_screen_option_' . self::USER_META_KEY, array( self::class, 'save_screen_option' ), 10, 3 );
	}

	/**
	 * Add a "Show reorder controls" checkbox to Screen Options.
	 *
	 * Uses a hidden input + checkbox pattern so unchecking saves '0' correctly.
	 *
	 * @param string     $settings Existing screen settings HTML.
	 * @param \WP_Screen $screen  Current screen.
	 * @return string
	 */
	public static function add_screen_option( string $settings, \WP_Screen $screen ): string {
		if ( 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return $settings;
		}

		$checked = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		$settings .= '<fieldset><legend>' . esc_html__( 'Reorder controls', 'woocommerce' ) . '</legend>';
		$settings .= '<label for="wc-proto-reorder-controls">';
		$settings .= '<input type="hidden" name="' . esc_attr( self::USER_META_KEY ) . '" value="0" />';
		$settings .= '<input type="checkbox" id="wc-proto-reorder-controls" name="' . esc_attr( self::USER_META_KEY ) . '" value="1" ' . checked( $checked, '1', false ) . ' />';
		$settings .= ' ' . esc_html__( 'Show reorder controls', 'woocommerce' );
		$settings .= '</label></fieldset>';

		return $settings;
	}

	/**
	 * Inject CSS to hide reorder arrows when the user opted out.
	 */
	public static function maybe_hide_controls(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}

		$show = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		if ( '1' !== $show ) {
			echo '<style>.postbox-header .handle-order-higher, .postbox-header .handle-order-lower { display: none !important; }</style>';
		}
	}

	/**
	 * Allow WordPress to save this screen option to user meta.
	 *
	 * @param mixed  $status Filtered value (false by default).
	 * @param string $option Option name.
	 * @param mixed  $value  Submitted value.
	 * @return string '1' or '0'.
	 */
	public static function save_screen_option( $status, string $option, $value ): string {
		return '1' === (string) $value ? '1' : '0';
	}
}
