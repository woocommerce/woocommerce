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

		// Reject price separators containing digits on any write path, settings screen or not.
		add_filter( 'sanitize_option_woocommerce_price_decimal_sep', array( $this, 'sanitize_price_separator_option' ), 10, 2 );
		add_filter( 'sanitize_option_woocommerce_price_thousand_sep', array( $this, 'sanitize_price_separator_option' ), 10, 2 );
	}

	/**
	 * Validates a thousand or decimal separator submitted from the settings screen.
	 * Rejected values add a settings error and fall back to the stored value, or to
	 * the option default when the stored value is itself invalid.
	 *
	 * @since 11.2.0
	 * @param string $value     Option value passed through earlier filters.
	 * @param array  $option    Option data including 'id' and 'default'.
	 * @param mixed  $raw_value Raw POST value before any processing.
	 * @return string
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function sanitize_price_separator_setting( $value, $option, $raw_value ) {
		$normalized = $this->normalize_price_separator( $raw_value );

		if ( is_string( $normalized ) && $this->is_valid_price_separator( $normalized ) ) {
			return $normalized;
		}

		\WC_Admin_Settings::add_error(
			esc_html__( 'Thousand and decimal separators cannot contain numbers.', 'woocommerce' )
		);

		$default = $option['default'] ?? '';
		$stored  = get_option( $option['id'], $default );

		return $this->is_valid_price_separator( $stored ) ? $stored : $default;
	}

	/**
	 * Discards thousand and decimal separators containing numbers on any write path.
	 *
	 * The settings screen reports the problem to the user through
	 * wc_format_option_price_separators(). This filter covers update_option(),
	 * WP-CLI and REST writes, where there is no settings screen to report an error
	 * on, so the invalid value is silently replaced by the stored one.
	 *
	 * @since 11.2.0
	 * @param mixed  $value  Option value being saved.
	 * @param string $option Option name.
	 * @return mixed
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function sanitize_price_separator_option( $value, $option ) {
		if ( $this->is_valid_price_separator( $value ) ) {
			return $value;
		}

		$default = 'woocommerce_price_decimal_sep' === $option ? '.' : ',';
		$stored  = get_option( $option, $default );

		return $this->is_valid_price_separator( $stored ) ? $stored : $default;
	}

	/**
	 * Strips tags from a submitted separator and collapses whitespace runs into a
	 * single space, so the common single-space thousand separator survives where
	 * wc_clean() would trim it away.
	 *
	 * @param mixed $raw_value Submitted value.
	 * @return string|null Normalized value, or null when the value is not a string.
	 */
	private function normalize_price_separator( $raw_value ): ?string {
		if ( ! is_string( $raw_value ) ) {
			return null;
		}

		$no_tags = wp_kses( $raw_value, array() );

		return preg_replace( '/\s+/', ' ', $no_tags ) ?? $no_tags;
	}

	/**
	 * Checks whether a value can be used as a thousand or decimal separator.
	 *
	 * Separators are frequently stored as HTML entities, so the check runs against
	 * what the value renders as: `&#44;` is a comma and stays valid, while `&#49;`
	 * is the digit 1 and does not. `\p{N}` rather than `[0-9]` so that fullwidth
	 * and other localized digits, which would read as part of the amount, are
	 * rejected as well.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	private function is_valid_price_separator( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// preg_match() returns false on malformed UTF-8, which is not worth storing either.
		return 0 === preg_match( '/\p{N}/u', $decoded );
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
}
