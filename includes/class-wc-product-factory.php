<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object.
 *
 * @class 		WC_Product_Factory
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Factory {

	/**
	 * Get a product.
	 *
	 * @param mixed $product_id (default: false)
	 * @param array $deprecated
	 * @return WC_Product|bool Product object or null if the product cannot be loaded.
	 */
	public function get_product( $product_id = false, $deprecated = array() ) {
		$product_id = $this->get_product_id( $product_id );
		if ( ! $product_id ) {
			return false;
		}
		$product_type = $this->get_product_type( $product_id );
		$classname    = $this->get_classname_from_product_type( $product_type );

		// backwards compat filter
		$post_type = 'variation' === $product_type ? 'product_variation' : 'product';
		$classname = apply_filters( 'woocommerce_product_class', $classname, $product_type, $post_type, $product_id );

		if ( ! $classname ) {
			return false;
		}

		if ( ! class_exists( $classname ) ) {
			$classname = 'WC_Product_Simple';
		}

		try {
			// Try to get from cache, otherwise create a new object,
			$product = wp_cache_get( 'product-' . $product_id, 'products' );

			if ( ! is_a( $product, 'WC_Product' ) ) {
				$product = new $classname( $product_id );
				wp_cache_set( 'product-' . $product_id, $product, 'products' );
			}

			return $product;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get the product type for a product.
	 *
	 * @since 2.7.0
	 * @param  int $product_id
	 * @return string|false
	 */
	public static function get_product_type( $product_id ) {
		// Allow the overriding of the lookup in this function. Return the product type here.
		$override = apply_filters( 'woocommerce_product_type_query', false, $product_id );
		if ( ! $override ) {
			$post_type = get_post_type( $product_id );

			if ( 'product_variation' === $post_type ) {
				return 'variation';
			} elseif ( 'product' === $post_type ) {
				$terms = get_the_terms( $product_id, 'product_type' );
				return ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
			} else {
				return false;
			}
		} else {
			return $override;
		}
	}

	/**
	 * Create a WC coding standards compliant class name e.g. WC_Product_Type_Class instead of WC_Product_type-class.
	 * @param  string $product_type
	 * @return string|false
	 */
	public static function get_classname_from_product_type( $product_type ) {
		return $product_type ? 'WC_Product_' . implode( '_', array_map( 'ucfirst', explode( '-', $product_type ) ) ) : false;
	}

	/**
	 * Get the product ID depending on what was passed.
	 *
	 * @since 2.7.0
	 * @param  mixed $product
	 * @return int|bool false on failure
	 */
	private function get_product_id( $product ) {
		if ( is_numeric( $product ) ) {
			return $product;
		} elseif ( $product instanceof WC_Product ) {
			return $product->get_id();
		} elseif ( ! empty( $product->ID ) ) {
			return $product->ID;
		} else {
			return false;
		}
	}
}
