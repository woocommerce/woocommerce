<?php
/**
 * Backward compatibility shim for the relocated RefundPreviewSchema class.
 *
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema as RelocatedRefundPreviewSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * Keeps the old RefundPreviewSchema FQCN working after the class moved to the
 * version-neutral Internal\RestApi\Refunds namespace. It still extends the V4
 * AbstractSchema, like the old class did, and reads its properties from the relocated class.
 *
 * @deprecated 11.2.0 Use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema instead.
 */
class RefundPreviewSchema extends AbstractSchema {

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = RelocatedRefundPreviewSchema::IDENTIFIER;

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public function get_item_schema_properties(): array {
		return ( new RelocatedRefundPreviewSchema() )->get_item_schema_properties();
	}

	// The next method always throws so its return type can never be reached.
	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
	/**
	 * Not used. The refund preview controllers bypass prepare_item_for_response and
	 * return the raw data array directly, so this method must never be invoked.
	 * AbstractSchema requires it, but the body always throws.
	 *
	 * @param mixed           $item           Item data.
	 * @param WP_REST_Request $request        Request object.
	 * @param array           $include_fields Fields to include.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return array
	 * @throws \LogicException Always — this method should never be called for the preview route.
	 *
	 * @since 10.9.0
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		throw new \LogicException(
			'RefundPreviewSchema::get_item_response() should not be called; the preview controller bypasses prepare_item_for_response().'
		);
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
}
