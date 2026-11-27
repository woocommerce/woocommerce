<?php
/**
 * Backward compatibility shim: keeps the old ShippingZoneMethodService FQCN resolving after the
 * class moved to the version-neutral Internal\Shipping namespace. Will be removed in a future version.
 *
 * @deprecated 11.5.0 Use Automattic\WooCommerce\Internal\Shipping\ShippingZoneMethodService instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod;

defined( 'ABSPATH' ) || exit;

class_alias( \Automattic\WooCommerce\Internal\Shipping\ShippingZoneMethodService::class, __NAMESPACE__ . '\ShippingZoneMethodService' );
