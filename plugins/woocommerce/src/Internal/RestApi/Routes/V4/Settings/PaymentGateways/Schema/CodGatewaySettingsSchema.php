<?php
/**
 * CodGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * CodGatewaySettingsSchema class.
 *
 * Extends AbstractPaymentGatewaySettingsSchema for Cash on Delivery payment gateway.
 * No special fields needed - uses base implementation.
 *
 * Note: The COD gateway has enable_for_methods and enable_for_virtual fields
 * which are standard fields stored in gateway settings.
 */
class CodGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {
	// All functionality inherited from base class.
}
