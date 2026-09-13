<?php
/**
 * FormatValidator class.
 */

namespace Automattic\WooCommerce\Internal\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles sanitization of core options that need to conform to certain format.
 *
 * @since 6.6.0
 */
class OptionSanitizer {

	/**
	 * OptionSanitizer constructor.
	 */
	public function __construct() {
		// Sanitize color options.
		$color_options = array(
			'woocommerce_email_base_color',
			'woocommerce_email_background_color',
			'woocommerce_email_body_background_color',
			'woocommerce_email_text_color',
		);

		foreach ( $color_options as $option_name ) {
			add_filter(
				"woocommerce_admin_settings_sanitize_option_{$option_name}",
				array( $this, 'sanitize_color_option' ),
				10,
				2
			);
		}
		// Normalize stock threshold settings to non-negative integers.
		add_filter( 'woocommerce_admin_settings_sanitize_option_woocommerce_notify_low_stock_amount', 'absint' );
		add_filter( 'woocommerce_admin_settings_sanitize_option_woocommerce_notify_no_stock_amount', 'absint' );
	}

	/**
	 * Sanitizes values for options of type 'color' before persisting to the database.
	 * Falls back to previous/default value for the option if given an invalid value.
	 *
	 * @since 6.6.0
	 * @param string $value Option value.
	 * @param array  $option Option data.
	 * @return string Color in hex format.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function sanitize_color_option( $value, $option ) {
		$value = sanitize_hex_color( $value );

		// If invalid, try the current value.
		if ( ! $value && ! empty( $option['id'] ) ) {
			$value = sanitize_hex_color( get_option( $option['id'] ) );
		}

		// If still invalid, try the default.
		if ( ! $value && ! empty( $option['default'] ) ) {
			$value = sanitize_hex_color( $option['default'] );
		}

		return (string) $value;
	}

	/**
	 * Rejects thousand and decimal separators that contain a number.
	 * On rejection it adds a settings error and returns null, so the stored value is left untouched.
	 *
	 * @since 11.2.0
	 * @param mixed $value     Option value.
	 * @param mixed $raw_value Raw request value, null when the field was not submitted.
	 * @return mixed
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function sanitize_price_separator_setting( $value, $raw_value ) {
		if ( null === $raw_value ) {
			return $value;
		}

		if ( is_string( $raw_value ) ) {
			$separator = wp_kses( $raw_value, array() );
			$decoded   = html_entity_decode( $separator, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			if ( 0 === preg_match( '/\p{N}/u', $decoded ) ) {
				return $separator;
			}
		}

		\WC_Admin_Settings::add_error( __( 'Thousand and decimal separators cannot contain numbers.', 'woocommerce' ) );

		return null;
	}
}
