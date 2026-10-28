<?php
/**
 * Order ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\Abilities\Resources\OrderResource;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Shared order helpers for WooCommerce domain ability definitions.
 */
trait OrderAbilityTrait {

	/**
	 * Allowed order status slugs (without the `wc-` prefix).
	 *
	 * @return array<int, string>
	 */
	protected static function get_allowed_order_status_slugs(): array {
		return array_values(
			array_diff(
				array_map(
					array( OrderUtil::class, 'remove_status_prefix' ),
					array_keys( wc_get_order_statuses() )
				),
				array( OrderStatus::CHECKOUT_DRAFT )
			)
		);
	}

	/**
	 * Order types that the abilities can query: every registered order type
	 * except refunds and the HPOS placeholder.
	 *
	 * @return array<int, string>
	 */
	protected static function get_order_type_slugs(): array {
		return OrderResource::get_type_slugs();
	}

	/**
	 * Status slugs (without the `wc-` prefix) that are registered with the
	 * `wc-` prefix, including the statuses of other order types.
	 *
	 * @param array $args Arguments for get_post_stati().
	 * @return array<int, string>
	 */
	protected static function get_registered_order_status_slugs( array $args = array() ): array {
		return OrderResource::get_registered_status_slugs( $args );
	}

	/**
	 * Status slugs (without the `wc-` prefix) that the orders query accepts for
	 * any order type. Statuses that are excluded from search are not included.
	 *
	 * @return array<int, string>
	 */
	protected static function get_query_order_status_slugs(): array {
		return array_values(
			array_diff(
				array_unique( array_merge( self::get_allowed_order_status_slugs(), self::get_registered_order_status_slugs() ) ),
				array( OrderStatus::CHECKOUT_DRAFT ),
				self::get_registered_order_status_slugs( array( 'exclude_from_search' => true ) )
			)
		);
	}

	/**
	 * Possible order status slugs (without the `wc-` prefix) for ability output.
	 *
	 * @return array<int, string>
	 */
	protected static function get_order_output_status_slugs(): array {
		return OrderResource::get_output_status_slugs();
	}

	/**
	 * Get an order note output schema.
	 *
	 * @return array
	 */
	protected static function get_order_note_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'note_id' => array( 'type' => 'integer' ),
				'order'   => self::get_order_output_schema(),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the schema for a single order in a response.
	 *
	 * @return array
	 */
	protected static function get_order_output_schema(): array {
		return AbilityResources::output_schema( OrderResource::NAME );
	}

	/**
	 * Get an order from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected static function get_order_from_input( array $input ) {
		$order_id = (int) ( $input['id'] ?? 0 );
		if ( $order_id < 1 ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $order;
	}

	/**
	 * Check order edit access for an ability input payload.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_edit_order( $input = array() ): bool {
		$order_id = self::get_id_from_input( $input );

		return $order_id > 0 && self::check_order_permission( $order_id, 'edit' );
	}

	/**
	 * Check order read access for an ability input payload.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public static function can_view_order( $input = array() ): bool {
		$order_id = self::get_id_from_input( $input );

		return $order_id > 0 && self::check_order_permission( $order_id, 'read' );
	}

	/**
	 * Check a permission against the actual type of an order. An order whose
	 * type the abilities do not support fails. An ID with no order is checked
	 * as `shop_order`.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $context  Permission context, such as `read` or `edit`.
	 * @return bool
	 */
	protected static function check_order_permission( int $order_id, string $context ): bool {
		$type = OrderUtil::get_order_type( $order_id );
		$type = $type ? $type : 'shop_order';

		return in_array( $type, self::get_order_type_slugs(), true )
			&& wc_rest_check_post_permissions( $type, $context, $order_id );
	}

	/**
	 * Format an order for ability output.
	 *
	 * @param \WC_Order $order              Order object.
	 * @param bool      $include_line_items Whether to include line items.
	 * @return array
	 */
	protected static function format_order_for_response( \WC_Order $order, bool $include_line_items ): array {
		return AbilityResources::output_data( OrderResource::NAME, $order, null, $include_line_items ? array() : array( 'line_items' ) );
	}
}
