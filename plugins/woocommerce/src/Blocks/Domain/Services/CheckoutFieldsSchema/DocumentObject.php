<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema;

use WC_Cart;
use WC_Customer;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\BillingAddressSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ShippingAddressSchema;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * DocumentObject class.
 *
 * This will combine and format given cart/customer/checkout data into a standard object format that can be queried through
 * JSON. This is used for conditional fields and validation during checkout.
 */
class DocumentObject {
	/**
	 * Document object context which may adjust the schema response.
	 *
	 * @var null|string
	 */
	protected $context = null;

	/**
	 * Valid contexts.
	 *
	 * @var array
	 */
	protected $valid_contexts = [
		'shipping_address',
		'billing_address',
		'contact',
		'order',
	];

	/**
	 * The cart object.
	 *
	 * @var WC_Cart|null
	 */
	protected $cart = null;

	/**
	 * The customer object.
	 *
	 * @var WC_Customer|null
	 */
	protected $customer = null;

	/**
	 * Cart controller class instance.
	 *
	 * @var CartController
	 */
	protected $cart_controller;

	/**
	 * Schema controller class instance.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * The request data.
	 *
	 * @var array
	 */
	protected $request_data = [];

	/**
	 * Memoized result of get_data(). Rule evaluation calls get_data() once per rule, so the
	 * assembled data is cached until the cart, customer, or context changes.
	 *
	 * @var array|null
	 */
	protected $data = null;

	/**
	 * The constructor.
	 *
	 * @param array $request_data Data that overrides the default values.
	 */
	public function __construct( array $request_data = [] ) {
		$this->cart_controller   = new CartController();
		$this->schema_controller = StoreApi::container()->get( SchemaController::class );
		$this->request_data      = $request_data;
	}

	/**
	 * Converts additional field values into their document object representation.
	 *
	 * @param mixed $values Key value pairs of field values, keyed by field ID.
	 * @return mixed The converted values.
	 */
	protected function prepare_additional_fields( $values ) {
		return Package::container()->get( CheckoutFields::class )->prepare_values_for_document_object( $values );
	}

	/**
	 * Set document object context.
	 *
	 * @param null|string $context Context to set.
	 */
	public function set_context( $context = null ) {
		if ( ! in_array( $context, $this->valid_contexts, true ) ) {
			return;
		}
		$this->context = $context;
		$this->data    = null;
	}

	/**
	 * Set the customer object.
	 *
	 * @param WC_Customer $customer The customer object.
	 */
	public function set_customer( WC_Customer $customer ) {
		$this->customer = $customer;
		$this->data     = null;
	}

	/**
	 * Set the cart object.
	 *
	 * @param WC_Cart $cart The cart object.
	 */
	public function set_cart( WC_Cart $cart ) {
		$this->cart = $cart;
		$this->data = null;
	}

	/**
	 * Gets a subset of cart data.
	 *
	 * @return array The cart data.
	 */
	protected function get_cart_data() {
		$cart_data               = StoreApi::container()->get( SchemaController::class )->get( CartSchema::IDENTIFIER )->get_item_response( $this->cart );
		$selected_shipping_rates = array_filter(
			array_map(
				function ( $package ) {
					$selected_rate = array_search( true, array_column( $package['shipping_rates'], 'selected' ), true );
					return false !== $selected_rate && isset( $package['shipping_rates'][ $selected_rate ] ) ? $package['shipping_rates'][ $selected_rate ] : null;
				},
				$cart_data['shipping_rates']
			)
		);
		$local_pickup_method_ids = LocalPickupUtils::get_local_pickup_method_ids();

		return wp_parse_args(
			$this->request_data['cart'] ?? [],
			[
				'coupons'            => array_values( wc_list_pluck( $cart_data['coupons'], 'code' ) ),
				'shipping_rates'     => array_values( wc_list_pluck( $selected_shipping_rates, 'rate_id' ) ),
				'items'              => array_merge(
					...array_map(
						function ( $item ) {
							return array_fill( 0, (int) NumberUtil::ceil( $item['quantity'] ), $item['id'] );
						},
						$cart_data['items']
					)
				),
				'items_type'         => array_unique( array_values( wc_list_pluck( $cart_data['items'], 'type' ) ) ),
				'items_count'        => $cart_data['items_count'],
				'items_weight'       => $cart_data['items_weight'],
				'needs_shipping'     => $cart_data['needs_shipping'],
				'prefers_collection' => count( array_intersect( $local_pickup_method_ids, wc_list_pluck( $selected_shipping_rates, 'method_id' ) ) ) > 0,
				'totals'             => [
					'total_price' => (int) $cart_data['totals']->total_price,
					'total_tax'   => (int) $cart_data['totals']->total_tax,
				],
				'extensions'         => (object) $cart_data['extensions'],
			]
		);
	}

	/**
	 * Get checkout data.
	 *
	 * @return array Checkout data context.
	 */
	protected function get_checkout_data() {
		$checkout_data = $this->request_data['checkout'] ?? [];

		if ( isset( $checkout_data['additional_fields'] ) ) {
			$checkout_data['additional_fields'] = $this->prepare_additional_fields( $checkout_data['additional_fields'] );
		}

		return $checkout_data;
	}

	/**
	 * Get the customer data.
	 *
	 * @return array The customer data.
	 */
	protected function get_customer_data() {
		$customer_data = [
			'id'                => $this->request_data['customer']['id'] ?? $this->customer->get_id(),
			'shipping_address'  => wp_parse_args(
				$this->request_data['customer']['shipping_address'] ?? (object) [],
				$this->schema_controller->get( ShippingAddressSchema::IDENTIFIER )->get_item_response( $this->customer )
			),
			'billing_address'   => wp_parse_args(
				$this->request_data['customer']['billing_address'] ?? (object) [],
				$this->schema_controller->get( BillingAddressSchema::IDENTIFIER )->get_item_response( $this->customer )
			),
			'additional_fields' => $this->request_data['customer']['additional_fields'] ?? (object) [],
		];

		// Address locations keep their additional fields inside the address itself.
		foreach ( [ 'shipping_address', 'billing_address', 'additional_fields' ] as $key ) {
			$customer_data[ $key ] = $this->prepare_additional_fields( $customer_data[ $key ] );
		}

		if ( 'shipping_address' === $this->context ) {
			$customer_data['address'] = $customer_data['shipping_address'];
		}

		if ( 'billing_address' === $this->context ) {
			$customer_data['address'] = $customer_data['billing_address'];
		}

		return $customer_data;
	}

	/**
	 * Get the data for the document object.
	 *
	 * This isn't a 1:1 match with Store API because some data is simplified to make it easier to parse as JSON.
	 *
	 * @return array The data for the document object.
	 */
	public function get_data() {
		if ( ! is_null( $this->data ) ) {
			return $this->data;
		}

		// Get cart and customer objects before returning data if they are null.
		if ( is_null( $this->cart ) ) {
			$this->cart = $this->cart_controller->get_cart_for_response();
		}

		if ( is_null( $this->customer ) ) {
			$this->customer = ! empty( WC()->customer ) ? WC()->customer : new WC_Customer();
		}

		$this->data = [
			'cart'     => $this->get_cart_data(),
			'customer' => $this->get_customer_data(),
			'checkout' => $this->get_checkout_data(),
		];

		return $this->data;
	}

	/**
	 * Get the current context.
	 *
	 * @return null|string The context.
	 */
	public function get_context() {
		return $this->context;
	}
}
