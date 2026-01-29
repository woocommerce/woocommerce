<?php
/**
 * PayPal Notices Class
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices instead. This class will be removed in 11.0.0.
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Gateways\PayPal\Notices as PayPalNotices;

require_once __DIR__ . '/class-wc-gateway-paypal-helper.php';

/**
 * Class WC_Gateway_Paypal_Notices.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices instead. This class will be removed in 11.0.0.
 * @since 10.3.0
 */
class WC_Gateway_Paypal_Notices {
	/**
	 * The delegated notices instance.
	 *
	 * @var PayPalNotices
	 */
	private $notices;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->notices = new PayPalNotices();
	}

	/**
	 * Add PayPal Standard notices.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_notices() instead.
	 * @since 10.4.0
	 * @return void
	 */
	public function add_paypal_notices() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_notices()' );
		$this->notices->add_paypal_notices();
	}

	/**
	 * Add PayPal notices on the payments settings page.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_notices_on_payments_settings_page() instead.
	 * @since 10.4.0
	 * @return void
	 */
	public function add_paypal_notices_on_payments_settings_page() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_notices_on_payments_settings_page()' );
		$this->notices->add_paypal_notices_on_payments_settings_page();
	}

	/**
	 * Add notice warning about the migration to PayPal Payments on the Payments settings page.
	 *
	 * @deprecated 10.4.0 No longer used. Functionality is now handled by add_paypal_notices_on_payments_settings_page().
	 * @return void
	 */
	public function add_paypal_migration_notice_on_payments_settings_page() {
		wc_deprecated_function( __METHOD__, '10.4.0', 'WC_Gateway_Paypal_Notices::add_paypal_notices_on_payments_settings_page' );
		$this->add_paypal_notices_on_payments_settings_page();
	}

	/**
	 * Add notice warning about the migration to PayPal Payments.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_migration_notice() instead.
	 * @since 10.3.0
	 * @return void
	 */
	public function add_paypal_migration_notice() {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::add_paypal_migration_notice()' );
		$this->notices->add_paypal_migration_notice();
	}

	/**
	 * Check if the installation notice has been dismissed.
	 *
	 * @deprecated 10.4.0 No longer used. Functionality is now handled by is_notice_dismissed().
	 * @return bool
	 */
	protected static function paypal_migration_notice_dismissed(): bool {
		wc_deprecated_function( __METHOD__, '10.4.0', 'WC_Gateway_Paypal_Notices::is_notice_dismissed' );
		return (bool) get_user_meta( get_current_user_id(), 'dismissed_paypal_migration_completed_notice', true );
	}

	/**
	 * Set the flag indicating PayPal account restriction.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::set_account_restriction_flag() instead.
	 * @since 10.4.0
	 * @return void
	 */
	public static function set_account_restriction_flag(): void {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::set_account_restriction_flag()' );
		PayPalNotices::set_account_restriction_flag();
	}

	/**
	 * Clear the flag indicating PayPal account restriction.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::clear_account_restriction_flag() instead.
	 * @since 10.4.0
	 * @return void
	 */
	public static function clear_account_restriction_flag(): void {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::clear_account_restriction_flag()' );
		PayPalNotices::clear_account_restriction_flag();
	}

	/**
	 * Handle PayPal order response to manage account restriction notices.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Notices::manage_account_restriction_flag_for_notice() instead.
	 * @since 10.4.0
	 * @param int|string $http_code     The HTTP status code from the PayPal API response.
	 * @param array      $response_data The decoded response data from the PayPal API.
	 * @param WC_Order   $order         The WooCommerce order object.
	 * @return void
	 */
	public static function manage_account_restriction_flag_for_notice( $http_code, array $response_data, WC_Order $order ): void {
		wc_deprecated_function( __METHOD__, '10.5.0', 'Automattic\WooCommerce\Gateways\PayPal\Notices::manage_account_restriction_flag_for_notice()' );
		PayPalNotices::manage_account_restriction_flag_for_notice( $http_code, $response_data, $order );
	}
}
