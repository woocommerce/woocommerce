<?php
/**
 * Order resource class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The `order` and `order_item` resources and their fields.
 *
 * @since 11.4.0
 */
class OrderResource {

	public const NAME = 'order';

	public const ITEM = 'order_item';

	/**
	 * Register the order and its line items, which the `line_items` field refers to.
	 *
	 * @since 11.4.0
	 */
	public static function register(): void {
		AbilityResources::register_resource( self::NAME );
		AbilityResources::register_resource( self::ITEM );
		self::register_order_fields();
		self::register_item_fields();
	}

	/**
	 * Order types the abilities support: every registered order type except
	 * refunds and the HPOS placeholder.
	 *
	 * @since 11.4.0
	 *
	 * @return array<int, string>
	 */
	public static function get_type_slugs(): array {
		return array_values(
			array_diff(
				wc_get_order_types(),
				array( 'shop_order_refund', DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE )
			)
		);
	}

	/**
	 * Registered `wc-` statuses without the prefix, including those of other order types.
	 *
	 * @since 11.4.0
	 *
	 * @param array $args Arguments for get_post_stati().
	 * @return array<int, string>
	 */
	public static function get_registered_status_slugs( array $args = array() ): array {
		$statuses = array_filter(
			get_post_stati( $args ),
			static function ( $status ): bool {
				return 0 === strpos( $status, 'wc-' );
			}
		);

		return array_map( array( OrderUtil::class, 'remove_status_prefix' ), array_values( $statuses ) );
	}

	/**
	 * Every status slug (without the `wc-` prefix) an order in the output can have.
	 *
	 * @since 11.4.0
	 *
	 * @return array<int, string>
	 */
	public static function get_output_status_slugs(): array {
		return array_values(
			array_unique(
				array_merge(
					array_map(
						array( OrderUtil::class, 'remove_status_prefix' ),
						array_merge( OrderStatus::get_all(), array_keys( wc_get_order_statuses() ) )
					),
					self::get_registered_status_slugs()
				)
			)
		);
	}

	/**
	 * Register the fields of an order, in output order.
	 */
	private static function register_order_fields(): void {
		AbilityResources::register_field(
			self::NAME,
			'id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_id(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => self::get_output_status_slugs(),
				),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_status(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
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
			self::NAME,
			'currency_symbol',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'total',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_total(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'customer_id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_customer_id(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'billing_email',
			array(
				'schema'        => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'email',
				),
				'get_callback'  => static fn( \WC_Order $order ) => '' === $order->get_billing_email() ? null : $order->get_billing_email(),
				'personal_data' => true,
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'payment_method',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_payment_method(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'payment_method_title',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Order $order ) => $order->get_payment_method_title(),
			)
		);
		$date_schema = array(
			'type'   => array( 'string', 'null' ),
			'format' => 'date-time',
		);
		foreach ( array( 'date_created', 'date_modified' ) as $prop ) {
			AbilityResources::register_field(
				self::NAME,
				$prop,
				array(
					'schema'       => $date_schema,
					'get_callback' => static fn( \WC_Order $order ) => wc_rest_prepare_date_response( $order->{'get_' . $prop}(), false ),
				)
			);
			AbilityResources::register_field(
				self::NAME,
				$prop . '_gmt',
				array(
					'schema'       => $date_schema,
					'get_callback' => static fn( \WC_Order $order ) => wc_rest_prepare_date_response( $order->{'get_' . $prop}() ),
				)
			);
		}
		AbilityResources::register_field(
			self::NAME,
			'line_items',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Order line items. Only present when include_line_items is true.', 'woocommerce' ),
					'items'       => array( '$ref' => self::ITEM ),
				),
				'get_callback' => static fn( \WC_Order $order ) => array_values(
					array_filter(
						$order->get_items(),
						static fn( $item ) => $item instanceof \WC_Order_Item_Product
					)
				),
			)
		);
	}

	/**
	 * Register the fields of an order line item, in output order.
	 */
	private static function register_item_fields(): void {
		$types = array(
			'id'           => 'integer',
			'name'         => 'string',
			'product_id'   => 'integer',
			'variation_id' => 'integer',
			'quantity'     => 'integer',
			'subtotal'     => 'string',
			'total'        => 'string',
		);
		foreach ( $types as $prop => $type ) {
			AbilityResources::register_field(
				self::ITEM,
				$prop,
				array(
					'schema'       => array( 'type' => $type ),
					'get_callback' => static fn( \WC_Order_Item_Product $item ) => $item->{'get_' . $prop}(),
				)
			);
		}
	}
}
