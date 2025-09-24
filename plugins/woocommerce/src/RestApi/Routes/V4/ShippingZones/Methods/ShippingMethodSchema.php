<?php
/**
 * Shipping Method Schema.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Methods;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractSchema;

/**
 * Shipping Method Schema class.
 */
class ShippingMethodSchema extends AbstractSchema {

	/**
	 * The schema identifier.
	 */
	const IDENTIFIER = 'shipping_method';
}