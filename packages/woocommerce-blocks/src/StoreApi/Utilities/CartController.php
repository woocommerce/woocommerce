<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;

/**
 * Woo Cart Controller class.
 * Helper class to bridge the gap between the cart API and Woo core.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @since 2.5.0
 */
class CartController {

	/**
	 * Based on the core cart class but returns errors rather than rendering notices directly.
	 *
	 * @todo Overriding the core add_to_cart method was necessary because core outputs notices when an item is added to
	 * the cart. For us this would cause notices to build up and output on the store, out of context. Core would need
	 * refactoring to split notices out from other cart actions.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param array $request Add to cart request params.
	 * @return string|Error
	 */
	public function add_to_cart( $request ) {
		$cart    = $this->get_cart_instance();
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
		$cart_id = $cart->generate_cart_id(
			$this->get_product_id( $product ),
			$this->get_variation_id( $product ),
			$request['variation'],
			$request['cart_item_data']
		);

		$this->validate_add_to_cart( $product, $request );

		$existing_cart_id = $cart->find_product_in_cart( $cart_id );

		if ( $existing_cart_id ) {
			if ( $product->is_sold_individually() ) {
				throw new RouteException(
					'woocommerce_rest_cart_product_sold_individually',
					sprintf(
						/* translators: %s: product name */
						__( 'You cannot add another "%s" to your cart.', 'woocommerce' ),
						$product->get_name()
					),
					400
				);
			}
			$cart->set_quantity( $existing_cart_id, $request['quantity'] + $cart->cart_contents[ $existing_cart_id ]['quantity'], true );

			return $existing_cart_id;
		}

		$cart->cart_contents[ $cart_id ] = apply_filters(
			'woocommerce_add_cart_item',
			array_merge(
				$request['cart_item_data'],
				array(
					'key'          => $cart_id,
					'product_id'   => $this->get_product_id( $product ),
					'variation_id' => $this->get_variation_id( $product ),
					'variation'    => $request['variation'],
					'quantity'     => $request['quantity'],
					'data'         => $product,
					'data_hash'    => wc_get_cart_item_data_hash( $product ),
				)
			),
			$cart_id
		);

		$cart->cart_contents = apply_filters( 'woocommerce_cart_contents_changed', $cart->cart_contents );

		do_action(
			'woocommerce_add_to_cart',
			$cart_id,
			$this->get_product_id( $product ),
			$request['quantity'],
			$this->get_variation_id( $product ),
			$request['variation'],
			$request['cart_item_data']
		);

		return $cart_id;
	}

	/**
	 * Based on core `set_quantity` method, but validates if an item is sold individually first.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param string  $item_id Cart item id.
	 * @param integer $quantity Cart quantity.
	 */
	public function set_cart_item_quantity( $item_id, $quantity = 1 ) {
		$cart_item = $this->get_cart_item( $item_id );

		if ( empty( $cart_item ) ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_key', __( 'Cart item does not exist.', 'woocommerce' ), 404 );
		}

		$product = $cart_item['data'];

		if ( ! $product instanceof \WC_Product ) {
			throw new RouteException( 'woocommerce_rest_cart_invalid_product', __( 'Cart item is invalid.', 'woocommerce' ), 404 );
		}

		if ( $product->is_sold_individually() && $quantity > 1 ) {
			throw new RouteException(
				'woocommerce_rest_cart_product_sold_individually',
				sprintf(
					/* translators: %s: product name */
					__( 'You cannot add another "%s" to your cart.', 'woocommerce' ),
					$product->get_name()
				),
				400
			);
		}
		$cart = $this->get_cart_instance();
		$cart->set_quantity( $item_id, $quantity );
	}

