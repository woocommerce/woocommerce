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
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_products_query();
		self::register_products_manage();
		self::register_orders_query();
		self::register_orders_manage();
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
				'permission_callback' => array( __CLASS__, 'can_query_woocommerce' ),
				'meta'                => self::get_domain_meta( 'query', true, true, false ),
			)
		);
	}

	/**
	 * Register the product management ability.
	 */
	private static function register_products_manage(): void {
		if ( self::has_ability( 'woocommerce/products-manage' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/products-manage',
			array(
				'label'               => __( 'Manage Products', 'woocommerce' ),
				'description'         => __(
					'Create, update, or delete WooCommerce products using WooCommerce product APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_products_manage_input_schema(),
				'output_schema'       => self::get_entity_output_schema( 'product' ),
				'execute_callback'    => array( __CLASS__, 'execute_products_manage' ),
				'permission_callback' => array( __CLASS__, 'can_manage_products' ),
				'meta'                => self::get_domain_meta( 'manage', false, false, true ),
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
				'permission_callback' => array( __CLASS__, 'can_query_woocommerce' ),
				'meta'                => self::get_domain_meta( 'query', true, true, false ),
			)
		);
	}

	/**
	 * Register the order management ability.
	 */
	private static function register_orders_manage(): void {
		if ( self::has_ability( 'woocommerce/orders-manage' ) ) {
			return;
		}

		wp_register_ability(
			'woocommerce/orders-manage',
			array(
				'label'               => __( 'Manage Orders', 'woocommerce' ),
				'description'         => __(
					'Update WooCommerce order status or add order notes using WooCommerce order APIs.',
					'woocommerce'
				),
				'category'            => 'woocommerce',
				'input_schema'        => self::get_orders_manage_input_schema(),
				'output_schema'       => self::get_entity_output_schema( 'order' ),
				'execute_callback'    => array( __CLASS__, 'execute_orders_manage' ),
				'permission_callback' => array( __CLASS__, 'can_manage_orders' ),
				'meta'                => self::get_domain_meta( 'manage', false, false, true ),
			)
		);
	}

	/**
	 * Query products.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
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
	 * Manage products.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute_products_manage( array $input ) {
		$action = sanitize_key( $input['action'] ?? '' );

		switch ( $action ) {
			case 'create':
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
					'action'  => 'create',
					'product' => self::format_product( $product ),
				);

			case 'update':
				$product = self::get_product_from_input( $input );

				if ( is_wp_error( $product ) ) {
					return $product;
				}

				self::set_product_props( $product, $input );
				$product->save();

				return array(
					'action'  => 'update',
					'product' => self::format_product( $product ),
				);

			case 'delete':
				$product = self::get_product_from_input( $input );

				if ( is_wp_error( $product ) ) {
					return $product;
				}

				$product_id = $product->get_id();
				$deleted    = $product->delete( (bool) ( $input['force'] ?? false ) );

				return array(
					'action'  => 'delete',
					'deleted' => (bool) $deleted,
					'id'      => $product_id,
				);
		}

		return new \WP_Error(
			'woocommerce_invalid_product_action',
			__( 'Invalid product management action.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Query orders.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute_orders_query( array $input ) {
		$include_line_items = (bool) ( $input['include_line_items'] ?? false );

		if ( ! empty( $input['id'] ) ) {
			$order = wc_get_order( absint( $input['id'] ) );

			if ( ! $order ) {
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
	 * Manage orders.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute_orders_manage( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( absint( $input['id'] ) );

		if ( ! $order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$action = sanitize_key( $input['action'] ?? '' );

		switch ( $action ) {
			case 'update_status':
				if ( empty( $input['status'] ) ) {
					return new \WP_Error(
						'woocommerce_order_status_required',
						__( 'Order status is required.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				$order->update_status(
					sanitize_key( $input['status'] ),
					isset( $input['note'] ) ? wc_clean( wp_unslash( $input['note'] ) ) : ''
				);

				return array(
					'action' => 'update_status',
					'order'  => self::format_order( $order, false ),
				);

			case 'add_note':
				if ( empty( $input['note'] ) ) {
					return new \WP_Error(
						'woocommerce_order_note_required',
						__( 'Order note is required.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				$note_id = $order->add_order_note(
					wc_clean( wp_unslash( $input['note'] ) ),
					(bool) ( $input['customer_note'] ?? false )
				);

				return array(
					'action'  => 'add_note',
					'note_id' => (int) $note_id,
					'order'   => self::format_order( $order, false ),
				);
		}

		return new \WP_Error(
			'woocommerce_invalid_order_action',
			__( 'Invalid order management action.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Check read access for WooCommerce data.
	 *
	 * @return bool
	 */
	public static function can_query_woocommerce(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'view_woocommerce_reports' );
	}

	/**
	 * Check product management access.
	 *
	 * @return bool
	 */
	public static function can_manage_products(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_products' );
	}

	/**
	 * Check order management access.
	 *
	 * @return bool
	 */
	public static function can_manage_orders(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
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
	 * Get the products manage input schema.
	 *
	 * @return array
	 */
	private static function get_products_manage_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'action'            => array(
					'type' => 'string',
					'enum' => array( 'create', 'update', 'delete' ),
				),
				'id'                => array( 'type' => 'integer' ),
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
				'force'             => array( 'type' => 'boolean' ),
			),
			'required'             => array( 'action' ),
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
	 * Get the orders manage input schema.
	 *
	 * @return array
	 */
	private static function get_orders_manage_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'action'        => array(
					'type' => 'string',
					'enum' => array( 'update_status', 'add_note' ),
				),
				'id'            => array( 'type' => 'integer' ),
				'status'        => array( 'type' => 'string' ),
				'note'          => array( 'type' => 'string' ),
				'customer_note' => array( 'type' => 'boolean' ),
			),
			'required'             => array( 'action', 'id' ),
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
				'action'    => array( 'type' => 'string' ),
				$entity_key => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
				'deleted'   => array( 'type' => 'boolean' ),
				'id'        => array( 'type' => 'integer' ),
				'note_id'   => array( 'type' => 'integer' ),
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
	 * Sanitize a per-page value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	private static function sanitize_per_page( $value ): int {
		return min( 100, max( 1, absint( $value ) ) );
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
