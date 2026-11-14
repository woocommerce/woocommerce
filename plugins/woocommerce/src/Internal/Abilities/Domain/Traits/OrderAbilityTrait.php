<?php
/**
 * Order ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
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
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                   => array( 'type' => 'integer' ),
				'number'               => array(
					'type'        => 'string',
					'description' => __( 'Order number shown to the customer. Extensions can make it differ from id.', 'woocommerce' ),
				),
				'type'                 => array(
					'type' => 'string',
					'enum' => self::get_order_type_slugs(),
				),
				'parent_id'            => array(
					'type'        => 'integer',
					'description' => __( 'ID of the parent order, or 0 when the order has none.', 'woocommerce' ),
				),
				'status'               => array(
					'type' => 'string',
					'enum' => self::get_order_output_status_slugs(),
				),
				'currency'             => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'currency_symbol'      => array( 'type' => 'string' ),
				'total'                => array( 'type' => 'string' ),
				'discount_total'       => array( 'type' => 'string' ),
				'shipping_total'       => array( 'type' => 'string' ),
				'total_tax'            => array( 'type' => 'string' ),
				'customer_id'          => array( 'type' => 'integer' ),
				'billing_email'        => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'Billing email. Omitted when no billing email is set.', 'woocommerce' ),
				),
				'payment_method'       => array( 'type' => 'string' ),
				'payment_method_title' => array( 'type' => 'string' ),
				'date_created'         => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order creation date in the store timezone. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'date_created_gmt'     => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order creation date in UTC. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'date_modified'        => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order modification date in the store timezone. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'date_modified_gmt'    => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order modification date in UTC. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'date_paid'            => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Payment date in the store timezone. Kept after a later status change, such as a refund. Omitted when never paid.', 'woocommerce' ),
				),
				'date_paid_gmt'        => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'date_paid in UTC.', 'woocommerce' ),
				),
				'date_completed'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Completion date in the store timezone. Kept after a later status change, such as a refund. Omitted when never completed.', 'woocommerce' ),
				),
				'date_completed_gmt'   => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'date_completed in UTC.', 'woocommerce' ),
				),
				'line_items'           => array(
					'type'        => 'array',
					'description' => __( 'Order line items. Only present when include_line_items is true.', 'woocommerce' ),
					'items'       => ExtensibleAbilities::add_fields_schema(
						array(
							'type'                 => 'object',
							'properties'           => array(
								'id'           => array( 'type' => 'integer' ),
								'name'         => array( 'type' => 'string' ),
								'product_id'   => array( 'type' => 'integer' ),
								'variation_id' => array( 'type' => 'integer' ),
								'sku'          => array(
									'type'        => 'string',
									'description' => __( 'Omitted when the product no longer exists.', 'woocommerce' ),
								),
								'quantity'     => array( 'type' => 'number' ),
								'price'        => array(
									'type'        => 'string',
									'description' => __( 'Line total divided by quantity, after discounts and before tax.', 'woocommerce' ),
								),
								'tax_class'    => array( 'type' => 'string' ),
								'subtotal'     => array( 'type' => 'string' ),
								'subtotal_tax' => array( 'type' => 'string' ),
								'total'        => array( 'type' => 'string' ),
								'total_tax'    => array( 'type' => 'string' ),
							),
							'additionalProperties' => false,
						),
						'order_item'
					),
				),
				'shipping'             => array(
					'type'                 => 'object',
					'description'          => __( 'Order shipping address. Countries use ISO 3166-1 alpha-2 codes, and states use WooCommerce state codes.', 'woocommerce' ),
					'properties'           => array_fill_keys( self::shipping_address_fields(), array( 'type' => 'string' ) ),
					'additionalProperties' => false,
				),
				'shipping_lines'       => self::get_order_lines_schema(
					array(
						'id'           => array( 'type' => 'integer' ),
						'method_title' => array( 'type' => 'string' ),
						'method_id'    => array( 'type' => 'string' ),
						'instance_id'  => array( 'type' => 'string' ),
						'total'        => array( 'type' => 'string' ),
						'total_tax'    => array( 'type' => 'string' ),
					)
				),
				'fee_lines'            => self::get_order_lines_schema(
					array(
						'id'         => array( 'type' => 'integer' ),
						'name'       => array( 'type' => 'string' ),
						'tax_class'  => array( 'type' => 'string' ),
						'tax_status' => array(
							'type' => 'string',
							'enum' => array( 'taxable', 'none' ),
						),
						'total'      => array( 'type' => 'string' ),
						'total_tax'  => array( 'type' => 'string' ),
					)
				),
				'coupon_lines'         => self::get_order_lines_schema(
					array(
						'id'           => array( 'type' => 'integer' ),
						'code'         => array( 'type' => 'string' ),
						'discount'     => array( 'type' => 'string' ),
						'discount_tax' => array( 'type' => 'string' ),
					)
				),
				'tax_lines'            => self::get_order_lines_schema(
					array(
						'id'                 => array( 'type' => 'integer' ),
						'rate_code'          => array( 'type' => 'string' ),
						'rate_id'            => array( 'type' => 'integer' ),
						'label'              => array( 'type' => 'string' ),
						'compound'           => array( 'type' => 'boolean' ),
						'tax_total'          => array( 'type' => 'string' ),
						'shipping_tax_total' => array( 'type' => 'string' ),
					)
				),
			),
			'additionalProperties' => false,
		);
		return ExtensibleAbilities::add_fields_schema( $schema, 'order' );
	}

	/**
	 * Schema of one kind of order line other than line items. The include_lines input describes when they are present.
	 *
	 * @param array $properties Properties of one line.
	 * @return array
	 */
	private static function get_order_lines_schema( array $properties ): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => $properties,
				'additionalProperties' => false,
			),
		);
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
			return (bool) wc_rest_check_post_permissions( 'shop_order', 'edit' === $context ? 'batch' : $context );
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

		return self::format_order_for_response( $stored instanceof \WC_Order ? $stored : $order, array() );
	}

	/**
	 * Format orders for ability output. One order that fails returns its error.
	 *
	 * @param \WC_Order[] $orders   Order objects.
	 * @param string[]    $includes Optional parts, as for format_order_for_response().
	 * @return array|\WP_Error
	 */
	protected static function format_orders_for_response( array $orders, array $includes ) {
		if ( in_array( 'line_items', $includes, true ) ) {
			$product_ids = array();
			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item instanceof \WC_Order_Item_Product ) {
						$product_ids[] = $item->get_product_id();
						$product_ids[] = $item->get_variation_id();
					}
				}
			}
			$product_ids = array_unique( array_filter( $product_ids ) );
			if ( ! empty( $product_ids ) ) {
				// Prime caches to reduce future queries.
				_prime_post_caches( $product_ids );
			}
		}

		$formatted = array();
		foreach ( $orders as $order ) {
			$data = self::format_order_for_response( $order, $includes );
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
	 * @param \WC_Order $order    Order object.
	 * @param string[]  $includes Optional parts: `line_items`, and `lines` for the shipping, fee, coupon and tax lines.
	 * @return array|\WP_Error A WP_Error when an extension field value does not match its schema.
	 */
	protected static function format_order_for_response( \WC_Order $order, array $includes ) {
		$billing_email = $order->get_billing_email();

		$data = array(
			'id'                   => $order->get_id(),
			'number'               => (string) $order->get_order_number(),
			'type'                 => $order->get_type(),
			'parent_id'            => $order->get_parent_id(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'currency_symbol'      => html_entity_decode(
				get_woocommerce_currency_symbol( $order->get_currency() ),
				ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
			),
			'total'                => self::format_amount_for_response( $order->get_total() ),
			'discount_total'       => self::format_amount_for_response( $order->get_discount_total() ),
			'shipping_total'       => self::format_amount_for_response( $order->get_shipping_total() ),
			'total_tax'            => self::format_amount_for_response( $order->get_total_tax() ),
			'customer_id'          => $order->get_customer_id(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'shipping'             => array_intersect_key( $order->get_address( 'shipping' ), array_flip( self::shipping_address_fields() ) ),
		);

		if ( in_array( 'line_items', $includes, true ) ) {
			$data['line_items'] = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$line_item = ExtensibleAbilities::add_fields_to_object(
					self::format_line_item_for_response( $item ),
					'order_item',
					$item
				);
				if ( is_wp_error( $line_item ) ) {
					return $line_item;
				}
				$data['line_items'][] = $line_item;
			}
		}

		if ( in_array( 'lines', $includes, true ) ) {
			$data += self::format_order_lines_for_response( $order );
		}

		if ( '' !== $billing_email ) {
			$data['billing_email'] = $billing_email;
		}

		$data += array_filter(
			array(
				'date_created'       => self::format_date_for_response( $order->get_date_created(), false ),
				'date_created_gmt'   => self::format_date_for_response( $order->get_date_created() ),
				'date_modified'      => self::format_date_for_response( $order->get_date_modified(), false ),
				'date_modified_gmt'  => self::format_date_for_response( $order->get_date_modified() ),
				'date_paid'          => self::format_date_for_response( $order->get_date_paid(), false ),
				'date_paid_gmt'      => self::format_date_for_response( $order->get_date_paid() ),
				'date_completed'     => self::format_date_for_response( $order->get_date_completed(), false ),
				'date_completed_gmt' => self::format_date_for_response( $order->get_date_completed() ),
			)
		);

		return ExtensibleAbilities::add_fields_to_object( $data, 'order', $order );
	}

	/**
	 * Format a product line of an order, before extension fields are added.
	 *
	 * @param \WC_Order_Item_Product $item Product line.
	 * @return array
	 */
	private static function format_line_item_for_response( \WC_Order_Item_Product $item ): array {
		$quantity = $item->get_quantity();
		$product  = $item->get_product();

		$line_item = array(
			'id'           => $item->get_id(),
			'name'         => $item->get_name(),
			'product_id'   => $item->get_product_id(),
			'variation_id' => $item->get_variation_id(),
			'quantity'     => $quantity,
			'price'        => self::format_amount_for_response( $quantity ? (float) $item->get_total() / $quantity : 0 ),
			'tax_class'    => $item->get_tax_class(),
			'subtotal'     => self::format_amount_for_response( $item->get_subtotal() ),
			'subtotal_tax' => self::format_amount_for_response( $item->get_subtotal_tax() ),
			'total'        => self::format_amount_for_response( $item->get_total() ),
			'total_tax'    => self::format_amount_for_response( $item->get_total_tax() ),
		);

		if ( $product instanceof \WC_Product ) {
			$line_item['sku'] = $product->get_sku();
		}

		return $line_item;
	}

	/**
	 * Format the shipping, fee, coupon and tax lines of an order, with the keys of wc/v3/orders.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array
	 */
	private static function format_order_lines_for_response( \WC_Order $order ): array {
		return array(
			'shipping_lines' => array_values(
				array_map(
					static function ( \WC_Order_Item_Shipping $item ): array {
						return array(
							'id'           => $item->get_id(),
							'method_title' => $item->get_method_title(),
							'method_id'    => $item->get_method_id(),
							'instance_id'  => (string) $item->get_instance_id(),
							'total'        => self::format_amount_for_response( $item->get_total() ),
							'total_tax'    => self::format_amount_for_response( $item->get_total_tax() ),
						);
					},
					$order->get_shipping_methods()
				)
			),
			'fee_lines'      => array_values(
				array_map(
					static function ( \WC_Order_Item_Fee $item ): array {
						return array(
							'id'         => $item->get_id(),
							'name'       => $item->get_name(),
							'tax_class'  => $item->get_tax_class(),
							'tax_status' => $item->get_tax_status(),
							'total'      => self::format_amount_for_response( $item->get_total() ),
							'total_tax'  => self::format_amount_for_response( $item->get_total_tax() ),
						);
					},
					$order->get_fees()
				)
			),
			'coupon_lines'   => array_values(
				array_map(
					static function ( \WC_Order_Item_Coupon $item ): array {
						return array(
							'id'           => $item->get_id(),
							'code'         => $item->get_code(),
							'discount'     => self::format_amount_for_response( $item->get_discount() ),
							'discount_tax' => self::format_amount_for_response( $item->get_discount_tax() ),
						);
					},
					$order->get_coupons()
				)
			),
			'tax_lines'      => array_values(
				array_map(
					static function ( \WC_Order_Item_Tax $item ): array {
						return array(
							'id'                 => $item->get_id(),
							'rate_code'          => $item->get_rate_code(),
							'rate_id'            => $item->get_rate_id(),
							'label'              => $item->get_label(),
							'compound'           => $item->is_compound(),
							'tax_total'          => self::format_amount_for_response( $item->get_tax_total() ),
							'shipping_tax_total' => self::format_amount_for_response( $item->get_shipping_tax_total() ),
						);
					},
					$order->get_taxes()
				)
			),
		);
	}
}
