<?php
/**
 * Backward compatibility shim for the relocated DataUtils class.
 *
 * The class moved to the version-neutral Automattic\WooCommerce\Internal\RestApi\Refunds
 * namespace because it is shared by the wc/v3 and wc/v4 refund endpoints. This file keeps
 * the old FQCN resolving: the autoloader maps the old class name to this file, and the
 * alias below points it at the new class.
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
