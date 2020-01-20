<?php
/**
 * Helper class to bridge the gap between the cart API and Woo core.
 *
 * Overrides some of the woo core cart methods to make them work with the API and generally increase flexibility. Some of this logic should move to core.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

use \WP_Error as Error;
use \WC_REST_Exception as RestException;

/**
 * Woo Cart Controller class.
 *
 * @since 2.5.0
 */
class CartController {

	/**
	 * Based on the core cart class but returns errors rather than rendering notices directly.
	 *
	 * @throws RestException Exception if invalid data is detected.
	 *
	 * @param array $request Add to cart request params.
	 * @return string|Error
	 */
	public function add_to_cart( $request ) {
		try {
			$request = wp_parse_args(
				$request,
				[
					'id'             => 0,
					'quantity'       => 1,
					'variation'      => [],
					'cart_item_data' => [],
				]
			);

			$request = $this->filter_request_data( $this->parse_variation_data( $request ) );
			$product = $this->get_product_for_cart( $request );

			if ( $product->is_type( 'variation' ) ) {
				$product_id   = $product->get_parent_id();
				$variation_id = $product->get_id();
			} else {
				$product_id   = $product->get_id();
				$variation_id = 0;
			}

			$cart_id          = wc()->cart->generate_cart_id( $product_id, $variation_id, $request['variation'], $request['cart_item_data'] );
			$existing_cart_id = wc()->cart->find_product_in_cart( $cart_id );

			if ( ! $product->is_purchasable() ) {
				throw new RestException( 'woocommerce_rest_cart_product_is_not_purchasable', __( 'This product cannot be purchased.', 'woocommerce' ), 403 );
			}

			if ( $product->is_sold_individually() && $existing_cart_id ) {
				/* translators: %s: product name */
				throw new RestException( 'woocommerce_rest_cart_product_sold_individually', sprintf( __( '"%s" is already inside your cart.', 'woocommerce' ), $product->get_name() ), 403 );
			}

			if ( ! $product->is_in_stock() ) {
				/* translators: %s: product name */
				throw new RestException( 'woocommerce_rest_cart_product_no_stock', sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product->get_name() ), 403 );
			}

			if ( $product->managing_stock() ) {
				$cart_product_quantities   = wc()->cart->get_cart_item_quantities();
				$stock_controller_id       = $product->get_stock_managed_by_id();
				$stock_controller_quantity = isset( $cart_product_quantities[ $stock_controller_id ] ) ? $cart_product_quantities[ $stock_controller_id ] : 0;

				if ( ! $product->has_enough_stock( $stock_controller_quantity + $request['quantity'] ) ) {
					throw new RestException(
						'woocommerce_rest_cart_product_no_stock',
						sprintf(
							/* translators: 1: product name 2: quantity in stock */
							__( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'woocommerce' ),
							$product->get_name(),
							wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product )
						),
						403
					);
				}
			}

			if ( $existing_cart_id ) {
				wc()->cart->set_quantity( $existing_cart_id, $request['quantity'] + wc()->cart->cart_contents[ $existing_cart_id ]['quantity'], true );
				return $existing_cart_id;
			}

			wc()->cart->cart_contents[ $cart_id ] = apply_filters(
				'woocommerce_add_cart_item',
				array_merge(
					$request['cart_item_data'],
					array(
						'key'          => $cart_id,
						'product_id'   => $product_id,
						'variation_id' => $variation_id,
						'variation'    => $request['variation'],
						'quantity'     => $request['quantity'],
						'data'         => $product,
						'data_hash'    => wc_get_cart_item_data_hash( $product ),
					)
				),
				$cart_id
			);

			wc()->cart->cart_contents = apply_filters( 'woocommerce_cart_contents_changed', wc()->cart->cart_contents );

			do_action( 'woocommerce_add_to_cart', $cart_id, $product_id, $request['quantity'], $variation_id, $request['variation'], $request['cart_item_data'] );

