<?php
/**
 * Backward compatibility shim: keeps the old OrderLineMetaValidator FQCN resolving after the class
 * moved to the version-neutral Internal\RestApi\Orders namespace. Will be removed in a future version.
 *
 * @deprecated 11.5.0 Use Automattic\WooCommerce\Internal\RestApi\Orders\OrderLineMetaValidator instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

class_alias( \Automattic\WooCommerce\Internal\RestApi\Orders\OrderLineMetaValidator::class, __NAMESPACE__ . '\OrderLineMetaValidator' );
