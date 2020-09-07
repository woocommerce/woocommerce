<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;

/**
 * Abstract Cart Route
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
abstract class AbstractCartRoute extends AbstractRoute {
	/**
	 * Get the route response based on the type of request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		$this->maybe_load_cart();
		$this->maybe_recalculate_totals();
		return parent::get_response( $request );
	}

	/**
	 * If shipping/tax data has changed on the server since last calculation, trigger a recalculation now.
	 *
	 * @return void
	 */
	protected function maybe_recalculate_totals() {
		$current_hash = wc()->session->get( 'store_api_calculation_hash', '' );
		$new_hash     = md5(
			wp_json_encode(
				[
					\WC_Cache_Helper::get_transient_version( 'shipping' ),
					wc()->cart->get_cart_hash(),
				]
			)
		);
		if ( ! hash_equals( $current_hash, $new_hash ) ) {
			wc()->session->set( 'store_api_calculation_hash', $new_hash );
			wc()->cart->get_cart();
			wc()->cart->calculate_shipping();
			wc()->cart->calculate_totals();
		}
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 500.
	 * @param array  $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = 500, $additional_data = [] ) {
		switch ( $http_status_code ) {
			case 409:
				// If there was a conflict, return the cart so the client can resolve it.
				$controller = new CartController();
				$cart       = $controller->get_cart_instance();

				return new \WP_Error(
					$error_code,
					$error_message,
					array_merge(
						$additional_data,
						[
							'status' => $http_status_code,
							'cart'   => $this->schema->get_item_response( $cart ),
						]
					)
				);
		}
		return new \WP_Error( $error_code, $error_message, [ 'status' => $http_status_code ] );
	}
}
