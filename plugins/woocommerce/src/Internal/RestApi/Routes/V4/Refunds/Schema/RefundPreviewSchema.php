<?php
/**
 * Backward compatibility shim: keeps the old RefundPreviewSchema FQCN resolving after the
 * class moved to the version-neutral Internal\RestApi\Refunds namespace. The relocated class
 * no longer extends the V4 AbstractSchema, so instanceof checks against it no longer match.
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