	/**
	 * Validate all items in the cart and check for errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param \WC_Product $product Product object associated with the cart item.
	 * @param array       $request Add to cart request params.
	 */
	public function validate_add_to_cart( \WC_Product $product, $request ) {
		if ( ! $product->is_purchasable() ) {
			$this->throw_default_product_exception( $product );
		}

		if ( ! $product->is_in_stock() ) {
			throw new RouteException(
				'woocommerce_rest_cart_product_no_stock',
				sprintf(
					/* translators: %s: product name */
					__( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ),
					$product->get_name()
				),
				400
			);
		}

		if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
			$qty_remaining = $this->get_remaining_stock_for_product( $product );
			$qty_in_cart   = $this->get_product_quantity_in_cart( $product );

			if ( $qty_remaining < $qty_in_cart + $request['quantity'] ) {
				throw new RouteException(
					'woocommerce_rest_cart_product_no_stock',
					sprintf(
						/* translators: 1: product name 2: quantity in stock */
						__( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'woocommerce' ),
						$product->get_name(),
						wc_format_stock_quantity_for_display( $qty_remaining, $product )
					),
					400
				);
			}
		}

		/**
		 * Hook: woocommerce_add_to_cart_validation (legacy).
		 *
		 * Allow 3rd parties to validate if an item can be added to the cart. This is a legacy hook from Woo core.
		 * This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture
		 * notices and convert to exceptions instead.
		 */
		$passed_validation = apply_filters(
			'woocommerce_add_to_cart_validation',
			true,
			$this->get_product_id( $product ),
			$request['quantity'],
			$this->get_variation_id( $product ),
			$request['variation']
		);

		if ( ! $passed_validation ) {
			// Validation did not pass - see if an error notice was thrown.
			NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_add_to_cart_error' );

			// If no notice was thrown, throw the default notice instead.
			$this->throw_default_product_exception( $product );
		}

