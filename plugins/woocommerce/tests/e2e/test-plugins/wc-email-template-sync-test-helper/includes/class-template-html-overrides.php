<?php
/**
 * Template HTML override filter for the WC Email Template Sync test helper plugin.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Filter wrapper that swaps in fixture-controlled canonical HTML for a given email type.
 *
 * Dormant when its driving option is empty.
 */
class Template_HTML_Overrides {

	public const OPTION_NAME = 'wc_test_template_html_override';

	/**
	 * Register the filter.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_email_block_template_html',
			array( $this, 'maybe_override' ),
			100,
			2
		);
	}

	/**
	 * Conditionally swap the canonical HTML for a given email type when an override option is set.
	 *
	 * @param string $template_html The HTML produced by the upstream filter.
	 * @param mixed  $email         The WC_Email instance whose template is being rendered.
	 * @return string Possibly-overridden HTML.
	 */
	public function maybe_override( string $template_html, $email ): string {
		$overrides = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $overrides ) || empty( $overrides ) ) {
			return $template_html;
		}

		$email_id = is_object( $email ) && isset( $email->id ) ? (string) $email->id : '';
		if ( '' === $email_id ) {
			return $template_html;
		}

		return isset( $overrides[ $email_id ] )
			? (string) $overrides[ $email_id ]
			: $template_html;
	}
}
