<?php
/**
 * ChequeGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * ChequeGatewaySettingsSchema class.
 *
 * Extends PaymentGatewaySettingsSchema for Cheque payment gateway.
 * No special fields or custom logic needed - uses base implementation.
 */
class ChequeGatewaySettingsSchema extends PaymentGatewaySettingsSchema {
	// All functionality inherited from base class.
}
