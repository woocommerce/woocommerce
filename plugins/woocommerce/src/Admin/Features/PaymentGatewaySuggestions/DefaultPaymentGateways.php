<?php
/**
 * This class is only available for backward compatibility.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;

defined( 'ABSPATH' ) || exit;

/**
 * Default Payment Gateways.
 *
 * @deprecated 10.0.0 This class is deprecated and will be removed in a future version.
 */
class DefaultPaymentGateways {
	/**
	 * Get array of countries supported by WCPay depending on feature flag.
	 *
	 * @deprecated 10.0.0 This method is deprecated and will be removed in a future version.
	 *
	 * @return array Array of countries.
	 */
	public static function get_wcpay_countries(): array {
		try {
			/**
			 * The Payments Settings [page] service.
			 *
			 * @var SettingsPaymentsService $settings_payments_service
			 */
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			return $settings_payments_service->get_payment_extension_suggestion_countries( PaymentsExtensionSuggestions::WOOPAYMENTS );
		} catch ( \Throwable $e ) {
			// In case of any error, use an empty array.
			return array();
		}
	}
}
