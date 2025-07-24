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

		$base_color_default        = '#720eec';
		$bg_color_default          = '#f7f7f7';
		$body_bg_color_default     = '#ffffff';
		$body_text_color_default   = '#3c3c3c';
		$footer_text_color_default = '#3c3c3c';

		if ( $email_improvements_enabled ) {
			$base_color_default        = '#8526ff';
			$bg_color_default          = '#ffffff';
			$body_bg_color_default     = '#ffffff';
			$body_text_color_default   = '#1e1e1e';
			$footer_text_color_default = '#787c82';

			$global_colors = self::get_colors_from_global_styles();

			$base_color_default        = $global_colors['base'] ? $global_colors['base'] : $base_color_default;
			$bg_color_default          = $global_colors['bg'] ? $global_colors['bg'] : $bg_color_default;
			$body_bg_color_default     = $global_colors['body_bg'] ? $global_colors['body_bg'] : $body_bg_color_default;
			$body_text_color_default   = $global_colors['body_text'] ? $global_colors['body_text'] : $body_text_color_default;
			$footer_text_color_default = $global_colors['footer_text'] ? $global_colors['footer_text'] : $footer_text_color_default;
		}

		return compact(
			'base_color_default',
			'bg_color_default',
			'body_bg_color_default',
			'body_text_color_default',
			'footer_text_color_default',
		);
	}

	/**
	 * Get email colors from global styles.
	 *
	 * @return array Array of colors.
	 */
	public static function get_colors_from_global_styles() {
		if ( ! wp_is_block_theme() || ! function_exists( 'wp_get_global_styles' ) ) {
			return array(
				'base'        => '',
				'bg'          => '',
				'body_bg'     => '',
				'body_text'   => '',
				'footer_text' => '',
			);
		}

		$styles = wp_get_global_styles( array(), array( 'transforms' => array( 'resolve-variables' ) ) );

		$bg          = $styles['color']['background'] ?? '';
		$body_bg     = $styles['color']['background'] ?? '';
		$body_text   = $styles['color']['text'] ?? '';
		$base        = $styles['elements']['button']['color']['background'] ?? '';
		$footer_text = $styles['elements']['caption']['color']['text'] ?? '';

		$bg          = is_string( $bg ) ? sanitize_hex_color( $bg ) : '';
		$body_bg     = is_string( $body_bg ) ? sanitize_hex_color( $body_bg ) : '';
		$body_text   = is_string( $body_text ) ? sanitize_hex_color( $body_text ) : '';
		$base        = is_string( $base ) ? sanitize_hex_color( $base ) : '';
		$footer_text = is_string( $footer_text ) ? sanitize_hex_color( $footer_text ) : '';

		return compact(
			'base',
			'bg',
			'body_bg',
			'body_text',
			'footer_text',
		);
	}
}