			return $cart_id;
		} catch ( RestException $e ) {
			return new Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get main instance of cart class.
	 *
	 * @return \WC_Cart
	 */
	public function get_cart_instance() {
		return wc()->cart;
	}

	/**
	 * Return a cart item from the woo core cart class.
	 *
	 * @param string $item_id Cart item id.
	 * @return array
	 */
	public function get_cart_item( $item_id ) {
		return isset( wc()->cart->cart_contents[ $item_id ] ) ? wc()->cart->cart_contents[ $item_id ] : [];
	}

	/**
	 * Returns all cart items.
	 *
	 * @return array
	 */
	public function get_cart_items() {
		return array_filter( wc()->cart->get_cart() );
	}

	/**
	 * Empty cart contents.
	 */
	public function empty_cart() {
		wc()->cart->empty_cart();
	}

	/**
	 * Get a product object to be added to the cart.
	 *
	 * @throws RestException Exception if invalid data is detected.
	 *
	 * @param array $request Add to cart request params.
	 * @return \WC_Product|Error Returns a product object if purchasable.
	 */
	protected function get_product_for_cart( $request ) {
		$product = wc_get_product( $request['id'] );

		if ( ! $product || 'trash' === $product->get_status() ) {
			throw new RestException( 'woocommerce_rest_cart_invalid_product', __( 'This product cannot be added to the cart.', 'woocommerce' ), 403 );
		}

		return $product;
	}

	/**
	 * Filter data for add to cart requests.
	 *
	 * @param array $request Add to cart request params.
	 * @return array Updated request array.
	 */
	protected function filter_request_data( $request ) {
		$product_id   = $request['id'];
		$variation_id = 0;
		$product      = wc_get_product( $product_id );

		if ( $product->is_type( 'variation' ) ) {
			$product_id   = $product->get_parent_id();
			$variation_id = $product->get_id();
		}

		$request['cart_item_data'] = (array) apply_filters(
			'woocommerce_add_cart_item_data',
			$request['cart_item_data'],
			$product_id,
			$variation_id,
			$request['quantity']
		);

		if ( $product->is_sold_individually() ) {
			$request['quantity'] = apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', 1, $request['quantity'], $product_id, $variation_id, $request['cart_item_data'] );
		}

		return $request;
	}

	/**
	 * If variations are set, validate and format the values ready to add to the cart.
	 *
	 * @throws RestException Exception if invalid data is detected.
	 *
	 * @param array $request Add to cart request params.
	 * @return array Updated request array.
	 */
	protected function parse_variation_data( $request ) {
		$product = $this->get_product_for_cart( $request );

		// Remove variation request if not needed.
		if ( ! $product->is_type( array( 'variation', 'variable' ) ) ) {
			$request['variation'] = [];
			return $request;
		}

		// Flatten data and format posted values.
		$variable_product_attributes = $this->get_variable_product_attributes( $product );
		$request['variation']        = $this->sanitize_variation_data( wp_list_pluck( $request['variation'], 'value', 'attribute' ), $variable_product_attributes );

		// If we have a parent product, find the variation ID.
		if ( $product->is_type( 'variable' ) ) {
			$request['id'] = $this->get_variation_id_from_variation_data( $request, $product );
		}

		// Now we have a variation ID, get the valid set of attributes for this variation. They will have an attribute_ prefix since they are from meta.
		$expected_attributes = wc_get_product_variation_attributes( $request['id'] );
		$missing_attributes  = [];

		foreach ( $variable_product_attributes as $attribute ) {
			if ( ! $attribute['is_variation'] ) {
				continue;
			}

			$prefixed_attribute_name = 'attribute_' . sanitize_title( $attribute['name'] );
			$expected_value          = isset( $expected_attributes[ $prefixed_attribute_name ] ) ? $expected_attributes[ $prefixed_attribute_name ] : '';
			$attribute_label         = wc_attribute_label( $attribute['name'] );

			if ( isset( $request['variation'][ wc_variation_attribute_name( $attribute['name'] ) ] ) ) {
				$given_value = $request['variation'][ wc_variation_attribute_name( $attribute['name'] ) ];

				if ( $expected_value === $given_value ) {
					continue;
				}

				// If valid values are empty, this is an 'any' variation so get all possible values.
				if ( '' === $expected_value && in_array( $given_value, $attribute->get_slugs(), true ) ) {
					continue;
				}

				throw new RestException(
					'woocommerce_rest_invalid_variation_data',
					/* translators: %1$s: Attribute name, %2$s: Allowed values. */
					sprintf( __( 'Invalid value posted for %1$s. Allowed values: %2$s', 'woocommerce' ), $attribute_label, implode( ', ', $attribute->get_slugs() ) ),
					400
				);
			}

			// If no attribute was posted, only error if the variation has an 'any' attribute which requires a value.
			if ( '' === $expected_value ) {
				$missing_attributes[] = $attribute_label;
			}
		}

		if ( ! empty( $missing_attributes ) ) {
			throw new RestException(
				'woocommerce_rest_missing_variation_data',
				/* translators: %s: Attribute name. */
				__( 'Missing variation data for variable product.', 'woocommerce' ) . ' ' . sprintf( _n( '%s is a required field', '%s are required fields', count( $missing_attributes ), 'woocommerce' ), wc_format_list_of_items( $missing_attributes ) ),
				400
			);
		}

		return $request;
	}

	/**
	 * Try to match request data to a variation ID and return the ID.
	 *
	 * @throws RestException Exception if variation cannot be found.
	 *
	 * @param array       $request Add to cart request params.
	 * @param \WC_Product $product Product being added to the cart.
	 * @return int Matching variation ID.
	 */
	protected function get_variation_id_from_variation_data( $request, $product ) {
		$data_store       = \WC_Data_Store::load( 'product' );
		$match_attributes = $request['variation'];
		$variation_id     = $data_store->find_matching_product_variation( $product, $match_attributes );

		if ( empty( $variation_id ) ) {
			throw new RestException( 'woocommerce_rest_variation_id_from_variation_data', __( 'No matching variation found.', 'woocommerce' ), 400 );
		}

		return $variation_id;
	}

	/**
	 * Format and sanitize variation data posted to the API.
	 *
	 * Labels are converted to names (e.g. Size to pa_size), and values are cleaned.
	 *
	 * @throws RestException Exception if variation cannot be found.
	 *
	 * @param array $variation_data Key value pairs of attributes and values.
	 * @param array $variable_product_attributes Product attributes we're expecting.
	 * @return array
	 */
	protected function sanitize_variation_data( $variation_data, $variable_product_attributes ) {
		$return = [];

		foreach ( $variable_product_attributes as $attribute ) {
			if ( ! $attribute['is_variation'] ) {
				continue;
			}
			$attribute_label = wc_attribute_label( $attribute['name'] );

			// Attribute labels e.g. Size.
			if ( isset( $variation_data[ $attribute_label ] ) ) {
				$return[ wc_variation_attribute_name( $attribute['name'] ) ] = $attribute['is_taxonomy'] ? sanitize_title( $variation_data[ $attribute_label ] ) : html_entity_decode( wc_clean( $variation_data[ $attribute_label ] ), ENT_QUOTES, get_bloginfo( 'charset' ) );
				continue;
			}

			// Attribute slugs e.g. pa_size.
			if ( isset( $variation_data[ $attribute['name'] ] ) ) {
				$return[ wc_variation_attribute_name( $attribute['name'] ) ] = $attribute['is_taxonomy'] ? sanitize_title( $variation_data[ $attribute['name'] ] ) : html_entity_decode( wc_clean( $variation_data[ $attribute['name'] ] ), ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
		}
		return $return;
	}

	/**
	 * Get product attributes from the variable product (which may be the parent if the product object is a variation).
	 *
	 * @throws RestException Exception if product is invalid.
	 *
	 * @param \WC_Product $product Product being added to the cart.
	 * @return array
	 */
	protected function get_variable_product_attributes( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		if ( ! $product || 'trash' === $product->get_status() ) {
			throw new RestException( 'woocommerce_rest_cart_invalid_parent_product', __( 'This product cannot be added to the cart.', 'woocommerce' ), 403 );
		}

		return $product->get_attributes();
	}
}
