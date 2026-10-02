<?php
/**
 * Backward compatibility shim: keeps the old DataUtils FQCN resolving after the class
 * moved to the version-neutral Internal\RestApi\Refunds namespace.
 *
 * @deprecated 11.2.0 Use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

class_alias(
	\Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils::class,
	'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils'
);
