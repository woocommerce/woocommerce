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
	 * @throws \Exception When construction fails for any reason other than the core data store's invalid-product failure for an ID that no longer resolves to a product post.
	 */
	private function get_product_for_rest_request( $request ) {
		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		if ( isset( $request['type'] ) && ! is_scalar( $request['type'] ) ) {
			// Falling back to a default class here would silently rewrite the product's type on update.
			return new \WP_Error(
				"woocommerce_rest_invalid_{$this->post_type}_type",
				__( 'Invalid product type.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$existing_product_type = isset( $request['type'] ) && $id ? \WC_Product_Factory::get_product_type( $id ) : false;

		if ( ProductType::VARIATION === $existing_product_type ) {
			return $this->get_invalid_product_id_error( true );
		}

		if ( isset( $request['type'] ) ) {
			$classname = \WC_Product_Factory::get_classname_from_product_type( (string) $request['type'] );

			if ( ! $classname || ! class_exists( $classname ) ) {
				// Fall back to the stored type rather than silently converting the product to simple.
				// get_product_classname() resolves it through woocommerce_product_class, so a stored
				// class registered only by that filter still wins. The requested type is not resolved
				// that way. The string check guards woocommerce_product_type_query, which can return
				// any truthy value, and a non-string one would fatal while the class name is built.
				$classname = is_string( $existing_product_type ) && '' !== $existing_product_type
					? \WC_Product_Factory::get_product_classname( $id, $existing_product_type )
					: 'WC_Product_Simple';
			}

			try {
				$product = new $classname( $id );
			} catch ( \Exception $e ) {
				// Convert only the core data store's own invalid-product failure for a nonzero
				// target that is no longer a product post; absence of a post proves deletion
				// there because wp_delete_post() invalidates the posts cache (unlike
				// WooCommerce's products cache group). The throw site stands in for that
				// failure: a store that reads its own backend throws from its own file, so it
				// is rethrown. A store that delegates to parent::read() throws from the core
				// file instead and is converted, which is why the post-type check below has to
				// carry the decision on its own. Everything else is rethrown unchanged, since
				// typed exceptions carry their own codes and extension-backed stores may fail
				// transiently for IDs that never had a post.
				$is_core_invalid_product = ! ( $e instanceof \WC_Data_Exception )
					&& ( new \ReflectionClass( \WC_Product_Data_Store_CPT::class ) )->getFileName() === $e->getFile();
				$target_post_type        = get_post_type( $id );

				if ( ! $is_core_invalid_product || ! $id || 'product' === $target_post_type ) {
					throw $e;
				}

				return $this->get_invalid_product_id_error( 'product_variation' === $target_post_type );
			}
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
	 * Prepare the product targeted by a duplicate request, converting typed failures to errors.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * @return \WC_Product|\WP_Error
	 */
	private function prepare_product_for_duplication( $request ) {
		try {
			$product = $this->prepare_object_for_database( $request );
		} catch ( \WC_Data_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		// The pre-insert filter runs after the trait's guarantees, so the shape must be re-checked.
		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error(
				"woocommerce_rest_{$this->post_type}_not_created",
				__( 'Invalid product.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $product;
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
