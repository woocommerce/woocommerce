<?php
/**
 * OrderResource class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Fields returned by the resource abilities.
 *
 * @since 11.4.0
 */
class OrderResource {

	use DomainAbilityHelpers;

	public const NAME = 'order';
	public const ITEM = 'order_item';

	public const SHIPPING = 'order_shipping';

	/**
	 * Register order and line item fields.
	 */
	public static function register(): void {
		AbilityResources::register_resource( self::NAME );
		AbilityResources::register_resource( self::ITEM );
		self::register_shipping();

		AbilityResources::register_field(
			'order',
			'id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_id(),
			)
		);

		AbilityResources::register_field(
			'order',
			'status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => self::get_order_output_status_slugs(),
				),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_status(),
			)
		);

		AbilityResources::register_field(
			'order',
			'currency',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_currency(),
			)
		);

		AbilityResources::register_field(
			'order',
			'currency_symbol',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => html_entity_decode(
					get_woocommerce_currency_symbol( $order->get_currency() ),
					ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
				),
			)
		);

		AbilityResources::register_field(
			'order',
			'total',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => self::format_amount_for_response( $order->get_total() ),
			)
		);

		AbilityResources::register_field(
			'order',
			'customer_id',
			array(
				'schema'        => array( 'type' => 'integer' ),
				'get_callback'  => static fn( \WC_Order $order ) => $order->get_customer_id(),
				'personal_data' => 'numeric_id',
			)
		);

		AbilityResources::register_field(
			'order',
			'billing_email',
			array(
				'schema'        => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'Billing email. Omitted when no billing email is set.', 'woocommerce' ),
				),
				'get_callback'  => static fn( \WC_Order $order ) => '' === $order->get_billing_email() ? null : $order->get_billing_email(),
				'omit_if_null'  => true,
				'personal_data' => 'email',
			)
		);

		AbilityResources::register_field(
			'order',
			'payment_method',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_payment_method(),
			)
		);

		AbilityResources::register_field(
			'order',
			'payment_method_title',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_payment_method_title(),
			)
		);

		AbilityResources::register_field(
			'order',
			'date_created',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order creation date in the store timezone. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_created(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_created_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order creation date in UTC. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_created() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_modified',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order modification date in the store timezone. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_modified(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_modified_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Order modification date in UTC. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_modified() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'line_items',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Order line items. Only present when include_line_items is true.', 'woocommerce' ),
					'items'       => array( '$ref' => 'order_item' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => array_values( array_filter( $order->get_items(), static fn( $item ) => $item instanceof \WC_Order_Item_Product ) ),
			)
		);

		AbilityResources::register_field(
			'order',
			'shipping',
			array(
				'schema'        => array(
					'$ref'        => self::SHIPPING,
					'exclude'     => array( 'extensions' ),
					'description' => __( 'Order shipping address. Countries use ISO 3166-1 alpha-2 codes, and states use WooCommerce state codes.', 'woocommerce' ),
				),
				'get_callback'  => static fn( \WC_Order $order ) => $order->get_address( 'shipping' ),
				// Every member is personal; the order policy must also protect this nested resource.
				'personal_data' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'number',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Order number shown to the customer. Extensions can make it differ from id.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => (string) $order->get_order_number(),
			)
		);

		AbilityResources::register_field(
			'order',
			'type',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => self::get_order_type_slugs(),
				),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_type(),
			)
		);

		AbilityResources::register_field(
			'order',
			'parent_id',
			array(
				'schema'       => array(
					'type'        => 'integer',
					'description' => __( 'ID of the parent order, or 0 when the order has none.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_parent_id(),
			)
		);

		AbilityResources::register_field(
			'order',
			'discount_total',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => self::format_amount_for_response( $order->get_discount_total() ),
			)
		);

		AbilityResources::register_field(
			'order',
			'shipping_total',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => self::format_amount_for_response( $order->get_shipping_total() ),
			)
		);

		AbilityResources::register_field(
			'order',
			'total_tax',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => self::format_amount_for_response( $order->get_total_tax() ),
			)
		);

		AbilityResources::register_field(
			'order',
			'date_paid',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Payment date in the store timezone. Kept after a later status change, such as a refund. Omitted when never paid.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_paid(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_paid_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'date_paid in UTC.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_paid() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_completed',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Completion date in the store timezone. Kept after a later status change, such as a refund. Omitted when never completed.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_completed(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'date_completed_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'date_completed in UTC.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order $order ) => self::format_date_for_response( $order->get_date_completed() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order',
			'shipping_lines',
			array(
				'schema'       => self::get_order_lines_schema(
					array(
						'id'           => array( 'type' => 'integer' ),
						'method_title' => array( 'type' => 'string' ),
						'method_id'    => array( 'type' => 'string' ),
						'instance_id'  => array( 'type' => 'string' ),
						'total'        => array( 'type' => 'string' ),
						'total_tax'    => array( 'type' => 'string' ),
					)
				),
				'get_callback' => static fn( \WC_Order $order ) => array_values(
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
			)
		);

		AbilityResources::register_field(
			'order',
			'fee_lines',
			array(
				'schema'       => self::get_order_lines_schema(
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
				'get_callback' => static fn( \WC_Order $order ) => array_values(
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
			)
		);

		AbilityResources::register_field(
			'order',
			'coupon_lines',
			array(
				'schema'       => self::get_order_lines_schema(
					array(
						'id'           => array( 'type' => 'integer' ),
						'code'         => array( 'type' => 'string' ),
						'discount'     => array( 'type' => 'string' ),
						'discount_tax' => array( 'type' => 'string' ),
					)
				),
				'get_callback' => static fn( \WC_Order $order ) => array_values(
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
			)
		);

		AbilityResources::register_field(
			'order',
			'tax_lines',
			array(
				'schema'       => self::get_order_lines_schema(
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
				'get_callback' => static fn( \WC_Order $order ) => array_values(
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
			)
		);

		AbilityResources::register_field(
			'order_item',
			'id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_id(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'name',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_name(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'product_id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_product_id(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'variation_id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_variation_id(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'quantity',
			array(
				'schema'       => array( 'type' => 'number' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_quantity(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'subtotal',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => self::format_amount_for_response( $item->get_subtotal() ),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'total',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => self::format_amount_for_response( $item->get_total() ),
			)
		);
		AbilityResources::register_field(
			'order_item',
			'sku',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Omitted when the product no longer exists.', 'woocommerce' ),
				),
				'get_callback' => static function ( \WC_Order_Item_Product $item ) {
					$product = $item->get_product();
					return $product instanceof \WC_Product ? $product->get_sku() : null;
				},
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'order_item',
			'price',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Line total divided by quantity, after discounts and before tax.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => self::format_amount_for_response( $item->get_quantity() ? (float) $item->get_total() / $item->get_quantity() : 0 ),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'tax_class',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->get_tax_class(),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'subtotal_tax',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => self::format_amount_for_response( $item->get_subtotal_tax() ),
			)
		);

		AbilityResources::register_field(
			'order_item',
			'total_tax',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order_Item_Product $item ) => self::format_amount_for_response( $item->get_total_tax() ),
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
	 * Register shipping attributes using the Core order eraser's property classifications.
	 */
	private static function register_shipping(): void {
		AbilityResources::register_resource( self::SHIPPING );
		$types = array(
			'first_name' => 'text',
			'last_name'  => 'text',
			'company'    => 'text',
			'address_1'  => 'text',
			'address_2'  => 'text',
			'city'       => 'text',
			'state'      => 'address_state',
			'postcode'   => 'text',
			'country'    => 'address_country',
			'phone'      => 'phone',
		);
		foreach ( $types as $field => $type ) {
			AbilityResources::register_field(
				self::SHIPPING,
				$field,
				array(
					'schema'        => array( 'type' => 'string' ),
					'get_callback'  => static fn( array $address ) => $address[ $field ] ?? null,
					'omit_if_null'  => true,
					'personal_data' => $type,
				)
			);
		}
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
}
