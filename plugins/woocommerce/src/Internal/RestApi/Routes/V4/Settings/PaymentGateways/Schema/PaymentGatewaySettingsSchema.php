<?php
/**
 * PaymentGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * PaymentGatewaySettingsSchema class.
 *
 * Generic payment gateway settings schema for gateways without special requirements.
 * Extends AbstractPaymentGatewaySettingsSchema with default implementations.
 */
class PaymentGatewaySettingsSchema extends AbstractPaymentGatewaySettingsSchema {
	// All functionality inherited from abstract base class.
}
