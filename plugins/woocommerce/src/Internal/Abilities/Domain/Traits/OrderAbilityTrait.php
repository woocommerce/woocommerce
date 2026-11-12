<?php
/**
 * Order ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
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
		return array_values(
			array_diff(
				wc_get_order_types(),
				array( 'shop_order_refund', DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE )
			)
		);
	}

	/**
	 * Status slugs (without the `wc-` prefix) that are registered with the
	 * `wc-` prefix, including the statuses of other order types.
	 *
	 * @param array $args Arguments for get_post_stati().
	 * @return array<int, string>
	 */
	protected static function get_registered_order_status_slugs( array $args = array() ): array {
		$statuses = array_filter(
			get_post_stati( $args ),
			static function ( $status ): bool {
				return 0 === strpos( $status, 'wc-' );
			}
		);

		return array_map( array( OrderUtil::class, 'remove_status_prefix' ), array_values( $statuses ) );
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
		return array_values(
			array_unique(
				array_merge(
					array_map(
						array( OrderUtil::class, 'remove_status_prefix' ),
						array_merge( OrderStatus::get_all(), array_keys( wc_get_order_statuses() ) )
					),
					self::get_registered_order_status_slugs()
				)
			)
		);
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
		return AbilityResources::output_schema( 'order' );
	}

	/**
	 * Get an order from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected static function get_order_from_input( array $input ) {
		$order = wc_get_order( $input['id'] );

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				/* translators: %d: order ID. */
				sprintf( __( 'Order %d does not exist. Use the woocommerce/orders-query ability to find the order ID.', 'woocommerce' ), $input['id'] ),
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
	 * type the abilities do not support fails. An ID that wc_get_order() has no
	 * order class for passes for users who could act on any order, so they get
	 * the not-found error instead of a permission error.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $context  Permission context, such as `read` or `edit`.
	 * @return bool
	 */
	protected static function check_order_permission( int $order_id, string $context ): bool {
		$class_names = \WC_Order_Factory::get_class_names_for_order_ids( array( $order_id ) );

		if ( empty( $class_names[ $order_id ] ) ) {
			return wc_rest_check_post_permissions( 'shop_order', 'edit' === $context ? 'batch' : $context );
		}

		$type = OrderUtil::get_order_type( $order_id );

		return in_array( $type, self::get_order_type_slugs(), true )
			&& wc_rest_check_post_permissions( $type, $context, $order_id );
	}

	/**
	 * Format an order after a save, retaining the saved object if reloading fails.
	 *
	 * @param \WC_Order $order Saved order object.
	 * @return array|\WP_Error
	 */
	protected static function format_saved_order_for_response( \WC_Order $order ) {
		$stored = wc_get_order( $order->get_id() );

		return self::format_order_for_response( $stored instanceof \WC_Order ? $stored : $order, false );
	}

	/**
	 * Format orders for ability output. One order that fails returns its error.
	 *
	 * @param \WC_Order[] $orders             Order objects.
	 * @param bool        $include_line_items Whether to include line items.
	 * @return array|\WP_Error
	 */
	protected static function format_orders_for_response( array $orders, bool $include_line_items ) {
		$formatted = array();
		foreach ( $orders as $order ) {
			$data = self::format_order_for_response( $order, $include_line_items );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			$formatted[] = $data;
		}
		return $formatted;
	}

	/**
	 * The shipping address fields of the output. The woocommerce_get_order_address
	 * filter can add other keys, and the closed output schema refuses them.
	 *
	 * @return string[]
	 */
	private static function shipping_address_fields(): array {
		return array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' );
	}

	/**
	 * Format an order for ability output.
	 *
	 * @param \WC_Order $order              Order object.
	 * @param bool      $include_line_items Whether to include line items and shipping lines.
	 * @return array|\WP_Error A WP_Error when an extension field value does not match its schema.
	 */
	protected static function format_order_for_response( \WC_Order $order, bool $include_line_items ) {
		return AbilityResources::output_data( 'order', $order, null, $include_line_items ? array() : array( 'line_items', 'shipping_lines' ) );
	}
}
