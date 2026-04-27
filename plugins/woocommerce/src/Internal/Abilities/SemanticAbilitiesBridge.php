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
	 * Products REST route.
	 */
	private const PRODUCTS_ROUTE = '/wc/v3/products';

	/**
	 * Orders REST route.
	 */
	private const ORDERS_ROUTE = '/wc/v3/orders';

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

	private static function error_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'code'    => array( 'type' => 'string' ),
				'message' => array( 'type' => 'string' ),
				'status'  => array( 'type' => 'integer' ),
			),
			'required'   => array( 'code', 'message' ),
		);
	}

	private static function query_result_schema( array $item_schema ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'items'   => array( 'type' => 'array', 'items' => $item_schema ),
				'total'   => array( 'type' => 'integer' ),
				'error'   => self::error_schema(),
			),
			'required'   => array( 'success', 'items' ),
		);
	}

	private static function manage_result_schema( array $item_schema ): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'id'      => array( 'type' => 'integer', 'description' => 'Affected resource id, when applicable.' ),
				'message' => array( 'type' => 'string' ),
				'item'    => $item_schema,
				'deleted' => array( 'type' => 'boolean' ),
				'error'   => self::error_schema(),
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
			'output_schema'       => self::query_result_schema( self::product_summary_schema() ),
			'execute_callback'    => static function ( $input ) {
				return self::query_products( self::normalize_input( $input ) );
			},
			'permission_callback' => static function () {
				return self::can_read_products();
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
							'categories'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array( 'type' => 'integer' ),
									),
									'required'   => array( 'id' ),
								),
							),
							'tags'           => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array( 'type' => 'integer' ),
									),
									'required'   => array( 'id' ),
								),
							),
							'images'         => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'id'  => array( 'type' => 'integer' ),
										'src' => array( 'type' => 'string', 'format' => 'uri' ),
										'alt' => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
					'force'  => array( 'type' => 'boolean', 'default' => true, 'description' => 'Delete permanently when action is delete.' ),
				),
				'required'   => array( 'action' ),
			),
			'output_schema'       => self::manage_result_schema( self::product_summary_schema() ),
			'execute_callback'    => static function ( $input ) {
				return self::manage_product( self::normalize_input( $input ) );
			},
			'permission_callback' => static function () {
				return self::can_manage_products();
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
			'output_schema'       => self::query_result_schema( self::order_summary_schema() ),
			'execute_callback'    => static function ( $input ) {
				return self::query_orders( self::normalize_input( $input ) );
			},
			'permission_callback' => static function () {
				return self::can_read_orders();
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
							'payment_method_title' => array( 'type' => 'string' ),
							'set_paid'       => array( 'type' => 'boolean' ),
							'billing'        => self::address_schema(),
							'shipping'       => self::address_schema(),
							'line_items'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'product_id'   => array( 'type' => 'integer' ),
										'variation_id' => array( 'type' => 'integer' ),
										'quantity'     => array( 'type' => 'integer', 'minimum' => 1 ),
										'subtotal'     => array( 'type' => 'string' ),
										'total'        => array( 'type' => 'string' ),
									),
									'required'   => array( 'product_id', 'quantity' ),
								),
							),
							'shipping_lines' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'method_id'    => array( 'type' => 'string' ),
										'method_title' => array( 'type' => 'string' ),
										'total'        => array( 'type' => 'string' ),
									),
								),
							),
							'coupon_lines'   => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'code' => array( 'type' => 'string' ),
									),
									'required'   => array( 'code' ),
								),
							),
						),
					),
				),
				'required'   => array( 'action' ),
			),
			'output_schema'       => self::manage_result_schema( self::order_summary_schema() ),
			'execute_callback'    => static function ( $input ) {
				return self::manage_order( self::normalize_input( $input ) );
			},
			'permission_callback' => static function () {
				return self::can_manage_orders();
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}

	private static function address_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'first_name' => array( 'type' => 'string' ),
				'last_name'  => array( 'type' => 'string' ),
				'company'    => array( 'type' => 'string' ),
				'address_1'  => array( 'type' => 'string' ),
				'address_2'  => array( 'type' => 'string' ),
				'city'       => array( 'type' => 'string' ),
				'state'      => array( 'type' => 'string' ),
				'postcode'   => array( 'type' => 'string' ),
				'country'    => array( 'type' => 'string' ),
				'email'      => array( 'type' => 'string' ),
				'phone'      => array( 'type' => 'string' ),
			),
		);
	}

	private static function query_products( array $input ): array {
		if ( isset( $input['id'] ) ) {
			$id     = max( 0, (int) $input['id'] );
			$result = self::dispatch_rest_request( 'GET', self::PRODUCTS_ROUTE . '/' . $id );

			if ( ! $result['success'] ) {
				return self::query_error_result( $result['error'] );
			}

			return array(
				'success' => true,
				'items'   => array( self::summarize_product( $result['data'] ) ),
				'total'   => 1,
			);
		}

		$result = self::dispatch_rest_request(
			'GET',
			self::PRODUCTS_ROUTE,
			self::only_allowed_keys( $input, array( 'search', 'status', 'sku', 'per_page', 'page' ) )
		);

		if ( ! $result['success'] ) {
			return self::query_error_result( $result['error'] );
		}

		$items = array();
		foreach ( self::ensure_list( $result['data'] ) as $product ) {
			$items[] = self::summarize_product( $product );
		}

		return array(
			'success' => true,
			'items'   => $items,
			'total'   => self::get_total_count( $result, count( $items ) ),
		);
	}

	private static function manage_product( array $input ): array {
		$action = isset( $input['action'] ) ? (string) $input['action'] : '';
		$fields = self::only_allowed_keys(
			self::get_fields( $input ),
			array(
				'name',
				'sku',
				'type',
				'status',
				'regular_price',
				'sale_price',
				'manage_stock',
				'stock_quantity',
				'description',
				'short_description',
				'categories',
				'tags',
				'images',
			)
		);

		switch ( $action ) {
			case 'create':
				$result = self::dispatch_rest_request( 'POST', self::PRODUCTS_ROUTE, $fields );
				break;

			case 'update':
				$id = self::required_id( $input );
				if ( is_array( $id ) ) {
					return $id;
				}
				$result = self::dispatch_rest_request( 'PUT', self::PRODUCTS_ROUTE . '/' . $id, $fields );
				break;

			case 'delete':
				$id = self::required_id( $input );
				if ( is_array( $id ) ) {
					return $id;
				}
				$result = self::dispatch_rest_request(
					'DELETE',
					self::PRODUCTS_ROUTE . '/' . $id,
					array( 'force' => $input['force'] ?? true )
				);
				break;

			default:
				return self::manage_error_result( self::error( 'invalid_action', __( 'Action must be create, update, or delete.', 'woocommerce' ), 400 ) );
		}

		if ( ! $result['success'] ) {
			return self::manage_error_result( $result['error'] );
		}

		$data    = is_array( $result['data'] ) ? $result['data'] : array();
		$deleted = 'delete' === $action && ! empty( $data['deleted'] );
		$item    = $deleted && isset( $data['previous'] ) ? $data['previous'] : $data;

		return array_filter(
			array(
				'success' => true,
				'id'      => isset( $item['id'] ) ? (int) $item['id'] : ( $input['id'] ?? null ),
				'message' => self::success_message( 'product', $action ),
				'item'    => self::summarize_product( $item ),
				'deleted' => $deleted,
			),
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	private static function query_orders( array $input ): array {
		if ( isset( $input['id'] ) ) {
			$id     = max( 0, (int) $input['id'] );
			$result = self::dispatch_rest_request( 'GET', self::ORDERS_ROUTE . '/' . $id );

			if ( ! $result['success'] ) {
				return self::query_error_result( $result['error'] );
			}

			return array(
				'success' => true,
				'items'   => array( self::summarize_order( $result['data'] ) ),
				'total'   => 1,
			);
		}

		$result = self::dispatch_rest_request(
			'GET',
			self::ORDERS_ROUTE,
			self::only_allowed_keys( $input, array( 'status', 'customer', 'after', 'before', 'per_page', 'page' ) )
		);

		if ( ! $result['success'] ) {
			return self::query_error_result( $result['error'] );
		}

		$items = array();
		foreach ( self::ensure_list( $result['data'] ) as $order ) {
			$items[] = self::summarize_order( $order );
		}

		return array(
			'success' => true,
			'items'   => $items,
			'total'   => self::get_total_count( $result, count( $items ) ),
		);
	}

	private static function manage_order( array $input ): array {
		$action = isset( $input['action'] ) ? (string) $input['action'] : '';
		$fields = self::only_allowed_keys(
			self::get_fields( $input ),
			array(
				'status',
				'customer_id',
				'customer_note',
				'payment_method',
				'payment_method_title',
				'set_paid',
				'billing',
				'shipping',
				'line_items',
				'shipping_lines',
				'coupon_lines',
			)
		);

		switch ( $action ) {
			case 'create':
				$result = self::dispatch_rest_request( 'POST', self::ORDERS_ROUTE, $fields );
				break;

			case 'update':
				$id = self::required_id( $input );
				if ( is_array( $id ) ) {
					return $id;
				}
				$result = self::dispatch_rest_request( 'PUT', self::ORDERS_ROUTE . '/' . $id, $fields );
				break;

			default:
				return self::manage_error_result( self::error( 'invalid_action', __( 'Action must be create or update.', 'woocommerce' ), 400 ) );
		}

		if ( ! $result['success'] ) {
			return self::manage_error_result( $result['error'] );
		}

		$item = is_array( $result['data'] ) ? $result['data'] : array();

		return array_filter(
			array(
				'success' => true,
				'id'      => isset( $item['id'] ) ? (int) $item['id'] : ( $input['id'] ?? null ),
				'message' => self::success_message( 'order', $action ),
				'item'    => self::summarize_order( $item ),
			),
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	private static function dispatch_rest_request( string $method, string $route, array $params = array() ): array {
		if ( ! class_exists( '\WP_REST_Request' ) || ! function_exists( 'rest_do_request' ) ) {
			return array(
				'success' => false,
				'error'   => self::error( 'rest_unavailable', __( 'The WordPress REST API is not available.', 'woocommerce' ), 500 ),
			);
		}

		$request = new \WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );
		if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => self::error_from_wp_error( $response ),
			);
		}

		if ( $response instanceof \WP_REST_Response ) {
			$status = $response->get_status();
			$data   = $response->get_data();

			if ( $status >= 400 ) {
				return array(
					'success' => false,
					'error'   => self::error_from_rest_data( $data, $status ),
				);
			}

			return array(
				'success' => true,
				'status'  => $status,
				'headers' => $response->get_headers(),
				'data'    => $data,
			);
		}

		return array(
			'success' => true,
			'status'  => 200,
			'headers' => array(),
			'data'    => $response,
		);
	}

	private static function summarize_product( $product ): array {
		$product = is_array( $product ) ? $product : array();
		$summary = self::copy_keys(
			$product,
			array( 'id', 'name', 'sku', 'status', 'type', 'price', 'regular_price', 'sale_price', 'stock_status', 'stock_quantity' )
		);

		if ( isset( $summary['id'] ) ) {
			$summary['id'] = (int) $summary['id'];
		}
		if ( array_key_exists( 'stock_quantity', $summary ) && null !== $summary['stock_quantity'] ) {
			$summary['stock_quantity'] = (int) $summary['stock_quantity'];
		}

		return $summary;
	}

	private static function summarize_order( $order ): array {
		$order   = is_array( $order ) ? $order : array();
		$summary = self::copy_keys(
			$order,
			array( 'id', 'status', 'total', 'currency', 'customer_id', 'date_created', 'payment_method' )
		);

		if ( isset( $summary['id'] ) ) {
			$summary['id'] = (int) $summary['id'];
		}
		if ( isset( $summary['customer_id'] ) ) {
			$summary['customer_id'] = (int) $summary['customer_id'];
		}

		$billing = isset( $order['billing'] ) && is_array( $order['billing'] ) ? $order['billing'] : array();
		if ( isset( $billing['email'] ) ) {
			$summary['customer_email'] = (string) $billing['email'];
		}

		$line_items                   = isset( $order['line_items'] ) && is_array( $order['line_items'] ) ? $order['line_items'] : array();
		$summary['line_item_count']   = count( $line_items );
		$summary['payment_method']    = $summary['payment_method'] ?? '';

		return $summary;
	}

	private static function normalize_input( $input ): array {
		return is_array( $input ) ? $input : array();
	}

	private static function get_fields( array $input ): array {
		return isset( $input['fields'] ) && is_array( $input['fields'] ) ? $input['fields'] : array();
	}

	private static function only_allowed_keys( array $input, array $allowed_keys ): array {
		return array_intersect_key( $input, array_flip( $allowed_keys ) );
	}

	private static function copy_keys( array $input, array $keys ): array {
		$output = array();
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$output[ $key ] = $input[ $key ];
			}
		}
		return $output;
	}

	private static function ensure_list( $data ): array {
		return is_array( $data ) ? array_values( $data ) : array();
	}

	private static function required_id( array $input ) {
		$id = isset( $input['id'] ) ? max( 0, (int) $input['id'] ) : 0;

		if ( 0 === $id ) {
			return self::manage_error_result( self::error( 'missing_id', __( 'A positive id is required for this action.', 'woocommerce' ), 400 ) );
		}

		return $id;
	}

	private static function query_error_result( array $error ): array {
		return array(
			'success' => false,
			'items'   => array(),
			'total'   => 0,
			'error'   => $error,
		);
	}

	private static function manage_error_result( array $error ): array {
		return array(
			'success' => false,
			'message' => $error['message'],
			'error'   => $error,
		);
	}

	private static function get_total_count( array $result, int $fallback ): int {
		$headers = isset( $result['headers'] ) && is_array( $result['headers'] ) ? $result['headers'] : array();

		return isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : $fallback;
	}

	private static function success_message( string $resource, string $action ): string {
		return sprintf(
			/* translators: 1: resource name, 2: action name. */
			__( '%1$s %2$sd successfully.', 'woocommerce' ),
			ucfirst( $resource ),
			$action
		);
	}

	private static function error( string $code, string $message, int $status = 500 ): array {
		return array(
			'code'    => $code,
			'message' => $message,
			'status'  => $status,
		);
	}

	private static function error_from_wp_error( \WP_Error $error ): array {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;

		return self::error( $error->get_error_code(), $error->get_error_message(), $status );
	}

	private static function error_from_rest_data( $data, int $status ): array {
		if ( is_array( $data ) ) {
			$code    = isset( $data['code'] ) ? (string) $data['code'] : 'rest_error';
			$message = isset( $data['message'] ) ? (string) $data['message'] : __( 'REST request failed.', 'woocommerce' );
			$status  = isset( $data['data']['status'] ) ? (int) $data['data']['status'] : $status;

			return self::error( $code, $message, $status );
		}

		return self::error( 'rest_error', __( 'REST request failed.', 'woocommerce' ), $status );
	}

	private static function can_read_products(): bool {
		return current_user_can( 'read_private_products' ) || current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' );
	}

	private static function can_manage_products(): bool {
		return current_user_can( 'edit_products' ) || current_user_can( 'manage_woocommerce' );
	}

	private static function can_read_orders(): bool {
		return current_user_can( 'read_shop_orders' ) || current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
	}

	private static function can_manage_orders(): bool {
		return current_user_can( 'edit_shop_orders' ) || current_user_can( 'manage_woocommerce' );
	}
}
