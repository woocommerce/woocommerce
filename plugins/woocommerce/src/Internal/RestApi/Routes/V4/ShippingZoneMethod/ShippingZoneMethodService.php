<?php
/**
 * Backward compatibility shim: keeps the old ShippingZoneMethodService FQCN resolving after the
 * class moved to the version-neutral Internal\Shipping namespace.
 *
 * @deprecated 11.5.0 Use Automattic\WooCommerce\Internal\Shipping\ShippingZoneMethodService instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

class_alias(
	\Automattic\WooCommerce\Internal\Shipping\ShippingZoneMethodService::class,
	'Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService'
);
