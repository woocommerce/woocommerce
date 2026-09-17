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
use WP_REST_Request;

/**
 * Keeps the old RefundPreviewSchema FQCN working after the class moved to the
 * version-neutral Internal\RestApi\Refunds namespace. Restores the members the old
 * class inherited from the V4 AbstractSchema, which the relocated class does not
 * carry; only instanceof AbstractSchema no longer matches.
 *
 * @deprecated 11.2.0 Use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema instead.
 */
class RefundPreviewSchema extends RelocatedRefundPreviewSchema {

	/**
	 * Context for the item schema - view and edit only.
	 *
	 * @var array
	 */
	const VIEW_EDIT_CONTEXT = array( 'view', 'edit' );

	/**
	 * Return all writable properties for the item schema.
	 *
	 * @return array The schema properties.
	 * @since 10.9.0
	 */
	public function get_writable_item_schema_properties(): array {
		return array_filter( $this->get_item_schema_properties(), array( $this, 'filter_writable_props' ) );
	}

	/**
	 * Filter schema properties to only return writable ones.
	 *
	 * @param array $schema The schema property to check.
	 * @return bool True if the property is writable, false otherwise.
	 * @since 10.9.0
	 */
	protected function filter_writable_props( array $schema ): bool {
		return empty( $schema['readonly'] );
	}

	// The next method always throws so its return type can never be reached.
	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
	/**
	 * Not used. The refund preview controllers bypass prepare_item_for_response and
	 * return the raw data array directly, so this method must never be invoked. It is
	 * kept only because the old class exposed it via AbstractSchema.
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
