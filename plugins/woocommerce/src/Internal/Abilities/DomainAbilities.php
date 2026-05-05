<?php
/**
 * Domain Abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers canonical WooCommerce abilities backed by WooCommerce domain APIs.
 */
class DomainAbilities {

	/**
	 * Initialize ability registration.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	final public static function init(): void {
		/*
		 * Register abilities when Abilities API is ready.
		 * Support both old (pre-6.9) and new (6.9+) action names.
		 */
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register canonical domain abilities.
	 *
	 * @since 10.9.0
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_products_query();
		self::register_product_create();
		self::register_product_update();
		self::register_product_delete();
		self::register_orders_query();
		self::register_order_update_status();
		self::register_order_add_note();
	}

	/**
	 * Register the product query ability.
	 */
	private static function register_products_query(): void {
		if ( self::has_ability( 'woocommerce/products-query' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/products-query',
			array(
				'label'               => __( 'Query Products', 'woocommerce' ),
				'description'         => __(
					'Find WooCommerce products by ID or common catalog filters using WooCommerce product APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_products_query_input_schema(),
				'output_schema'       => self::get_collection_output_schema( 'products' ),
				'execute_callback'    => array( __CLASS__, 'execute_products_query' ),
				'permission_callback' => array( __CLASS__, 'can_query_products' ),
				'meta'                => self::get_domain_meta( 'query', true, true, false ),
			)
		);
	}

	/**
	 * Register the product creation ability.
	 */
	private static function register_product_create(): void {
		if ( self::has_ability( 'woocommerce/product-create' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/product-create',
			array(
				'label'               => __( 'Create Product', 'woocommerce' ),
				'description'         => __(
					'Create a WooCommerce product using WooCommerce product APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_product_create_input_schema(),
				'output_schema'       => self::get_entity_output_schema( 'product' ),
				'execute_callback'    => array( __CLASS__, 'execute_product_create' ),
				'permission_callback' => array( __CLASS__, 'can_create_product' ),
				'meta'                => self::get_domain_meta( 'create', false, false, false ),
			)
		);
	}

	/**
	 * Register the product update ability.
	 */
	private static function register_product_update(): void {
		if ( self::has_ability( 'woocommerce/product-update' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/product-update',
			array(
				'label'               => __( 'Update Product', 'woocommerce' ),
				'description'         => __(
					'Update an existing WooCommerce product using WooCommerce product APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_product_update_input_schema(),
				'output_schema'       => self::get_entity_output_schema( 'product' ),
				'execute_callback'    => array( __CLASS__, 'execute_product_update' ),
				'permission_callback' => array( __CLASS__, 'can_update_product' ),
				'meta'                => self::get_domain_meta( 'update', false, false, true ),
			)
		);
	}

	/**
	 * Register the product delete ability.
	 */
	private static function register_product_delete(): void {
		if ( self::has_ability( 'woocommerce/product-delete' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/product-delete',
			array(
				'label'               => __( 'Delete Product', 'woocommerce' ),
				'description'         => __(
					'Delete a WooCommerce product using WooCommerce product APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_product_delete_input_schema(),
				'output_schema'       => self::get_delete_output_schema(),
				'execute_callback'    => array( __CLASS__, 'execute_product_delete' ),
				'permission_callback' => array( __CLASS__, 'can_delete_product' ),
				'meta'                => self::get_domain_meta( 'delete', false, false, true ),
			)
		);
	}

	/**
	 * Register the order query ability.
	 */
	private static function register_orders_query(): void {
		if ( self::has_ability( 'woocommerce/orders-query' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/orders-query',
			array(
				'label'               => __( 'Query Orders', 'woocommerce' ),
				'description'         => __(
					'Find WooCommerce orders by ID or common order filters using WooCommerce order APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_orders_query_input_schema(),
				'output_schema'       => self::get_collection_output_schema( 'orders' ),
				'execute_callback'    => array( __CLASS__, 'execute_orders_query' ),
				'permission_callback' => array( __CLASS__, 'can_query_orders' ),
				'meta'                => self::get_domain_meta( 'query', true, true, false ),
			)
		);
	}

	/**
	 * Register the order status update ability.
	 */
	private static function register_order_update_status(): void {
		if ( self::has_ability( 'woocommerce/order-update-status' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/order-update-status',
			array(
				'label'               => __( 'Update Order Status', 'woocommerce' ),
				'description'         => __(
					'Update a WooCommerce order status using WooCommerce order APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_order_update_status_input_schema(),
				'output_schema'       => self::get_entity_output_schema( 'order' ),
				'execute_callback'    => array( __CLASS__, 'execute_order_update_status' ),
				'permission_callback' => array( __CLASS__, 'can_manage_orders' ),
				'meta'                => self::get_domain_meta( 'update-status', false, false, true ),
			)
		);
	}

	/**
	 * Register the order note creation ability.
	 */
	private static function register_order_add_note(): void {
		if ( self::has_ability( 'woocommerce/order-add-note' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/order-add-note',
			array(
				'label'               => __( 'Add Order Note', 'woocommerce' ),
				'description'         => __(
					'Add a note to a WooCommerce order using WooCommerce order APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_order_add_note_input_schema(),
				'output_schema'       => self::get_order_note_output_schema(),
				'execute_callback'    => array( __CLASS__, 'execute_order_add_note' ),
				'permission_callback' => array( __CLASS__, 'can_manage_orders' ),
				'meta'                => self::get_domain_meta( 'add-note', false, false, false ),
			)
		);
	}

	/**
	 * Query products.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_products_query( array $input ) {
		if ( ! empty( $input['id'] ) ) {
			$product = wc_get_product( absint( $input['id'] ) );

			if ( ! $product ) {
				return new \WP_Error(
					'woocommerce_product_not_found',
					__( 'Product not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}

			return array(
				'products' => array( self::format_product( $product ) ),
				'total'    => 1,
				'page'     => 1,
				'per_page' => 1,
			);
		}

		$page     = max( 1, absint( $input['page'] ?? 1 ) );
		$per_page = self::sanitize_per_page( $input['per_page'] ?? 10 );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
		);

		foreach ( array( 'status', 'type', 'sku', 'stock_status' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = wc_clean( wp_unslash( $input['search'] ) );
		}

		$results  = wc_get_products( $args );
		$products = is_object( $results ) && isset( $results->products ) ? $results->products : array();
		$total    = is_object( $results ) && isset( $results->total ) ? (int) $results->total : count( $products );

		return array(
			'products' => array_map( array( __CLASS__, 'format_product' ), $products ),
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Create a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_product_create( array $input ) {
		$type    = sanitize_key( $input['type'] ?? 'simple' );
		$product = wc_get_product_object( $type );

		if ( ! $product ) {
			return new \WP_Error(
				'woocommerce_invalid_product_type',
				__( 'Invalid product type.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		self::set_product_props( $product, $input );
		$product->save();

		return array(
			'product' => self::format_product( $product ),
		);
	}

	/**
	 * Update a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_product_update( array $input ) {
		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		self::set_product_props( $product, $input );
		$product->save();

		return array(
			'product' => self::format_product( $product ),
		);
	}

	/**
	 * Delete a product.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_product_delete( array $input ) {
		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		$product_id = $product->get_id();
		$deleted    = $product->delete( (bool) ( $input['force'] ?? false ) );

		return array(
			'deleted' => (bool) $deleted,
			'id'      => $product_id,
		);
	}

	/**
	 * Query orders.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_orders_query( array $input ) {
		$include_line_items = (bool) ( $input['include_line_items'] ?? false );

		if ( ! empty( $input['id'] ) ) {
			$order = wc_get_order( absint( $input['id'] ) );

			if ( ! $order instanceof \WC_Order ) {
				return new \WP_Error(
					'woocommerce_order_not_found',
					__( 'Order not found.', 'woocommerce' ),
					array( 'status' => 404 )
				);
			}

			return array(
				'orders'   => array( self::format_order( $order, $include_line_items ) ),
				'total'    => 1,
				'page'     => 1,
				'per_page' => 1,
			);
		}

		$page     = max( 1, absint( $input['page'] ?? 1 ) );
		$per_page = self::sanitize_per_page( $input['per_page'] ?? 10 );
		$args     = array(
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'return'   => 'objects',
			'type'     => 'shop_order',
		);

		foreach ( array( 'status', 'billing_email' ) as $field ) {
			if ( ! empty( $input[ $field ] ) ) {
				$args[ $field ] = wc_clean( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( ! empty( $input['customer_id'] ) ) {
			$args['customer_id'] = absint( $input['customer_id'] );
		}

		$results = wc_get_orders( $args );
		$orders  = is_object( $results ) && isset( $results->orders ) ? $results->orders : array();
		$orders  = array_values(
			array_filter(
				$orders,
				static function ( $order ): bool {
					return $order instanceof \WC_Order;
				}
			)
		);
		$total   = is_object( $results ) && isset( $results->total ) ? (int) $results->total : count( $orders );

		return array(
			'orders'   => array_map(
				static function ( $order ) use ( $include_line_items ) {
					return self::format_order( $order, $include_line_items );
				},
				$orders
			),
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Update an order status.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_order_update_status( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( absint( $input['id'] ) );

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		if ( empty( $input['status'] ) ) {
			return new \WP_Error(
				'woocommerce_order_status_required',
				__( 'Order status is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order->update_status(
			sanitize_key( $input['status'] ),
			isset( $input['note'] ) ? self::sanitize_string( $input['note'] ) : ''
		);

		return array(
			'order' => self::format_order( $order, false ),
		);
	}

	/**
	 * Add an order note.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 *
	 * @since 10.9.0
	 */
	public static function execute_order_add_note( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( absint( $input['id'] ) );

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		if ( empty( $input['note'] ) ) {
			return new \WP_Error(
				'woocommerce_order_note_required',
				__( 'Order note is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$note_id = $order->add_order_note(
			self::sanitize_string( $input['note'] ),
			( (bool) ( $input['customer_note'] ?? false ) ) ? 1 : 0
		);

		return array(
			'note_id' => (int) $note_id,
			'order'   => self::format_order( $order, false ),
		);
	}

	/**
	 * Check product read access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_query_products( $input = array() ): bool {
		$product_id = self::get_input_id( $input );

		return wc_rest_check_post_permissions( 'product', 'read', $product_id );
	}

	/**
	 * Check product creation access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_create_product( $input = array() ): bool {
		return wc_rest_check_post_permissions( 'product', 'create' );
	}

	/**
	 * Check product update access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_update_product( $input = array() ): bool {
		$product_id = self::get_input_id( $input );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'edit', $product_id );
	}

	/**
	 * Check product deletion access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_delete_product( $input = array() ): bool {
		$product_id = self::get_input_id( $input );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'delete', $product_id );
	}

	/**
	 * Check order read access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_query_orders( $input = array() ): bool {
		$order_id = self::get_input_id( $input );

		return wc_rest_check_post_permissions( 'shop_order', 'read', $order_id );
	}

	/**
	 * Check order management access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_manage_orders( $input = array() ): bool {
		$order_id = self::get_input_id( $input );

		return $order_id > 0 && wc_rest_check_post_permissions( 'shop_order', 'edit', $order_id );
	}

	/**
	 * Get common domain metadata.
	 *
	 * @param string $operation   Operation group.
	 * @param bool   $is_readonly Whether the ability is readonly.
	 * @param bool   $idempotent  Whether the ability is idempotent.
	 * @param bool   $destructive Whether the ability can mutate data.
	 * @return array
	 */
	private static function get_domain_meta( string $operation, bool $is_readonly, bool $idempotent, bool $destructive ): array {
		return array(
			'show_in_rest'                  => true,
			'mcp'                           => array(
				'public' => true,
				'type'   => 'tool',
			),
			'woocommerce_ability_source'    => 'domain-api',
			'woocommerce_ability_operation' => $operation,
			'annotations'                   => array(
				'readonly'    => $is_readonly,
				'idempotent'  => $idempotent,
				'destructive' => $destructive,
			),
		);
	}

	/**
	 * Get the products query input schema.
	 *
	 * @return array
	 */
	private static function get_products_query_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'           => array( 'type' => 'integer' ),
				'search'       => array( 'type' => 'string' ),
				'sku'          => array( 'type' => 'string' ),
				'status'       => array( 'type' => 'string' ),
				'type'         => array( 'type' => 'string' ),
				'stock_status' => array( 'type' => 'string' ),
				'page'         => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'     => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
	}

	/**
	 * Get the product create input schema.
	 *
	 * @return array
	 */
	private static function get_product_create_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'type'              => array( 'type' => 'string' ),
				'name'              => array( 'type' => 'string' ),
				'sku'               => array( 'type' => 'string' ),
				'regular_price'     => array( 'type' => 'string' ),
				'sale_price'        => array( 'type' => 'string' ),
				'description'       => array( 'type' => 'string' ),
				'short_description' => array( 'type' => 'string' ),
				'status'            => array( 'type' => 'string' ),
				'manage_stock'      => array( 'type' => 'boolean' ),
				'stock_quantity'    => array( 'type' => 'integer' ),
				'stock_status'      => array( 'type' => 'string' ),
				'virtual'           => array( 'type' => 'boolean' ),
				'downloadable'      => array( 'type' => 'boolean' ),
			),
			'required'             => array( 'name' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the product update input schema.
	 *
	 * @return array
	 */
	private static function get_product_update_input_schema(): array {
		$schema               = self::get_product_create_input_schema();
		$schema['properties'] = array_merge(
			array(
				'id' => array( 'type' => 'integer' ),
			),
			$schema['properties']
		);
		$schema['required']   = array( 'id' );

		return $schema;
	}

	/**
	 * Get the product delete input schema.
	 *
	 * @return array
	 */
	private static function get_product_delete_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'    => array( 'type' => 'integer' ),
				'force' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the orders query input schema.
	 *
	 * @return array
	 */
	private static function get_orders_query_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                 => array( 'type' => 'integer' ),
				'status'             => array( 'type' => 'string' ),
				'customer_id'        => array( 'type' => 'integer' ),
				'billing_email'      => array( 'type' => 'string' ),
				'include_line_items' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'page'               => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page'           => array(
					'type'    => 'integer',
					'default' => 10,
					'minimum' => 1,
					'maximum' => 100,
				),
			),
			'additionalProperties' => false,
			'default'              => array(),
		);
	}

	/**
	 * Get the order status update input schema.
	 *
	 * @return array
	 */
	private static function get_order_update_status_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'     => array( 'type' => 'integer' ),
				'status' => array( 'type' => 'string' ),
				'note'   => array( 'type' => 'string' ),
			),
			'required'             => array( 'id', 'status' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the order note input schema.
	 *
	 * @return array
	 */
	private static function get_order_add_note_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'            => array( 'type' => 'integer' ),
				'note'          => array( 'type' => 'string' ),
				'customer_note' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'required'             => array( 'id', 'note' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a collection output schema.
	 *
	 * @param string $collection_key Collection property key.
	 * @return array
	 */
	private static function get_collection_output_schema( string $collection_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$collection_key => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
				'total'         => array( 'type' => 'integer' ),
				'page'          => array( 'type' => 'integer' ),
				'per_page'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an entity output schema.
	 *
	 * @param string $entity_key Entity property key.
	 * @return array
	 */
	private static function get_entity_output_schema( string $entity_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$entity_key => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a delete output schema.
	 *
	 * @return array
	 */
	private static function get_delete_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'deleted' => array( 'type' => 'boolean' ),
				'id'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an order note output schema.
	 *
	 * @return array
	 */
	private static function get_order_note_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'note_id' => array( 'type' => 'integer' ),
				'order'   => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a product from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	private static function get_product_from_input( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_product_id_required',
				__( 'Product ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product = wc_get_product( absint( $input['id'] ) );

		if ( ! $product ) {
			return new \WP_Error(
				'woocommerce_product_not_found',
				__( 'Product not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $product;
	}

	/**
	 * Set supported product properties from ability input.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $input   Ability input.
	 */
	private static function set_product_props( \WC_Product $product, array $input ): void {
		$setters = array(
			'name'              => 'set_name',
			'sku'               => 'set_sku',
			'regular_price'     => 'set_regular_price',
			'sale_price'        => 'set_sale_price',
			'description'       => 'set_description',
			'short_description' => 'set_short_description',
			'status'            => 'set_status',
			'manage_stock'      => 'set_manage_stock',
			'stock_quantity'    => 'set_stock_quantity',
			'stock_status'      => 'set_stock_status',
			'virtual'           => 'set_virtual',
			'downloadable'      => 'set_downloadable',
		);

		foreach ( $setters as $field => $setter ) {
			if ( array_key_exists( $field, $input ) && is_callable( array( $product, $setter ) ) ) {
				$value = $input[ $field ];

				if ( is_string( $value ) ) {
					$value = wc_clean( wp_unslash( $value ) );
				}

				$product->{$setter}( $value );
			}
		}
	}

	/**
	 * Format a product for ability output.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	private static function format_product( \WC_Product $product ): array {
		return array(
			'id'             => $product->get_id(),
			'name'           => $product->get_name(),
			'slug'           => $product->get_slug(),
			'permalink'      => $product->get_permalink(),
			'type'           => $product->get_type(),
			'status'         => $product->get_status(),
			'sku'            => $product->get_sku(),
			'price'          => $product->get_price(),
			'regular_price'  => $product->get_regular_price(),
			'sale_price'     => $product->get_sale_price(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => null === $product->get_stock_quantity() ? null : (int) $product->get_stock_quantity(),
			'manage_stock'   => (bool) $product->get_manage_stock(),
			'virtual'        => (bool) $product->get_virtual(),
			'downloadable'   => (bool) $product->get_downloadable(),
			'date_created'   => self::format_datetime( $product->get_date_created() ),
			'date_modified'  => self::format_datetime( $product->get_date_modified() ),
		);
	}

	/**
	 * Format an order for ability output.
	 *
	 * @param \WC_Order $order              Order object.
	 * @param bool      $include_line_items Whether to include line items.
	 * @return array
	 */
	private static function format_order( \WC_Order $order, bool $include_line_items ): array {
		$data = array(
			'id'                   => $order->get_id(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'customer_id'          => $order->get_customer_id(),
			'billing_email'        => $order->get_billing_email(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'date_created'         => self::format_datetime( $order->get_date_created() ),
			'date_modified'        => self::format_datetime( $order->get_date_modified() ),
		);

		if ( $include_line_items ) {
			$data['line_items'] = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$data['line_items'][] = array(
					'id'           => $item->get_id(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity'     => $item->get_quantity(),
					'subtotal'     => $item->get_subtotal(),
					'total'        => $item->get_total(),
				);
			}
		}

		return $data;
	}

	/**
	 * Format a WooCommerce datetime.
	 *
	 * @param \WC_DateTime|null $datetime Date/time value.
	 * @return string|null
	 */
	private static function format_datetime( ?\WC_DateTime $datetime ): ?string {
		return $datetime ? $datetime->date( DATE_ATOM ) : null;
	}

	/**
	 * Get an ID value from ability input.
	 *
	 * @param mixed $input Ability input.
	 * @return int
	 */
	private static function get_input_id( $input ): int {
		return is_array( $input ) && ! empty( $input['id'] ) ? absint( $input['id'] ) : 0;
	}

	/**
	 * Sanitize a per-page value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	private static function sanitize_per_page( $value ): int {
		return min( 100, max( 1, absint( $value ) ) );
	}

	/**
	 * Sanitize a scalar string input value.
	 *
	 * @param mixed $value Raw input value.
	 * @return string
	 */
	private static function sanitize_string( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$clean = wc_clean( wp_unslash( (string) $value ) );

		return is_string( $clean ) ? $clean : '';
	}

	/**
	 * Check whether an ability is already registered.
	 *
	 * @param string $ability_id Ability ID.
	 * @return bool
	 */
	private static function has_ability( string $ability_id ): bool {
		return function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id );
	}
}
