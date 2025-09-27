<?php
/**
 * Abilities REST Bridge class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;
use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities REST Bridge class for WooCommerce.
 *
 * Configuration-driven registry that exposes REST endpoints as WordPress abilities.
 * Each ability is explicitly configured with ID, label, description, and operation.
 */
class AbilitiesRestBridge {

	/**
	 * Get REST controller configurations with explicit IDs, labels, and descriptions.
	 *
	 * @return array Controller configurations.
	 */
	private static function get_configurations(): array {
		return array(
			array(
				'controller' => \WC_REST_Products_Controller::class,
				'route'      => '/wc/v3/products',
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/products-list',
						'operation'   => 'list',
						'label'       => __( 'List Products', 'woocommerce' ),
						'description' => __( 'Retrieve a paginated list of products with optional filters for status, category, price range, and other attributes.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/products-get',
						'operation'   => 'get',
						'label'       => __( 'Get Product', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a single product by ID, including price, description, images, and metadata.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/products-create',
						'operation'   => 'create',
						'label'       => __( 'Create Product', 'woocommerce' ),
						'description' => __( 'Create a new product in WooCommerce with name, price, description, and other product attributes.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/products-update',
						'operation'   => 'update',
						'label'       => __( 'Update Product', 'woocommerce' ),
						'description' => __( 'Update an existing product by modifying its attributes such as price, stock, description, or metadata.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/products-delete',
						'operation'   => 'delete',
						'label'       => __( 'Delete Product', 'woocommerce' ),
						'description' => __( 'Permanently delete a product from the store. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Orders_Controller::class,
				'route'      => '/wc/v3/orders',
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/orders-list',
						'operation'   => 'list',
						'label'       => __( 'List Orders', 'woocommerce' ),
						'description' => __( 'Retrieve a paginated list of orders with optional filters for status, customer, date range, and other criteria.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/orders-get',
						'operation'   => 'get',
						'label'       => __( 'Get Order', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a single order by ID, including line items, customer details, and payment information.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/orders-create',
						'operation'   => 'create',
						'label'       => __( 'Create Order', 'woocommerce' ),
						'description' => __( 'Create a new order with customer information, line items, shipping details, and payment information.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/orders-update',
						'operation'   => 'update',
						'label'       => __( 'Update Order', 'woocommerce' ),
						'description' => __( 'Update an existing order by modifying status, customer information, line items, or other order details.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_System_Status_Controller::class,
				'route'      => '/wc/v3/system_status',
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/system-status-get',
						'operation'   => 'list',
						'label'       => __( 'Get System Status', 'woocommerce' ),
						'description' => __( 'Retrieve comprehensive system status information including environment details, database info, active plugins, theme, and WooCommerce settings.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Order_Notes_Controller::class,
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/order-notes-list',
						'operation'   => 'list',
						'route'       => '/wc/v3/orders/{order_id}/notes',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'List Order Notes', 'woocommerce' ),
						'description' => __( 'Retrieve all notes for a specific order, including internal and customer-visible notes with filtering options.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-notes-get',
						'operation'   => 'get',
						'route'       => '/wc/v3/orders/{order_id}/notes/{id}',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Note ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Get Order Note', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a specific order note by ID.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-notes-create',
						'operation'   => 'create',
						'route'       => '/wc/v3/orders/{order_id}/notes',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Create Order Note', 'woocommerce' ),
						'description' => __( 'Add a new note to an order, either as an internal note or customer-visible note.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-notes-delete',
						'operation'   => 'delete',
						'route'       => '/wc/v3/orders/{order_id}/notes/{id}',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Note ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Delete Order Note', 'woocommerce' ),
						'description' => __( 'Permanently remove a note from an order. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Customers_Controller::class,
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/customers-list',
						'operation'   => 'list',
						'route'       => '/wc/v3/customers',
						'route_params' => array(),
						'label'       => __( 'List Customers', 'woocommerce' ),
						'description' => __( 'Retrieve a paginated list of customers with optional filters for email, role, and registration date.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/customers-get',
						'operation'   => 'get',
						'route'       => '/wc/v3/customers/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Customer ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Get Customer', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a single customer by ID, including personal details and order history.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/customers-create',
						'operation'   => 'create',
						'route'       => '/wc/v3/customers',
						'route_params' => array(),
						'label'       => __( 'Create Customer', 'woocommerce' ),
						'description' => __( 'Create a new customer account with email, personal information, and billing/shipping addresses.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/customers-update',
						'operation'   => 'update',
						'route'       => '/wc/v3/customers/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Customer ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Update Customer', 'woocommerce' ),
						'description' => __( 'Update an existing customer by modifying personal information, addresses, or account settings.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/customers-delete',
						'operation'   => 'delete',
						'route'       => '/wc/v3/customers/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Customer ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Delete Customer', 'woocommerce' ),
						'description' => __( 'Permanently delete a customer account. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Coupons_Controller::class,
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/coupons-list',
						'operation'   => 'list',
						'route'       => '/wc/v3/coupons',
						'route_params' => array(),
						'label'       => __( 'List Coupons', 'woocommerce' ),
						'description' => __( 'Retrieve a paginated list of coupons with optional filters for code, discount type, and usage restrictions.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/coupons-get',
						'operation'   => 'get',
						'route'       => '/wc/v3/coupons/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Coupon ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Get Coupon', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a single coupon by ID, including discount settings and usage restrictions.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/coupons-create',
						'operation'   => 'create',
						'route'       => '/wc/v3/coupons',
						'route_params' => array(),
						'label'       => __( 'Create Coupon', 'woocommerce' ),
						'description' => __( 'Create a new coupon with discount code, amount, expiration date, and usage restrictions.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/coupons-update',
						'operation'   => 'update',
						'route'       => '/wc/v3/coupons/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Coupon ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Update Coupon', 'woocommerce' ),
						'description' => __( 'Update an existing coupon by modifying discount amount, expiration date, or usage restrictions.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/coupons-delete',
						'operation'   => 'delete',
						'route'       => '/wc/v3/coupons/{id}',
						'route_params' => array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Coupon ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Delete Coupon', 'woocommerce' ),
						'description' => __( 'Permanently delete a coupon. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Product_Variations_Controller::class,
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/product-variations-list',
						'operation'   => 'list',
						'route'       => '/wc/v3/products/{product_id}/variations',
						'route_params' => array(
							'product_id' => array(
								'type'        => 'integer',
								'description' => __( 'Product ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'List Product Variations', 'woocommerce' ),
						'description' => __( 'Retrieve all variations for a variable product, including pricing, stock, and attribute combinations.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/product-variations-get',
						'operation'   => 'get',
						'route'       => '/wc/v3/products/{product_id}/variations/{id}',
						'route_params' => array(
							'product_id' => array(
								'type'        => 'integer',
								'description' => __( 'Product ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Variation ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Get Product Variation', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a specific product variation, including attributes, pricing, and stock.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/product-variations-create',
						'operation'   => 'create',
						'route'       => '/wc/v3/products/{product_id}/variations',
						'route_params' => array(
							'product_id' => array(
								'type'        => 'integer',
								'description' => __( 'Product ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Create Product Variation', 'woocommerce' ),
						'description' => __( 'Create a new product variation with specific attributes, pricing, and stock settings.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/product-variations-update',
						'operation'   => 'update',
						'route'       => '/wc/v3/products/{product_id}/variations/{id}',
						'route_params' => array(
							'product_id' => array(
								'type'        => 'integer',
								'description' => __( 'Product ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Variation ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Update Product Variation', 'woocommerce' ),
						'description' => __( 'Update an existing product variation by modifying attributes, pricing, stock, or other settings.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/product-variations-delete',
						'operation'   => 'delete',
						'route'       => '/wc/v3/products/{product_id}/variations/{id}',
						'route_params' => array(
							'product_id' => array(
								'type'        => 'integer',
								'description' => __( 'Product ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Variation ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Delete Product Variation', 'woocommerce' ),
						'description' => __( 'Permanently delete a product variation. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
			array(
				'controller' => \WC_REST_Order_Refunds_Controller::class,
				'abilities'  => array(
					array(
						'id'          => 'woocommerce/order-refunds-list',
						'operation'   => 'list',
						'route'       => '/wc/v3/orders/{order_id}/refunds',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'List Order Refunds', 'woocommerce' ),
						'description' => __( 'Retrieve all refunds for a specific order, including refund amounts, reasons, and timestamps.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-refunds-get',
						'operation'   => 'get',
						'route'       => '/wc/v3/orders/{order_id}/refunds/{id}',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Refund ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Get Order Refund', 'woocommerce' ),
						'description' => __( 'Retrieve detailed information about a specific refund, including line items and refund metadata.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-refunds-create',
						'operation'   => 'create',
						'route'       => '/wc/v3/orders/{order_id}/refunds',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Create Order Refund', 'woocommerce' ),
						'description' => __( 'Process a refund for an order, specifying amount, reason, and whether to restock items.', 'woocommerce' ),
					),
					array(
						'id'          => 'woocommerce/order-refunds-delete',
						'operation'   => 'delete',
						'route'       => '/wc/v3/orders/{order_id}/refunds/{id}',
						'route_params' => array(
							'order_id' => array(
								'type'        => 'integer',
								'description' => __( 'Order ID', 'woocommerce' ),
							),
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Refund ID', 'woocommerce' ),
							),
						),
						'label'       => __( 'Delete Order Refund', 'woocommerce' ),
						'description' => __( 'Permanently delete a refund record. This action cannot be undone.', 'woocommerce' ),
					),
				),
			),
		);
	}

	/**
	 * Initialize the ability registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		// Register abilities when Abilities API is ready.
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register all configured abilities.
	 */
	public static function register_abilities(): void {
		// Only register abilities if this is an MCP endpoint request.
		// We check here (on abilities_api_init action) rather than earlier
		// because REST request detection requires the WordPress REST infrastructure
		// to be fully initialized.
		if ( ! MCPAdapterProvider::is_mcp_request() ) {
			return;
		}

		foreach ( self::get_configurations() as $config ) {
			RestAbilityFactory::register_controller_abilities( $config );
		}
	}
}
