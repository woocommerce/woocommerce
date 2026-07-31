<?php
/**
 * Product REST request preparation helpers.
 *
 * @package WooCommerce\Internal\RestApi
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RestApi;

use Automattic\WooCommerce\Enums\ProductType;

/**
 * Shared product construction for REST write requests.
 */
trait ProductRequestPreparationTrait {

	/**
	 * Get the product instance targeted by a REST write request.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WC_Product|\WP_Error
	 * @throws \Exception When product construction fails.
	 */
	private function get_product_for_rest_request( $request ) {
		$id                    = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$existing_product_type = isset( $request['type'] ) && $id ? \WC_Product_Factory::get_product_type( $id ) : false;

		if ( ProductType::VARIATION === $existing_product_type ) {
			return $this->get_invalid_product_id_error( true );
		}

		if ( isset( $request['type'] ) ) {
			$classname = \WC_Product_Factory::get_classname_from_product_type( $request['type'] );

			if ( ! class_exists( $classname ) ) {
				$classname = 'WC_Product_Simple';
			}

			$product = new $classname( $id );
		} elseif ( isset( $request['id'] ) ) {
			$product = wc_get_product( $id );
		} else {
			$product = new \WC_Product_Simple();
		}

		if ( ! $product instanceof \WC_Product ) {
			return $this->get_invalid_product_id_error();
		}

		return ProductType::VARIATION === $product->get_type()
			? $this->get_invalid_product_id_error( true )
			: $product;
	}

	/**
	 * Build the invalid-product error used by product write endpoints.
	 *
	 * @param bool $is_variation Whether the target is a product variation.
	 * @return \WP_Error
	 */
	private function get_invalid_product_id_error( bool $is_variation = false ): \WP_Error {
		return new \WP_Error(
			"woocommerce_rest_invalid_{$this->post_type}_id",
			$is_variation
				? __( 'To manipulate product variations you should use the /products/&lt;product_id&gt;/variations/&lt;id&gt; endpoint.', 'woocommerce' )
				: __( 'Invalid product ID.', 'woocommerce' ),
			array( 'status' => 404 )
		);
	}
}
