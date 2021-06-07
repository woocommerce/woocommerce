<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use AutomateWoo\Exception;
use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;

/**
 * Class CartExtensionsSchema
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class CartExtensionsSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart-extensions';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-extensions';

	/**
	 * Cart extensions schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @param \WP_REST_Request $request Request containing data for the extension callback.
	 * @throws RouteException When callback is not callable or parameters are incorrect.
	 *
	 * @return array
	 */
	public function get_item_response( $request = null ) {
		try {
			$callback = $this->extend->get_update_callback( $request['namespace'] );
		} catch ( RouteException $e ) {
			throw $e;
		}
		if ( is_callable( $callback ) ) {
			$callback( $request['data'] );
		}
		return rest_ensure_response( wc()->api->get_endpoint_data( '/wc/store/cart' ) );
	}
}
