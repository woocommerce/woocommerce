<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\Blocks\Domain\Services\AutocompleteInterface;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CartApplyCoupon class.
 */
class CartAddressSuggestion extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'address-suggestion';

	const SCHEMA_TYPE = 'address-suggestion';

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
		return '/cart/address-suggestion';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'query' => [
						'description' => __( 'The search query.', 'woocommerce' ),
						'type'        => 'string',
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
	protected function get_route_response( \WP_REST_Request $request ) {
		$provider = apply_filters( 'woocommerce_store_api_address_autocomplete_provider_registration', null );
		if ( null === $provider ) {
			throw new RouteException( 'address_autocomplete_provider_not_registered', __( 'No address autocomplete provider registered.', 'woocommerce' ), 404 );
		}
		if ( ! $provider instanceof AutocompleteInterface ) {
			throw new RouteException( 'address_autocomplete_provider_invalid', __( 'Invalid address autocomplete provider.', 'woocommerce' ), 404 );
		}
		$query = $request['query'];
		$hits  = $provider->search( $query );
		return rest_ensure_response( $this->schema->get_item_response( $hits ) );
	}
}
