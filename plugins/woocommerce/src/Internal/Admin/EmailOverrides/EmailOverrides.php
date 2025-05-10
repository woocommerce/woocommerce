<?php
/**
 * Helper class to gradually enable email improvements to existing merchants.
 *
 * @since 9.9.0
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailOverrides;

use WC_Tracker;

defined( 'ABSPATH' ) || exit;


/**
 * EmailPreview Class.
 */
class EmailOverrides {

	/**
	 * Non-exhaustive list of email customizers.
	 *
	 * @var string[]
	 */
	private const EMAIL_CUSTOMIZERS = array(
		'aco-email-customizer-and-designer-for-woocommerce.php',
		'decorator.php',
		'email-customizer-for-woocommerce.php',
		'email-customizer-pro.php',
		'kadence-woocommerce-email-designer.php',
		'mailpoet.php',
		'wp-html-mail.php',
		'yaymail.php',
	);

	/**
	 * Check if any core emails are being overridden by a template override.
	 *
	 * @return bool True if core emails are being overridden, false otherwise.
	 */
	public static function has_email_templates_overridden() {
		$all_template_overrides = WC_Tracker::get_all_template_overrides();
		$core_email_overrides   = WC_Tracker::get_core_email_overrides( $all_template_overrides );
		return $core_email_overrides['count'] > 0;
	}

	/**
	 * Check if any of the email customizers is enabled.
	 *
	 * @return bool True if any of the email customizers is enabled, false otherwise.
	 */
	public static function is_email_customizer_enabled() {
		$all_plugins    = WC_Tracker::get_all_plugins();
		$active_plugins = $all_plugins['active_plugins'];
		$plugin_slugs   = array_map(
			function ( $plugin_path ) {
				$parts = explode( '/', $plugin_path );
				return end( $parts );
			},
			array_keys( $active_plugins )
		);
		return count( array_intersect( self::EMAIL_CUSTOMIZERS, $plugin_slugs ) ) > 0;
	}
}
