<?php
/**
 * Backward compatibility shim: keeps the old RefundPreviewSchema FQCN resolving after the class
 * moved to the version-neutral Internal\RestApi\Refunds\Schema namespace. Will be removed in a
 * future version.
 *
 * @deprecated 11.3.0 Use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema instead.
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema;

defined( 'ABSPATH' ) || exit;

class_alias( \Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema::class, __NAMESPACE__ . '\RefundPreviewSchema' );
