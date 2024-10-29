<?php
/**
 * General store tracking actions.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WC_Order;
use WC_Payment_Gateway;
use WC_Product;

/**
 * Filters and Actions added to Store pages to perform analytics
 */
class Universal {
	/**
	 * Trait to handle common analytics functions.
	 */
	use Woo_Analytics_Trait;

	/**
	 * Constructor.
	 */
	public function init_hooks() {
		$this->find_cart_checkout_content_sources();
		$this->additional_blocks_on_cart_page     = $this->get_additional_blocks_on_page( 'cart' );
		$this->additional_blocks_on_checkout_page = $this->get_additional_blocks_on_page( 'checkout' );

		// add to carts from non-product pages or lists -- search, store etc.
		add_action( 'wp_head', array( $this, 'loop_session_events' ), 2 );

		// Capture cart events.
		add_action( 'woocommerce_add_to_cart', array( $this, 'capture_add_to_cart' ), 10, 6 );

		add_action( 'woocommerce_after_cart', array( $this, 'remove_from_cart' ) );
		add_action( 'woocommerce_after_mini_cart', array( $this, 'remove_from_cart' ) );
		add_action( 'wcct_before_cart_widget', array( $this, 'remove_from_cart' ) );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'remove_from_cart_attributes' ), 10, 2 );

		// Checkout.
		// Send events after checkout template (shortcode).
		add_action( 'woocommerce_after_checkout_form', array( $this, 'checkout_process' ) );
		// Send events after checkout block.
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', array( $this, 'checkout_process' ) );

		// order confirmed.
		add_action( 'woocommerce_thankyou', array( $this, 'order_process' ), 10, 1 );
		add_action( 'woocommerce_after_cart', array( $this, 'remove_from_cart_via_quantity' ), 10, 1 );

		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'save_checkout_post_data' ), 10, 1 );

		add_action( 'woocommerce_created_customer', array( $this, 'capture_created_customer' ), 10, 2 );

		add_action( 'woocommerce_created_customer', array( $this, 'capture_post_checkout_created_customer' ), 10, 2 );
	}

	/**
	 * On product lists or other non-product pages, add an event listener to "Add to Cart" button click
	 */
	public function loop_session_events() {
		// Check for previous events queued in session data.
		if ( is_object( WC()->session ) ) {
			$data = WC()->session->get( 'wca_session_data' );
			if ( ! empty( $data ) ) {
				foreach ( $data as $data_instance ) {
					$this->record_event(
						$data_instance['event'],
						array(
							'pq' => $data_instance['quantity'],
						),
						$data_instance['product_id']
					);
				}
				// Clear data, now that these events have been recorded.
				WC()->session->set( 'wca_session_data', '' );
			}
		}
	}

	/**
	 * On the cart page, add an event listener for removal of product click
	 */
	public function remove_from_cart() {
		$common_props = $this->render_properties_as_js(
			$this->get_common_properties()
		);

		// We listen at div.woocommerce because the cart 'form' contents get forcibly
		// updated and subsequent removals from cart would then not have this click
		// handler attached.
		wc_enqueue_js(
			"jQuery( 'div.woocommerce' ).on( 'click', 'a.remove', function() {
				var productID = jQuery( this ).data( 'product_id' );
				var quantity = jQuery( this ).parent().parent().find( '.qty' ).val()
				var productDetails = {
					'id': productID,
					'quantity': quantity ? quantity : '1',
				};
				_wca.push( {
					'_en': 'woocommerceanalytics_remove_from_cart',
					'pi': productDetails.id,
					'pq': productDetails.quantity, " .
					$common_props . '
				} );
			} );'
		);
	}

	/**
	 * Adds the product ID to the remove product link (for use by remove_from_cart above) if not present
	 *
	 * @param string $url Full HTML a tag of the link to remove an item from the cart.
	 * @param string $key Unique Key ID for a cart item.
	 *
	 * @return string
	 */
	public function remove_from_cart_attributes( $url, $key ) {
		if ( str_contains( $url, 'data-product_id' ) ) {
			return $url;
		}

		$item    = WC()->cart->get_cart_item( $key );
		$product = $item['data'];

		$new_attributes = sprintf(
			'" data-product_id="%s">',
			esc_attr( $product->get_id() )
		);

		$url = str_replace( '">', $new_attributes, $url );
		return $url;
	}

	/**
	 * Get the selected shipping option for a cart item. If the name cannot be found in the options table, the method's
	 * ID will be used.
	 *
	 * @param string $cart_item_key the cart item key.
	 *
	 * @return mixed|bool
	 */
	public function get_shipping_option_for_item( $cart_item_key ) {
		$packages         = wc()->shipping()->get_packages();
		$selected_options = wc()->session->get( 'chosen_shipping_methods' );

		if ( ! is_array( $packages ) || ! is_array( $selected_options ) ) {
			return false;
		}

		foreach ( $packages as $package_id => $package ) {

			if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
				return false;
			}

			foreach ( $package['contents'] as $package_item ) {
				if ( ! isset( $package_item['key'] ) || $package_item['key'] !== $cart_item_key || ! isset( $selected_options[ $package_id ] ) ) {
					continue;
				}
				$selected_rate_id = $selected_options[ $package_id ];
				$method_key_id    = sanitize_text_field( str_replace( ':', '_', $selected_rate_id ) );
				$option_name      = 'woocommerce_' . $method_key_id . '_settings';
				$option_value     = get_option( $option_name );
				$title            = '';
				if ( is_array( $option_value ) && isset( $option_value['title'] ) ) {
					$title = $option_value['title'];
				}
				if ( ! $title ) {
					return $selected_rate_id;
				}
				return $title;
			}
		}

		return false;
	}

	/**
	 * On the Checkout page, trigger an event for each product in the cart
	 */
	public function checkout_process() {
		global $post;
		$checkout_page_id = wc_get_page_id( 'checkout' );
		$cart             = WC()->cart->get_cart();

		$enabled_payment_options = array_filter(
			WC()->payment_gateways->get_available_payment_gateways(),
			function ( $payment_gateway ) {
				if ( ! $payment_gateway instanceof WC_Payment_Gateway ) {
					return false;
				}

				return $payment_gateway->is_available();
			}
		);

		$enabled_payment_options = array_keys( $enabled_payment_options );

		$is_in_checkout_page = $checkout_page_id === $post->ID ? 'Yes' : 'No';
		$session             = WC()->session;
		if ( is_object( $session ) ) {
			$session->set( 'checkout_page_used', true );
			$session->save_data();
		}

		foreach ( $cart as $cart_item_key => $cart_item ) {
			/**
			* This filter is already documented in woocommerce/templates/cart/cart.php
			*/
			$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( ! $product || ! $product instanceof WC_Product ) {
				continue;
			}

			$data = $this->get_cart_checkout_shared_data();

			$data['from_checkout'] = $is_in_checkout_page;

			if ( ! empty( $data['products'] ) ) {
				unset( $data['products'] );
			}

			if ( ! empty( $data['shipping_options_count'] ) ) {
				unset( $data['shipping_options_count'] );
			}

			$data['pq'] = $cart_item['quantity'];

			$properties = $this->process_event_properties(
				'woocommerceanalytics_product_checkout',
				$data,
				$product->get_id()
			);

			wc_enqueue_js(
				"
				var cartItem_{$cart_item_key}_logged = false;
				var properties = {$properties};
				// Check if jQuery is available
				if ( typeof jQuery !== 'undefined' ) {
					// This is only triggered on the checkout shortcode.
					jQuery( document.body ).on( 'init_checkout', function () {
						if ( true === cartItem_{$cart_item_key}_logged ) {
							return;
						}
						if ( typeof wp !== 'undefined' && typeof wp.hooks !== 'undefined' && typeof wp.hooks.addAction === 'function' ) {
							wp.hooks.addAction( 'wcpay.payment-request.availability', 'wcpay', function ( args ) {
								properties.express_checkout = args.paymentRequestType;
							} );
						}
						properties.checkout_page_contains_checkout_block = '0';
						properties.checkout_page_contains_checkout_shortcode = '1';

						_wca.push( properties );
						cartItem_{$cart_item_key}_logged = true;

					} );
				}

				if (
					typeof wp !== 'undefined' &&
					typeof wp.data !== 'undefined' &&
					typeof wp.data.subscribe !== 'undefined'
				) {
					wp.data.subscribe( function () {
						if ( true === cartItem_{$cart_item_key}_logged ) {
							return;
						}

						const checkoutDataStore = wp.data.select( 'wc/store/checkout' );
						// Ensures we're not in Cart, but in Checkout page.
						if (
							typeof checkoutDataStore !== 'undefined' &&
							checkoutDataStore.getOrderId() !== 0
						) {
							properties.express_checkout = Object.keys( wc.wcBlocksRegistry.getExpressPaymentMethods() );
							properties.checkout_page_contains_checkout_block = '1';
							properties.checkout_page_contains_checkout_shortcode = '0';

							_wca.push( properties );
							cartItem_{$cart_item_key}_logged = true;
						}
					} );
				}
			"
			);

		}
	}

	/**
	 * After the checkout process, fire an event for each item in the order
	 *
	 * @param string $order_id Order Id.
	 */
	public function order_process( $order_id ) {
		$order = wc_get_order( $order_id );

		if (
			! $order
			|| ! $order instanceof WC_Order
		) {
			return;
		}

		$payment_option = $order->get_payment_method();

		if ( is_object( WC()->session ) ) {
			$create_account     = true === WC()->session->get( 'wc_checkout_createaccount_used' ) ? 'Yes' : 'No';
			$checkout_page_used = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';

		} else {
			$create_account     = 'No';
			$checkout_page_used = 'No';
		}

		$delayed_account_creation = ucfirst( get_option( 'woocommerce_enable_delayed_account_creation', 'Yes' ) );

		$guest_checkout = $order->get_user() ? 'No' : 'Yes';

		$express_checkout = 'null';
		// When the payment option is woocommerce_payment
		// See if Google Pay or Apple Pay was used.
		if ( 'woocommerce_payments' === $payment_option ) {
			$payment_option_title = $order->get_payment_method_title();
			if ( 'Google Pay (WooCommerce Payments)' === $payment_option_title ) {
				$express_checkout = array( 'google_pay' );
			} elseif ( 'Apple Pay (WooCommerce Payments)' === $payment_option_title ) {
				$express_checkout = array( 'apple_pay' );
			}
		}

		$checkout_page_contains_checkout_block         = '0';
			$checkout_page_contains_checkout_shortcode = '0';

		$order_source = $order->get_created_via();
		if ( 'store-api' === $order_source ) {
			$checkout_page_contains_checkout_block     = '1';
			$checkout_page_contains_checkout_shortcode = '0';
		} elseif ( 'checkout' === $order_source ) {
			$checkout_page_contains_checkout_block     = '0';
			$checkout_page_contains_checkout_shortcode = '1';
		}

		// loop through products in the order and queue a purchase event.
		foreach ( $order->get_items() as $order_item ) {
			// @phan-suppress-next-line PhanUndeclaredMethod -- Checked before being called. See also https://github.com/phan/phan/issues/1204.
			$product_id = is_callable( array( $order_item, 'get_product_id' ) ) ? $order_item->get_product_id() : -1;

			$order_items       = $order->get_items();
			$order_items_count = 0;
			if ( is_array( $order_items ) ) {
				$order_items_count = count( $order_items );
			}
			$order_coupons       = $order->get_coupons();
			$order_coupons_count = 0;
			if ( is_array( $order_coupons ) ) {
				$order_coupons_count = count( $order_coupons );
			}
			$this->record_event(
				'woocommerceanalytics_product_purchase',
				array(
					'oi'                       => $order->get_order_number(),
					'pq'                       => $order_item->get_quantity(),
					'payment_option'           => $payment_option,
					'create_account'           => $create_account,
					'guest_checkout'           => $guest_checkout,
					'delayed_account_creation' => $delayed_account_creation,
					'express_checkout'         => $express_checkout,
					'products_count'           => $order_items_count,
					'coupon_used'              => $order_coupons_count,
					'order_value'              => $order->get_total(),
					'from_checkout'            => $checkout_page_used,
					'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
					'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
				),
				$product_id
			);
		}
	}

	/**
	 * Listen for clicks on the "Update Cart" button to know if an item has been removed by
	 * updating its quantity to zero
	 */
	public function remove_from_cart_via_quantity() {
		$common_props = $this->render_properties_as_js(
			$this->get_common_properties()
		);

		wc_enqueue_js(
			"
			jQuery( 'button[name=update_cart]' ).on( 'click', function() {
				var cartItems = jQuery( '.cart_item' );
				cartItems.each( function( item ) {
					var qty = jQuery( this ).find( 'input.qty' );
					if ( qty && qty.val() === '0' ) {
						var productID = jQuery( this ).find( '.product-remove a' ).data( 'product_id' );
						_wca.push( {
							'_en': 'woocommerceanalytics_remove_from_cart',
							'pi': productID, " .
							$common_props . '
						} );
					}
				} );
			} );'
		);
	}

	/**
	 * Gets the inner blocks of a block.
	 *
	 * @param array $inner_blocks The inner blocks.
	 *
	 * @return array
	 */
	private function get_inner_blocks( $inner_blocks ) {
		$block_names = array();
		if ( ! empty( $inner_blocks['blockName'] ) ) {
			$block_names[] = $inner_blocks['blockName'];
		}
		if ( isset( $inner_blocks['innerBlocks'] ) && is_array( $inner_blocks['innerBlocks'] ) ) {
			$block_names = array_merge( $block_names, $this->get_inner_blocks( $inner_blocks['innerBlocks'] ) );
		}
		return $block_names;
	}

	/**
	 * Track adding items to the cart.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id Product added to cart.
	 * @param int    $quantity Quantity added to cart.
	 * @param int    $variation_id Product variation.
	 * @param array  $variation Variation attributes..
	 * @param array  $cart_item_data Other cart data.
	 */
	public function capture_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$referer_postid = isset( $_SERVER['HTTP_REFERER'] ) ? url_to_postid( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : 0;
		// if the referring post is not a product OR the product being added is not the same as post.
		// (eg. related product list on single product page) then include a product view event.
		$product_by_referer_postid = wc_get_product( $referer_postid );
		if ( ! $product_by_referer_postid instanceof WC_Product || (int) $product_id !== $referer_postid ) {
			$this->capture_event_in_session_data( $product_id, $quantity, 'woocommerceanalytics_product_view' );
		}
		// add cart event to the session data.
		$this->capture_event_in_session_data( $product_id, $quantity, 'woocommerceanalytics_add_to_cart' );
	}

	/**
	 * Track in-session data.
	 *
	 * @param int    $product_id Product ID.
	 * @param int    $quantity Quantity.
	 * @param string $event Fired event.
	 */
	public function capture_event_in_session_data( $product_id, $quantity, $event ) {

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$quantity = ( 0 === $quantity ) ? 1 : $quantity;

		// check for existing data.
		if ( is_object( WC()->session ) ) {
			$data = WC()->session->get( 'wca_session_data' );
			if ( empty( $data ) || ! is_array( $data ) ) {
				$data = array();
			}
		} else {
			$data = array();
		}

		// extract new event data.
		$new_data = array(
			'event'      => $event,
			'product_id' => (string) $product_id,
			'quantity'   => (string) $quantity,
		);

		// append new data.
		$data[] = $new_data;

		WC()->session->set( 'wca_session_data', $data );
	}

	/**
	 * Save createaccount post data to be used in $this->order_process.
	 *
	 * @param array $data post data from the checkout page.
	 *
	 * @return array
	 */
	public function save_checkout_post_data( array $data ) {
		$session = WC()->session;
		if ( is_object( $session ) ) {
			if ( isset( $data['createaccount'] ) && ! empty( $data['createaccount'] ) ) {
				$session->set( 'wc_checkout_createaccount_used', true );
				$session->save_data();
			}
		}
		return $data;
	}

	/**
	 * Capture the create account event. Similar to save_checkout_post_data but works with Store API.
	 *
	 * @param int   $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 */
	public function capture_created_customer( $customer_id, $new_customer_data ) {
		$session = WC()->session;
		if (
			is_object( $session )
			&& is_array( $new_customer_data )
			&& ! empty( $new_customer_data['source'] )
		) {
			if ( str_contains( $new_customer_data['source'], 'store-api' ) ) {
				$session->set( 'wc_checkout_createaccount_used', true );
				$session->save_data();
			}
		}
	}

	/**
	 * Capture the post checkout create account event.
	 *
	 * @param int   $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 */
	public function capture_post_checkout_created_customer( $customer_id, $new_customer_data ) {
		if (
			is_array( $new_customer_data )
			&& ! empty( $new_customer_data['source'] )
			&& str_contains( $new_customer_data['source'], 'delayed-account-creation' )
		) {

			$checkout_page_used                        = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';
			$checkout_page_contains_checkout_block     = '1';
			$checkout_page_contains_checkout_shortcode = '0';

			$this->record_event(
				'woocommerceanalytics_post_account_creation',
				array(
					'from_checkout' => $checkout_page_used,
					'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
					'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
				)
			);
		}
	}
}
