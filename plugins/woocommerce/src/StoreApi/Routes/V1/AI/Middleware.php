<?php

namespace Automattic\WooCommerce\StoreApi\Routes\V1\AI;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * Middleware class.
 *
 * @internal
 */
class Middleware {


	/**
	 * Ensure that the user is allowed to make this request.
	 *
	 * @throws RouteException If the user is not allowed to make this request.
	 * @return boolean
	 */
	public static function is_authorized() {
		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new RouteException( 'woocommerce_rest_invalid_user', __( 'You are not allowed to make this request. Please make sure you are logged in.', 'woo-gutenberg-products-block' ), 403 );
			}
		} catch ( RouteException $error ) {
			return new \WP_Error(
				$error->getErrorCode(),
				$error->getMessage(),
				array( 'status' => $error->getCode() )
			);
		}

		$allow_ai_connection = get_option( 'woocommerce_blocks_allow_ai_connection' );

		if ( ! $allow_ai_connection ) {
			try {
				throw new RouteException( 'ai_connection_not_allowed', __( 'AI content generation is not allowed on this store. Update your store settings if you wish to enable this feature.', 'woo-gutenberg-products-block' ), 403 );
			} catch ( RouteException $error ) {
				return new \WP_Error(
					$error->getErrorCode(),
					$error->getMessage(),
					array( 'status' => $error->getCode() )
				);
			}
		}

		return true;
	}
}
