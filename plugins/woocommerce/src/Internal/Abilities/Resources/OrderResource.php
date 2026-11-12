<?php
/**
 * OrderResource class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Enums\OrderStatus;
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

	/**
	 * Register order and line item fields.
	 */
	public static function register(): void {
		AbilityResources::register_resource( self::NAME );
		AbilityResources::register_resource( self::ITEM );

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
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_customer_id(),
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
				'personal_data' => true,
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
				'schema'       => array(
					'type'                 => 'object',
					'description'          => __( 'Order shipping address. Countries use ISO 3166-1 alpha-2 codes, and states use WooCommerce state codes.', 'woocommerce' ),
					'properties'           => array_fill_keys( self::shipping_address_fields(), array( 'type' => 'string' ) ),
					'additionalProperties' => false,
				),
				'get_callback' => static fn( \WC_Order $order ) => array_intersect_key( $order->get_address( 'shipping' ), array_flip( self::shipping_address_fields() ) ),
			)
		);

		AbilityResources::register_field(
			'order',
			'shipping_lines',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Order shipping lines. Present together with line_items.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'           => array( 'type' => 'integer' ),
							'method_id'    => array( 'type' => 'string' ),
							'method_title' => array( 'type' => 'string' ),
							'total'        => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
				'get_callback' => static fn( \WC_Order $order ) => array_values(
					array_map(
						static function ( \WC_Order_Item_Shipping $item ): array {
						return array(
							'id'           => $item->get_id(),
							'method_id'    => $item->get_method_id(),
							'method_title' => $item->get_method_title(),
							'total'        => self::format_amount_for_response( $item->get_total() ),
						);
						},
						$order->get_shipping_methods()
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
	 * The shipping address fields of the output. The woocommerce_get_order_address
	 * filter can add other keys, and the closed output schema refuses them.
	 *
	 * @return string[]
	 */
	private static function shipping_address_fields(): array {
		return array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' );
	}
}
