<?php
/**
 * Backward compatibility shim for the relocated RefundPreviewSchema class.
 *
 * The class moved to the version-neutral Automattic\WooCommerce\Internal\RestApi\Refunds
 * namespace because it is shared by the wc/v3 and wc/v4 refund preview endpoints. This file
 * keeps the old FQCN resolving: the autoloader maps the old class name to this file, and the
 * alias below points it at the new class.
 *
 * Note: the relocated class no longer extends the V4 AbstractSchema, so instanceof checks
 * against that base class no longer match. Its public behavior (get_item_schema) is unchanged.
 *
 * @deprecated 11.2.0 Use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

class_alias(
	\Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema::class,
	'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema'
);
