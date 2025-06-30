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

			if ( wp_is_block_theme() && function_exists( 'wp_get_global_styles' ) ) {
				$global_styles             = wp_get_global_styles( array(), array( 'transforms' => array( 'resolve-variables' ) ) );
				$base_color_global         = ! empty( $global_styles['elements']['button']['color']['background'] )
					? sanitize_hex_color( $global_styles['elements']['button']['color']['background'] ) : '';
				$bg_color_global           = ! empty( $global_styles['color']['background'] )
					? sanitize_hex_color( $global_styles['color']['background'] ) : '';
				$body_bg_color_global      = ! empty( $global_styles['color']['background'] )
					? sanitize_hex_color( $global_styles['color']['background'] ) : '';
				$body_text_color_global    = ! empty( $global_styles['color']['text'] )
					? sanitize_hex_color( $global_styles['color']['text'] ) : '';
				$footer_text_color_global  = ! empty( $global_styles['elements']['caption']['color']['text'] )
					? sanitize_hex_color( $global_styles['elements']['caption']['color']['text'] ) : '';
				$base_color_default        = $base_color_global ? $base_color_global : $base_color_default;
				$bg_color_default          = $bg_color_global ? $bg_color_global : $bg_color_default;
				$body_bg_color_default     = $body_bg_color_global ? $body_bg_color_global : $body_bg_color_default;
				$body_text_color_default   = $body_text_color_global ? $body_text_color_global : $body_text_color_default;
				$footer_text_color_default = $footer_text_color_global ? $footer_text_color_global : $footer_text_color_default;
			}
		}

		return compact(
			'base_color_default',
			'bg_color_default',
			'body_bg_color_default',
			'body_text_color_default',
			'footer_text_color_default',
		);
	}
}