		/**
		 * Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent
		 * add to cart from occuring.
		 *
		 * @param \WC_Product $product Product object being added to the cart.
		 * @param array       $request Add to cart request params including id, quantity, and variation attributes.
		 */
		do_action( 'wooocommerce_store_api_validate_add_to_cart', $product, $request );
	}

	/**
	 * Validate all items in the cart and check for errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 */
	public function validate_cart_items() {
		$cart       = $this->get_cart_instance();
		$cart_items = $this->get_cart_items();

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$this->validate_cart_item( $cart_item );
		}

		// Before running the woocommerce_check_cart_items hook, unhook validation from the core cart.
		remove_action( 'woocommerce_check_cart_items', array( $cart, 'check_cart_items' ), 1 );
		remove_action( 'woocommerce_check_cart_items', array( $cart, 'check_cart_coupons' ), 1 );

		/**
		 * Hook: woocommerce_check_cart_items
		 *
		 * Allow 3rd parties to validate cart items. This is a legacy hook from Woo core.
		 * This filter will be deprecated because it encourages usage of wc_add_notice. For the API we need to capture
		 * notices and convert to exceptions instead.
		 */
		do_action( 'woocommerce_check_cart_items' );
		NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_cart_item_error' );
	}

	/**
	 * Validates an existing cart item and returns any errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param array $cart_item Cart item array.
	 */
	public function validate_cart_item( $cart_item ) {
		$product = $cart_item['data'];

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! $product->is_purchasable() ) {
			$this->throw_default_product_exception( $product );
		}

		if ( $product->is_sold_individually() && $cart_item['quantity'] > 1 ) {
			throw new RouteException(
				'woocommerce_rest_cart_product_sold_individually',
				sprintf(
					/* translators: %s: product name */
					__( 'There are too many &quot;%s&quot; in the cart. Only 1 can be purchased.', 'woocommerce' ),
					$product->get_name()
				),
				400
			);
		}

		if ( ! $product->is_in_stock() ) {
			throw new RouteException(
				'woocommerce_rest_cart_product_no_stock',
				sprintf(
					/* translators: %s: product name */
					__( '&quot;%s&quot; is out of stock and cannot be purchased.', 'woocommerce' ),
					$product->get_name()
				),
				400
			);
		}

		if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
			$qty_remaining = $this->get_remaining_stock_for_product( $product );
			$qty_in_cart   = $this->get_product_quantity_in_cart( $product );

			if ( $qty_remaining < $qty_in_cart ) {
				throw new RouteException(
					'woocommerce_rest_cart_product_no_stock',
					sprintf(
						/* translators: 1: quantity in stock, 2: product name  */
						_n(
							'There is only %1$s unit of &quot;%2$s&quot; in stock.',
							'There are only %1$s units of &quot;%2$s&quot; in stock.',
							$qty_remaining,
							'woocommerce'
						),
						wc_format_stock_quantity_for_display( $qty_remaining, $product ),
						$product->get_name()
					),
					400
				);
			}
		}

		/**
		 * Fire action to validate add to cart. Functions hooking into this should throw an \Exception to prevent
		 * add to cart from occurring.
		 *
		 * @param \WC_Product $product Product object being added to the cart.
		 * @param array       $cart_item Cart item array.
		 */
		do_action( 'wooocommerce_store_api_validate_cart_item', $product, $cart_item );
	}

	/**
	 * Validate all coupons in the cart and check for errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 */
	public function validate_cart_coupons() {
		$cart_coupons = $this->get_cart_coupons();

		foreach ( $cart_coupons as $code ) {
			$coupon = new \WC_Coupon( $code );
			$this->validate_cart_coupon( $coupon );
		}
	}

	/**
	 * Validate all items in the cart and get a list of errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 */
	public function get_cart_item_errors() {
		$errors     = [];
		$cart_items = $this->get_cart_items();

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			try {
				$this->validate_cart_item( $cart_item );
			} catch ( RouteException $error ) {
				$errors[] = new \WP_Error( $error->getErrorCode(), $error->getMessage() );
			}
		}

		return $errors;
	}

	/**
	 * Validate all items in the cart and get a list of errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 */
	public function get_cart_coupon_errors() {
		$errors       = [];
		$cart_coupons = $this->get_cart_coupons();

		foreach ( $cart_coupons as $code ) {
			try {
				$coupon = new \WC_Coupon( $code );
				$this->validate_cart_coupon( $coupon );
			} catch ( RouteException $error ) {
				$errors[] = new \WP_Error( $error->getErrorCode(), $error->getMessage() );
			}
		}

		return $errors;
	}

	/**
	 * Get main instance of cart class.
	 *
	 * @throws RouteException When cart cannot be loaded.
	 * @return \WC_Cart
	 */
	public function get_cart_instance() {
		$cart = wc()->cart;

		if ( ! $cart || ! $cart instanceof \WC_Cart ) {
			throw new RouteException( 'woocommerce_rest_cart_error', __( 'Unable to retrieve cart.', 'woocommerce' ), 500 );
		}

		return $cart;
	}

	/**
	 * Return a cart item from the woo core cart class.
	 *
	 * @param string $item_id Cart item id.
	 * @return array
	 */
	public function get_cart_item( $item_id ) {
		$cart = $this->get_cart_instance();
		return isset( $cart->cart_contents[ $item_id ] ) ? $cart->cart_contents[ $item_id ] : [];
	}

	/**
	 * Returns all cart items.
	 *
	 * @param callable $callback Optional callback to apply to the array filter.
	 * @return array
	 */
	public function get_cart_items( $callback = null ) {
		$cart = $this->get_cart_instance();
		return $callback ? array_filter( $cart->get_cart(), $callback ) : array_filter( $cart->get_cart() );
	}

	/**
	 * Get hashes for items in the current cart. Useful for tracking changes.
	 *
	 * @return array
	 */
	public function get_cart_hashes() {
		$cart = $this->get_cart_instance();
		return [
			'line_items' => $cart->get_cart_hash(),
			'shipping'   => md5( wp_json_encode( $cart->shipping_methods ) ),
			'fees'       => md5( wp_json_encode( $cart->get_fees() ) ),
			'coupons'    => md5( wp_json_encode( $cart->get_applied_coupons() ) ),
			'taxes'      => md5( wp_json_encode( $cart->get_taxes() ) ),
		];
	}

	/**
	 * Empty cart contents.
	 */
	public function empty_cart() {
		$cart = $this->get_cart_instance();
		$cart->empty_cart();
	}

	/**
	 * See if cart has applied coupon by code.
	 *
	 * @param string $coupon_code Cart coupon code.
	 * @return bool
	 */
	public function has_coupon( $coupon_code ) {
		$cart = $this->get_cart_instance();
		return $cart->has_discount( $coupon_code );
	}

	/**
	 * Returns all applied coupons.
	 *
	 * @param callable $callback Optional callback to apply to the array filter.
	 * @return array
	 */
	public function get_cart_coupons( $callback = null ) {
		$cart = $this->get_cart_instance();
		return $callback ? array_filter( $cart->get_applied_coupons(), $callback ) : array_filter( $cart->get_applied_coupons() );
	}

	/**
	 * Get shipping packages from the cart with calculated shipping rates.
	 *
	 * @todo this can be refactored once https://github.com/woocommerce/woocommerce/pull/26101 lands.
	 *
	 * @param bool $calculate_rates Should rates for the packages also be returned.
	 * @return array
	 */
	public function get_shipping_packages( $calculate_rates = true ) {
		$cart = $this->get_cart_instance();

		// See if we need to calculate anything.
		if ( ! $cart->needs_shipping() ) {
			return [];
		}

		$packages = $cart->get_shipping_packages();

		// Add extra package data to array.
		if ( count( $packages ) ) {
			$packages = array_map(
				function( $key, $package, $index ) {
					$package['package_id']   = isset( $package['package_id'] ) ? $package['package_id'] : $key;
					$package['package_name'] = isset( $package['package_name'] ) ? $package['package_name'] : $this->get_package_name( $package, $index );
					return $package;
				},
				array_keys( $packages ),
				$packages,
				range( 1, count( $packages ) )
			);
		}

		return $calculate_rates ? wc()->shipping()->calculate_shipping( $packages ) : $packages;
	}

	/**
	 * Creates a name for a package.
	 *
	 * @param array $package Shipping package from WooCommerce.
	 * @param int   $index Package number.
	 * @return string
	 */
	protected function get_package_name( $package, $index ) {
		return apply_filters(
			'woocommerce_shipping_package_name',
			$index > 1 ?
				sprintf(
					/* translators: %d: shipping package number */
					_x( 'Shipping %d', 'shipping packages', 'woocommerce' ),
					$index
				) :
				_x( 'Shipping', 'shipping packages', 'woocommerce' ),
			$package['package_id'],
			$package
		);
	}

	/**
	 * Selects a shipping rate.
	 *
	 * @param int|string $package_id ID of the package to choose a rate for.
	 * @param string     $rate_id ID of the rate being chosen.
	 */
	public function select_shipping_rate( $package_id, $rate_id ) {
		$cart                        = $this->get_cart_instance();
		$session_data                = wc()->session->get( 'chosen_shipping_methods' ) ? wc()->session->get( 'chosen_shipping_methods' ) : [];
		$session_data[ $package_id ] = $rate_id;

		wc()->session->set( 'chosen_shipping_methods', $session_data );
	}

	/**
	 * Based on the core cart class but returns errors rather than rendering notices directly.
	 *
	 * @todo Overriding the core apply_coupon method was necessary because core outputs notices when a coupon gets
	 * applied. For us this would cause notices to build up and output on the store, out of context. Core would need
	 * refactoring to split notices out from other cart actions.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param string $coupon_code Coupon code.
	 */
	public function apply_coupon( $coupon_code ) {
		$cart            = $this->get_cart_instance();
		$applied_coupons = $this->get_cart_coupons();
		$coupon          = new \WC_Coupon( $coupon_code );

		if ( $coupon->get_code() !== $coupon_code ) {
			throw new RouteException(
				'woocommerce_rest_cart_coupon_error',
				sprintf(
					/* Translators: %s coupon code */
					__( '"%s" is an invalid coupon code.', 'woocommerce' ),
					esc_html( $coupon_code )
				),
				400
			);
		}

		if ( $this->has_coupon( $coupon_code ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_coupon_error',
				sprintf(
					/* Translators: %s coupon code */
					__( 'Coupon code "%s" has already been applied.', 'woocommerce' ),
					esc_html( $coupon_code )
				),
				400
			);
		}

		if ( ! $coupon->is_valid() ) {
			throw new RouteException(
				'woocommerce_rest_cart_coupon_error',
				wp_strip_all_tags( $coupon->get_error_message() ),
				400
			);
		}

		// Prevents new coupons being added if individual use coupons are already in the cart.
		$individual_use_coupons = $this->get_cart_coupons(
			function( $code ) {
				$coupon = new \WC_Coupon( $code );
				return $coupon->get_individual_use();
			}
		);

		foreach ( $individual_use_coupons as $code ) {
			$individual_use_coupon = new \WC_Coupon( $code );

			if ( false === apply_filters( 'woocommerce_apply_with_individual_use_coupon', false, $coupon, $individual_use_coupon, $applied_coupons ) ) {
				throw new RouteException(
					'woocommerce_rest_cart_coupon_error',
					sprintf(
						/* translators: %s: coupon code */
						__( '"%s" has already been applied and cannot be used in conjunction with other coupons.', 'woocommerce' ),
						$code
					),
					400
				);
			}
		}

		if ( $coupon->get_individual_use() ) {
			$coupons_to_remove = array_diff( $applied_coupons, apply_filters( 'woocommerce_apply_individual_use_coupon', array(), $coupon, $applied_coupons ) );

			foreach ( $coupons_to_remove as $code ) {
				$cart->remove_coupon( $code );
			}

			$applied_coupons = array_diff( $applied_coupons, $coupons_to_remove );
		}

		$applied_coupons[] = $coupon_code;
		$cart->set_applied_coupons( $applied_coupons );

		do_action( 'woocommerce_applied_coupon', $coupon_code );
	}

	/**
	 * Validates an existing cart coupon and returns any errors.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param \WC_Coupon $coupon Coupon object applied to the cart.
	 */
	protected function validate_cart_coupon( \WC_Coupon $coupon ) {
		if ( ! $coupon->is_valid() ) {
			$cart = $this->get_cart_instance();
			$cart->remove_coupon( $coupon->get_code() );
			$cart->calculate_totals();
			throw new RouteException(
				'woocommerce_rest_cart_coupon_error',
				sprintf(
					// translators: %1$s coupon code, %2$s reason.
					__( 'The "%1$s" coupon has been removed from your cart: %2$s', 'woocommerce' ),
					$coupon->get_code(),
					wp_strip_all_tags( $coupon->get_error_message() )
				),
				409
			);
		}
	}

	/**
	 * Gets the qty of a product across line items.
	 *
	 * @param \WC_Product $product Product object.
	 * @return int
	 */
	protected function get_product_quantity_in_cart( $product ) {
		$cart               = $this->get_cart_instance();
		$product_quantities = $cart->get_cart_item_quantities();
		$product_id         = $product->get_stock_managed_by_id();

		return isset( $product_quantities[ $product_id ] ) ? $product_quantities[ $product_id ] : 0;
	}

	/**
	 * Gets remaining stock for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return int
	 */
	protected function get_remaining_stock_for_product( $product ) {
		$reserve_stock = new ReserveStock();
		$draft_order   = wc()->session->get( 'store_api_draft_order', 0 );
		$qty_reserved  = $reserve_stock->get_reserved_stock( $product, $draft_order );

		return $product->get_stock_quantity() - $qty_reserved;
	}

	/**
	 * Get a product object to be added to the cart.
	 *
	 * @throws RouteException Exception if invalid data is detected.
	 *
	 * @param array $request Add to cart request params.
	 * @return \WC_Product|Error Returns a product object if purchasable.
	 */
	protected function get_product_for_cart( $request ) {
		$product = wc_get_product( $request['id'] );

		if ( ! $product || 'trash' === $product->get_status() ) {
			throw new RouteException(
				'woocommerce_rest_cart_invalid_product',
				__( 'This product cannot be added to the cart.', 'woocommerce' ),
				400
			);
		}

		return $product;
	}

	/**
	 * For a given product, get the product ID.
	 *
	 * @param \WC_Product $product Product object associated with the cart item.
	 * @return int
	 */
	protected function get_product_id( \WC_Product $product ) {
		return $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	}

	/**
	 * For a given product, get the variation ID.
	 *
	 * @param \WC_Product $product Product object associated with the cart item.
	 * @return int
	 */
	protected function get_variation_id( \WC_Product $product ) {
		return $product->is_type( 'variation' ) ? $product->get_id() : 0;
	}

	/**
	 * Default exception thrown when an item cannot be added to the cart.
	 *
	 * @throws RouteException Exception with code woocommerce_rest_cart_product_is_not_purchasable.
	 *
	 * @param \WC_Product $product Product object associated with the cart item.
	 */
	protected function throw_default_product_exception( \WC_Product $product ) {
		throw new RouteException(
			'woocommerce_rest_cart_product_is_not_purchasable',
			sprintf(
				/* translators: %s: product name */
				__( '&quot;%s&quot; is not available for purchase.', 'woocommerce' ),
				$product->get_name()
			),
			400
		);
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
	 * @throws RouteException Exception if invalid data is detected.
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

				throw new RouteException(
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
			throw new RouteException(
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
	 * @throws RouteException Exception if variation cannot be found.
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
			throw new RouteException(
				'woocommerce_rest_variation_id_from_variation_data',
				__( 'No matching variation found.', 'woocommerce' ),
				400
			);
		}

		return $variation_id;
	}

	/**
	 * Format and sanitize variation data posted to the API.
	 *
	 * Labels are converted to names (e.g. Size to pa_size), and values are cleaned.
	 *
	 * @throws RouteException Exception if variation cannot be found.
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
			$attribute_label          = wc_attribute_label( $attribute['name'] );
			$variation_attribute_name = wc_variation_attribute_name( $attribute['name'] );

			// Attribute labels e.g. Size.
			if ( isset( $variation_data[ $attribute_label ] ) ) {
				$return[ $variation_attribute_name ] =
					$attribute['is_taxonomy']
						?
						sanitize_title( $variation_data[ $attribute_label ] )
						:
						html_entity_decode(
							wc_clean( $variation_data[ $attribute_label ] ),
							ENT_QUOTES,
							get_bloginfo( 'charset' )
						);
				continue;
			}

			// Attribute slugs e.g. pa_size.
			if ( isset( $variation_data[ $attribute['name'] ] ) ) {
				$return[ $variation_attribute_name ] =
					$attribute['is_taxonomy']
						?
						sanitize_title( $variation_data[ $attribute['name'] ] )
						:
						html_entity_decode(
							wc_clean( $variation_data[ $attribute['name'] ] ),
							ENT_QUOTES,
							get_bloginfo( 'charset' )
						);
			}
		}
		return $return;
	}

	/**
	 * Get product attributes from the variable product (which may be the parent if the product object is a variation).
	 *
	 * @throws RouteException Exception if product is invalid.
	 *
	 * @param \WC_Product $product Product being added to the cart.
	 * @return array
	 */
	protected function get_variable_product_attributes( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		if ( ! $product || 'trash' === $product->get_status() ) {
			throw new RouteException(
				'woocommerce_rest_cart_invalid_parent_product',
				__( 'This product cannot be added to the cart.', 'woocommerce' ),
				400
			);
		}

		return $product->get_attributes();
	}
}
