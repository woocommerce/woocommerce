<?php
/**
 * STRAP abilities bridge (experimental).
 *
 * Pattern C for the abilities-rollout verification loop: a single facade
 * ability that accepts an entity + operation discriminator and an opaque
 * params object. Minimal schema surface by design — trades client-side
 * type safety for the lowest possible context cost. Registered only on
 * MCP requests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * STRAP abilities bridge.
 */
class StrapAbilitiesBridge {

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
	 * Register abilities, gated on MCP request context.
	 */
	public static function register_abilities(): void {
		if ( ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability( 'woocommerce/commerce-operations', self::commerce_operations() );
	}

	private static function commerce_operations(): array {
		return array(
			'label'               => __( 'WooCommerce Commerce Operations', 'woocommerce' ),
			'description'         => __(
				'Perform CRUD operations against WooCommerce commerce entities. Supply the entity (products or orders), the operation (query, create, update, or delete), and an opaque params object whose accepted keys depend on the entity/operation pair. Discover supported params by calling query without filters first.',
				'woocommerce'
			),
			'category'            => 'woocommerce-rest',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'entity'    => array(
						'type'        => 'string',
						'enum'        => array( 'products', 'orders' ),
						'description' => 'The commerce entity to operate on.',
					),
					'operation' => array(
						'type'        => 'string',
						'enum'        => array( 'query', 'create', 'update', 'delete' ),
						'description' => 'The CRUD operation. Delete is not supported for orders.',
					),
					'id'        => array(
						'type'        => 'integer',
						'description' => 'Required for update and delete; optional for query (returns single).',
					),
					'params'    => array(
						'type'        => 'object',
						'description' => 'Operation parameters. Accepted keys vary by entity/operation.',
					),
				),
				'required'   => array( 'entity', 'operation' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'   => array( 'type' => 'boolean' ),
					'entity'    => array( 'type' => 'string' ),
					'operation' => array( 'type' => 'string' ),
					'data'      => array( 'description' => 'Operation result. Shape depends on entity/operation.' ),
					'error'     => array(
						'type'       => 'object',
						'properties' => array(
							'code'    => array( 'type' => 'string' ),
							'message' => array( 'type' => 'string' ),
							'status'  => array( 'type' => 'integer' ),
						),
					),
				),
				'required'   => array( 'success' ),
			),
			'execute_callback'    => static function ( $input ) {
				return self::execute_facade_operation( self::normalize_input( $input ) );
			},
			'permission_callback' => static function () {
				return self::can_use_facade();
			},
			'meta'                => array( 'show_in_rest' => true ),
		);
	}

	private static function execute_facade_operation( array $input ): array {
		$entity    = isset( $input['entity'] ) ? (string) $input['entity'] : '';
		$operation = isset( $input['operation'] ) ? (string) $input['operation'] : '';
		$params    = isset( $input['params'] ) && is_array( $input['params'] ) ? $input['params'] : array();

		if ( isset( $input['id'] ) ) {
			$params['id'] = $input['id'];
		}

		switch ( $entity ) {
			case 'products':
				$result = 'query' === $operation
					? self::query_products( $params )
					: self::manage_product( self::manage_input_from_facade( $operation, $params ) );
				break;

			case 'orders':
				$result = 'query' === $operation
					? self::query_orders( $params )
					: self::manage_order( self::manage_input_from_facade( $operation, $params ) );
				break;

			default:
				return self::facade_error_result(
					$entity,
					$operation,
					self::error( 'invalid_entity', __( 'Entity must be products or orders.', 'woocommerce' ), 400 )
				);
		}

		return self::wrap_facade_result( $entity, $operation, $result );
	}

	private static function manage_input_from_facade( string $operation, array $params ): array {
		$input = array(
			'action' => $operation,
			'fields' => $params,
		);

		if ( isset( $params['id'] ) ) {
			$input['id'] = $params['id'];
			unset( $input['fields']['id'] );
		}
		if ( isset( $params['force'] ) ) {
			$input['force'] = $params['force'];
			unset( $input['fields']['force'] );
		}

		return $input;
	}

	private static function wrap_facade_result( string $entity, string $operation, array $result ): array {
		if ( empty( $result['success'] ) ) {
			return self::facade_error_result(
				$entity,
				$operation,
				$result['error'] ?? self::error( 'operation_failed', __( 'Operation failed.', 'woocommerce' ), 500 )
			);
		}

		unset( $result['success'], $result['error'] );

		return array(
			'success'   => true,
			'entity'    => $entity,
			'operation' => $operation,
			'data'      => $result,
		);
	}

	private static function facade_error_result( string $entity, string $operation, array $error ): array {
		return array(
			'success'   => false,
			'entity'    => $entity,
			'operation' => $operation,
			'error'     => $error,
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

		$line_items                 = isset( $order['line_items'] ) && is_array( $order['line_items'] ) ? $order['line_items'] : array();
		$summary['line_item_count'] = count( $line_items );
		$summary['payment_method']  = $summary['payment_method'] ?? '';

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

	private static function can_use_facade(): bool {
		return current_user_can( 'read_private_products' )
			|| current_user_can( 'edit_products' )
			|| current_user_can( 'read_shop_orders' )
			|| current_user_can( 'edit_shop_orders' )
			|| current_user_can( 'manage_woocommerce' );
	}
}
