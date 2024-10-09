<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\Domain\Services\AutocompleteInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CartSelectAddress class.
 */
class CartSelectAddress extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-select-address';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/cart/select-address';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id'           => [
						'description' => __( 'Unique identifier for the address to select.', 'woocommerce' ),
						'type'        => 'string',
					],
					'address_type' => [
						'description' => __( 'Which address to assign the suggestion to (billing, shipping, or both).', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => [
							'billing',
							'shipping',
							'both',
						],
					],
				],
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$provider = apply_filters( 'woocommerce_store_api_address_autocomplete_provider_registration', null );
		if ( null === $provider ) {
			throw new RouteException( 'address_autocomplete_provider_not_registered', __( 'No address autocomplete provider registered.', 'woocommerce' ), 404 );
		}
		if ( ! $provider instanceof AutocompleteInterface ) {
			throw new RouteException( 'address_autocomplete_provider_invalid', __( 'Invalid address autocomplete provider.', 'woocommerce' ), 404 );
		}
		$id             = $request['id'];
		$address_type   = $request['address_type'];
		$address        = $provider->get_address( $id );
		$customer       = WC()->customer;
		$customer_props = [];

		if ( 'billing' === $address_type || 'both' === $address_type ) {
			// Run validation and sanitization now that the cart and customer data is loaded.
			$address          = $this->schema->billing_address_schema->sanitize_callback( $address, $request, 'billing_address' );
			$validation_check = $this->schema->billing_address_schema->validate_callback( $address, $request, 'billing_address' );

			if ( is_wp_error( $validation_check ) ) {
				return rest_ensure_response( $validation_check );
			}

			foreach ( $address as $key => $value ) {
				$customer_props[ 'billing_' . $key ] = $value;
			}
		}
		if ( 'shipping' === $address_type || 'both' === $address_type ) {
			// Run validation and sanitization now that the cart and customer data is loaded.
			$address          = $this->schema->shipping_address_schema->sanitize_callback( $address, $request, 'shipping_address' );
			$validation_check = $this->schema->shipping_address_schema->validate_callback( $address, $request, 'shipping_address' );

			if ( is_wp_error( $validation_check ) ) {
				return rest_ensure_response( $validation_check );
			}

			foreach ( $address as $key => $value ) {
				$customer_props[ 'shipping_' . $key ] = $value;
			}
		}

		$customer->set_props( $customer_props );
		$customer->save();
		$this->cart_controller->calculate_totals();

		return rest_ensure_response( $this->schema->get_item_response( $this->cart_controller->get_cart_instance() ) );
	}
}
