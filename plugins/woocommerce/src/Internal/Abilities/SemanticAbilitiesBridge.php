<?php
/**
 * Semantic abilities bridge (experimental).
 *
 * Pattern B for the abilities-rollout verification loop: bundles CRUD operations
 * per resource into a small set of semantic abilities. Intentionally narrows
 * output schemas to the fields most consumers actually need, rather than
 * reflecting the full REST controller shape. Registered only on MCP requests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Semantic abilities bridge.
 */
class SemanticAbilitiesBridge {

	/**
	 * Initialize.
	 */
	final public static function init(): void {
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register all semantic abilities, gated on MCP request context.
	 */
	public static function register_abilities(): void {
		if ( ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( self::get_abilities() as $id => $args ) {
			wp_register_ability( $id, $args );
		}
	}

	/**
	 * Ability definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_abilities(): array {
		return array(
			'woocommerce/products-query'  => self::products_query(),
			'woocommerce/products-manage' => self::products_manage(),
			'woocommerce/orders-query'    => self::orders_query(),
			'woocommerce/orders-manage'   => self::orders_manage(),
		);
	}

	/**
	 * Compact product shape returned by query operations.
	 */
	private static function product_summary_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'           => array( 'type' => 'integer' ),
				'name'         => array( 'type' => 'string' ),
				'sku'          => array( 'type' => 'string' ),
				'status'       => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ) ),
				'type'         => array( 'type' => 'string' ),
				'price'        => array( 'type' => 'string', 'description' => 'Current price in store currency.' ),
				'regular_price' => array( 'type' => 'string' ),
				'sale_price'   => array( 'type' => 'string' ),
				'stock_status' => array( 'type' => 'string', 'enum' => array( 'instock', 'outofstock', 'onbackorder' ) ),
				'stock_quantity' => array( 'type' => array( 'integer', 'null' ) ),
			),
			'required'   => array( 'id', 'name', 'status' ),
		);
	}

	/**
	 * Compact order shape returned by query operations.
	 */
	private static function order_summary_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'               => array( 'type' => 'integer' ),
				'status'           => array( 'type' => 'string' ),
				'total'            => array( 'type' => 'string' ),
				'currency'         => array( 'type' => 'string' ),
				'customer_id'      => array( 'type' => 'integer' ),
				'customer_email'   => array( 'type' => 'string' ),
				'date_created'     => array( 'type' => 'string', 'format' => 'date-time' ),
				'line_item_count'  => array( 'type' => 'integer' ),
				'payment_method'   => array( 'type' => 'string' ),
			),
			'required'   => array( 'id', 'status', 'total' ),
		);
	}

	private static function manage_result_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'id'      => array( 'type' => 'integer', 'description' => 'Affected resource id, when applicable.' ),
				'message' => array( 'type' => 'string' ),
			),
			'required'   => array( 'success' ),
		);
	}

	private static function products_query(): array {
		return array(
			'label'               => __( 'Query Products', 'woocommerce' ),
			'description'         => __( 'List or fetch products. Provide id for a single product, or filter fields for a list.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array( 'type' => 'integer', 'description' => 'If set, returns the single product with this id.' ),
					'search'   => array( 'type' => 'string' ),
					'status'   => array( 'type' => 'string', 'enum' => array( 'any', 'draft', 'pending', 'private', 'publish' ) ),
					'sku'      => array( 'type' => 'string' ),
					'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
					'page'     => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'items' => array( 'type' => 'array', 'items' => self::product_summary_schema() ),
					'total' => array( 'type' => 'integer' ),
				),
				'required'   => array( 'items' ),
			),
			'execute_callback'    => static function ( $input ) {
				return array( 'items' => array(), 'total' => 0 );
			},
			'permission_callback' => static function () {
				return current_user_can( 'read_private_products' ) || current_user_can( 'read' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		);
	}

	private static function products_manage(): array {
		return array(
			'label'               => __( 'Manage Products', 'woocommerce' ),
			'description'         => __( 'Create, update, or delete a product.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'action' => array( 'type' => 'string', 'enum' => array( 'create', 'update', 'delete' ) ),
					'id'     => array( 'type' => 'integer', 'description' => 'Required for update/delete.' ),
					'fields' => array(
						'type'        => 'object',
						'description' => 'Writable product fields for create/update. Only provide keys that need to change.',
						'properties'  => array(
							'name'           => array( 'type' => 'string' ),
							'sku'            => array( 'type' => 'string' ),
							'type'           => array( 'type' => 'string', 'enum' => array( 'simple', 'grouped', 'external', 'variable' ) ),
							'status'         => array( 'type' => 'string', 'enum' => array( 'draft', 'pending', 'private', 'publish' ) ),
							'regular_price'  => array( 'type' => 'string' ),
							'sale_price'     => array( 'type' => 'string' ),
							'manage_stock'   => array( 'type' => 'boolean' ),
							'stock_quantity' => array( 'type' => array( 'integer', 'null' ) ),
							'description'    => array( 'type' => 'string' ),
							'short_description' => array( 'type' => 'string' ),
						),
					),
				),
				'required'   => array( 'action' ),
			),
			'output_schema'       => self::manage_result_schema(),
			'execute_callback'    => static function ( $input ) {
				return array( 'success' => false, 'message' => 'Stub implementation for measurement only.' );
			},
			'permission_callback' => static function () {
				return current_user_can( 'manage_woocommerce' );
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}

	private static function orders_query(): array {
		return array(
			'label'               => __( 'Query Orders', 'woocommerce' ),
			'description'         => __( 'List or fetch orders. Provide id for a single order, or filter fields for a list.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'          => array( 'type' => 'integer', 'description' => 'If set, returns the single order with this id.' ),
					'status'      => array( 'type' => 'string', 'description' => 'Any, or a WooCommerce order status slug.' ),
					'customer'    => array( 'type' => 'integer', 'description' => 'Customer user id.' ),
					'after'       => array( 'type' => 'string', 'format' => 'date-time' ),
					'before'      => array( 'type' => 'string', 'format' => 'date-time' ),
					'per_page'    => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10 ),
					'page'        => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'items' => array( 'type' => 'array', 'items' => self::order_summary_schema() ),
					'total' => array( 'type' => 'integer' ),
				),
				'required'   => array( 'items' ),
			),
			'execute_callback'    => static function ( $input ) {
				return array( 'items' => array(), 'total' => 0 );
			},
			'permission_callback' => static function () {
				return current_user_can( 'edit_shop_orders' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		);
	}

	private static function orders_manage(): array {
		return array(
			'label'               => __( 'Manage Orders', 'woocommerce' ),
			'description'         => __( 'Create or update an order. Deletion is intentionally not exposed.', 'woocommerce' ),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'action' => array( 'type' => 'string', 'enum' => array( 'create', 'update' ) ),
					'id'     => array( 'type' => 'integer', 'description' => 'Required for update.' ),
					'fields' => array(
						'type'        => 'object',
						'description' => 'Writable order fields for create/update.',
						'properties'  => array(
							'status'         => array( 'type' => 'string' ),
							'customer_id'    => array( 'type' => 'integer' ),
							'customer_note'  => array( 'type' => 'string' ),
							'payment_method' => array( 'type' => 'string' ),
							'line_items'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'product_id' => array( 'type' => 'integer' ),
										'quantity'   => array( 'type' => 'integer', 'minimum' => 1 ),
									),
									'required'   => array( 'product_id', 'quantity' ),
								),
							),
						),
					),
				),
				'required'   => array( 'action' ),
			),
			'output_schema'       => self::manage_result_schema(),
			'execute_callback'    => static function ( $input ) {
				return array( 'success' => false, 'message' => 'Stub implementation for measurement only.' );
			},
			'permission_callback' => static function () {
				return current_user_can( 'edit_shop_orders' );
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}
}
