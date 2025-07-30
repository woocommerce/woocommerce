<?php
/**
 * EmailColors class file
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Helper class for email colors.
 *
 * @internal Just for internal use.
 */
class EmailColors {

	/**
	 * Get default colors for emails.
	 *
	 * @param bool|null $email_improvements_enabled Whether the email improvements feature is enabled.
	 * @return array Array of default email colors.
	 */
	public static function get_default_colors( ?bool $email_improvements_enabled = null ) {
		if ( null === $email_improvements_enabled ) {
			$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
		}

		$base        = '#720eec';
		$bg          = '#f7f7f7';
		$body_bg     = '#ffffff';
		$body_text   = '#3c3c3c';
		$footer_text = '#3c3c3c';

		if ( $email_improvements_enabled ) {
			$base        = '#8526ff';
			$bg          = '#ffffff';
			$body_bg     = '#ffffff';
			$body_text   = '#1e1e1e';
			$footer_text = '#787c82';

			$global_colors = static::get_colors_from_global_styles();

			if ( $global_colors ) {
				$base        = $global_colors['base'];
				$bg          = $global_colors['bg'];
				$body_bg     = $global_colors['body_bg'];
				$body_text   = $global_colors['body_text'];
				$footer_text = $global_colors['footer_text'];
			}
		}

		return compact(
			'base',
			'bg',
			'body_bg',
			'body_text',
			'footer_text',
		);
	}

	/**
	 * Get email colors from global styles.
	 *
	 * @return array|null Array of colors or null if global styles are not available or complete.
	 */
	public static function get_colors_from_global_styles() {
		$styles = static::get_global_styles_data();

		if ( ! $styles ) {
			return null;
		}

		$bg          = $styles['color']['background'] ?? null;
		$body_bg     = $styles['color']['background'] ?? null;
		$body_text   = $styles['color']['text'] ?? null;
		$base        = $styles['elements']['button']['color']['background'] ?? null;
		$footer_text = $styles['elements']['caption']['color']['text'] ?? null;

		$bg          = is_string( $bg ) ? sanitize_hex_color( $bg ) : '';
		$body_bg     = is_string( $body_bg ) ? sanitize_hex_color( $body_bg ) : '';
		$body_text   = is_string( $body_text ) ? sanitize_hex_color( $body_text ) : '';
		$base        = is_string( $base ) ? sanitize_hex_color( $base ) : $body_text;
		$footer_text = is_string( $footer_text ) ? sanitize_hex_color( $footer_text ) : $body_text;

		// Only return colors if all are set, otherwise email styles might not match and the email can become unreadable.
		if ( ! $bg || ! $body_bg || ! $body_text || ! $base || ! $footer_text ) {
			return null;
		}

		return compact(
			'base',
			'bg',
			'body_bg',
			'body_text',
			'footer_text',
		);
	}

	/**
	 * Method to retrieve global styles data.
	 *
	 * @return array|null
	 */
	protected static function get_global_styles_data() {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() || ! function_exists( 'wp_get_global_styles' ) ) {
			return null;
		}
		return wp_get_global_styles( array(), array( 'transforms' => array( 'resolve-variables' ) ) );
	}
}
