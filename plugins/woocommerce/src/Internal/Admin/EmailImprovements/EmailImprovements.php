<?php
/**
 * Helper class to gradually enable email improvements to existing merchants.
 *
 * @since 9.9.0
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailImprovements;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Tracker;

defined( 'ABSPATH' ) || exit;

/**
 * EmailImprovements Class.
 */
class EmailImprovements {

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

	private const EMAIL_TEMPLATE_PARTS = array(
		'email-addresses.php',
		'email-customer-details.php',
		'email-downloads.php',
		'email-footer.php',
		'email-header.php',
		'email-mobile-messaging.php',
		'email-order-details.php',
		'email-order-items.php',
		'email-styles.php',
	);

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'add_email_improvements_modal_to_url' ) );
	}

	/**
	 * Check if any core emails are being overridden by a template override.
	 *
	 * @return bool True if core emails are being overridden, false otherwise.
	 */
	public static function has_email_templates_overridden() {
		$all_template_overrides = WC_Tracker::get_all_template_overrides();
		$core_email_overrides   = self::get_core_email_overrides( $all_template_overrides );
		return count( $core_email_overrides ) > 0;
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

	/**
	 * Check if email improvements are enabled for existing stores.
	 *
	 * @return bool True if email improvements are enabled for existing stores, false otherwise.
	 */
	public static function is_email_improvements_enabled_for_existing_stores() {
		$is_feature_enabled             = FeaturesUtil::feature_is_enabled( 'email_improvements' );
		$is_enabled_for_existing_stores = 'yes' === get_option( 'woocommerce_email_improvements_existing_store_enabled' );
		return $is_feature_enabled && $is_enabled_for_existing_stores;
	}

	/**
	 * Check if email improvements should be enabled for existing stores.
	 * - The feature is not already enabled.
	 * - The feature was not manually disabled.
	 * - The email templates are not overridden.
	 * - The email customizer is not enabled.
	 *
	 * @return bool True if email improvements should be enabled for existing stores, false otherwise.
	 */
	public static function should_enable_email_improvements_for_existing_stores() {
		if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
			return false;
		}
		$manually_disabled_before = get_option( 'woocommerce_email_improvements_last_disabled_at' );
		if ( $manually_disabled_before ) {
			return false;
		}
		if ( self::has_email_templates_overridden() ) {
			return false;
		}

		if ( self::is_email_customizer_enabled() ) {
			return false;
		}
		// Temporarily paused roll-out to gather more feedback.
		return false;
	}

	/**
	 * Check if we should notice the merchant about email improvements.
	 *
	 * @return bool True if we should notice the merchant about email improvements, false otherwise.
	 */
	public static function should_notify_merchant_about_email_improvements() {
		return ! FeaturesUtil::feature_is_enabled( 'email_improvements' );
	}

	/**
	 * Add email improvements modal parameter to the URL when loading the WooCommerce Home page.
	 *
	 * @return void
	 */
	public static function add_email_improvements_modal_to_url() {
		// Check if we're on the WooCommerce Home page.
		if ( ! isset( $_GET['page'] ) || 'wc-admin' !== $_GET['page'] || isset( $_GET['path'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$dismissed_modal = get_option( 'woocommerce_admin_dismissed_email_improvements_modal' );
		if ( 'yes' !== $dismissed_modal && self::is_email_improvements_enabled_for_existing_stores() ) {
			update_option( 'woocommerce_admin_dismissed_email_improvements_modal', 'yes' );
			wp_safe_redirect( add_query_arg( 'emailImprovementsModal', 'enabled' ) );
			exit;
		}

		$dismissed_modal = get_option( 'woocommerce_admin_dismissed_try_email_improvements_modal' );
		if ( 'yes' !== $dismissed_modal && self::should_notify_merchant_about_email_improvements() ) {
			update_option( 'woocommerce_admin_dismissed_try_email_improvements_modal', 'yes' );
			wp_safe_redirect( add_query_arg( 'emailImprovementsModal', 'try' ) );
			exit;
		}
	}

	/**
	 * Get all core emails.
	 *
	 * @return array Core emails.
	 */
	public static function get_core_emails() {
		return array_filter(
			WC()->mailer()->get_emails(),
			function ( $email ) {
				return strpos( get_class( $email ), 'WC_Email_' ) === 0 && is_string( $email->template_html );
			}
		);
	}

	/**
	 * Get all core email template overrides.
	 *
	 * @param array $template_overrides All template overrides.
	 * @return array Core email template overrides.
	 */
	public static function get_core_email_overrides( $template_overrides ) {
		$core_emails          = self::get_core_emails();
		$core_email_templates = array_map(
			function ( $email ) {
				return basename( $email->template_html );
			},
			$core_emails
		);
		$all_email_templates  = array_merge( $core_email_templates, self::EMAIL_TEMPLATE_PARTS );
		return array_intersect( $all_email_templates, $template_overrides );
	}
}
