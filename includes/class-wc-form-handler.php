<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle frontend forms
 *
 * @class 		WC_Form_Handler
 * @version		2.2.0
 * @package		WooCommerce/Classes/
 * @category	Class
 * @author 		WooThemes
 */
class WC_Form_Handler {

	/**
	 * Hook in methods
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'save_address' ) );
		add_action( 'template_redirect', array( __CLASS__, 'save_account_details' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'checkout_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_login' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_registration' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_lost_password' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'process_reset_password' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'cancel_order' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'order_again' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'update_cart_action' ), 20 );
		add_action( 'wp_loaded', array( __CLASS__, 'add_to_cart_action' ), 20 );

		// May need $wp global to access query vars
		add_action( 'wp', array( __CLASS__, 'pay_action' ), 20 );
		add_action( 'wp', array( __CLASS__, 'add_payment_method_action' ), 20 );
	}

	/**
	 * Save and and update a billing or shipping address if the
	 * form was submitted through the user account page.
	 */
	public static function save_address() {
		global $wp;

		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
			return;
		}

		if ( empty( $_POST[ 'action' ] ) || 'edit_address' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-edit_address' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$load_address = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';

		$address = WC()->countries->get_address_fields( esc_attr( $_POST[ $load_address . '_country' ] ), $load_address . '_' );

		foreach ( $address as $key => $field ) {

			if ( ! isset( $field['type'] ) ) {
				$field['type'] = 'text';
			}

			// Get Value
			switch ( $field['type'] ) {
				case "checkbox" :
					$_POST[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
				break;
				default :
					$_POST[ $key ] = isset( $_POST[ $key ] ) ? wc_clean( $_POST[ $key ] ) : '';
				break;
			}

			// Hook to allow modification of value
			$_POST[ $key ] = apply_filters( 'woocommerce_process_myaccount_field_' . $key, $_POST[ $key ] );

			// Validation: Required fields
			if ( ! empty( $field['required'] ) && empty( $_POST[ $key ] ) ) {
				wc_add_notice( $field['label'] . ' ' . __( 'is a required field.', 'woocommerce' ), 'error' );
			}

			if ( ! empty( $_POST[ $key ] ) ) {

				// Validation rules
				if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
					foreach ( $field['validate'] as $rule ) {
						switch ( $rule ) {
							case 'postcode' :
								$_POST[ $key ] = strtoupper( str_replace( ' ', '', $_POST[ $key ] ) );

								if ( ! WC_Validation::is_postcode( $_POST[ $key ], $_POST[ $load_address . '_country' ] ) ) {
									wc_add_notice( __( 'Please enter a valid postcode/ZIP.', 'woocommerce' ), 'error' );
								} else {
									$_POST[ $key ] = wc_format_postcode( $_POST[ $key ], $_POST[ $load_address . '_country' ] );
								}
							break;
							case 'phone' :
								$_POST[ $key ] = wc_format_phone_number( $_POST[ $key ] );

								if ( ! WC_Validation::is_phone( $_POST[ $key ] ) ) {
									wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid phone number.', 'woocommerce' ), 'error' );
								}
							break;
							case 'email' :
								$_POST[ $key ] = strtolower( $_POST[ $key ] );

								if ( ! is_email( $_POST[ $key ] ) ) {
									wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid email address.', 'woocommerce' ), 'error' );
								}
							break;
						}
					}
				}
			}
		}

		if ( wc_notice_count( 'error' ) == 0 ) {

			foreach ( $address as $key => $field ) {
				update_user_meta( $user_id, $key, $_POST[ $key ] );
			}

			wc_add_notice( __( 'Address changed successfully.', 'woocommerce' ) );

			do_action( 'woocommerce_customer_save_address', $user_id, $load_address );

			wp_safe_redirect( wc_get_page_permalink( 'myaccount') );
			exit;
		}
	}

	/**
	 * Save the password/account details and redirect back to the my account page.
	 */
	public static function save_account_details() {

		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
			return;
		}

		if ( empty( $_POST[ 'action' ] ) || 'save_account_details' !== $_POST[ 'action' ] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'save_account_details' ) ) {
			return;
		}

		$errors       = new WP_Error();
		$user         = new stdClass();

		$user->ID     = (int) get_current_user_id();
		$current_user = get_user_by( 'id', $user->ID );

		if ( $user->ID <= 0 ) {
			return;
		}

		$account_first_name = ! empty( $_POST[ 'account_first_name' ] ) ? wc_clean( $_POST[ 'account_first_name' ] ) : '';
		$account_last_name  = ! empty( $_POST[ 'account_last_name' ] ) ? wc_clean( $_POST[ 'account_last_name' ] ) : '';
		$account_email      = ! empty( $_POST[ 'account_email' ] ) ? sanitize_email( $_POST[ 'account_email' ] ) : '';
		$pass_cur           = ! empty( $_POST[ 'password_current' ] ) ? $_POST[ 'password_current' ] : '';
		$pass1              = ! empty( $_POST[ 'password_1' ] ) ? $_POST[ 'password_1' ] : '';
		$pass2              = ! empty( $_POST[ 'password_2' ] ) ? $_POST[ 'password_2' ] : '';
		$save_pass          = true;

		$user->first_name   = $account_first_name;
		$user->last_name    = $account_last_name;
		$user->user_email   = $account_email;

		// Prevent emails being displayed, or leave alone.
		$user->display_name = is_email( $current_user->display_name ) ? $user->first_name : $current_user->display_name;

		if ( empty( $account_first_name ) || empty( $account_last_name ) ) {
			wc_add_notice( __( 'Please enter your name.', 'woocommerce' ), 'error' );
		}

		if ( empty( $account_email ) || ! is_email( $account_email ) ) {
			wc_add_notice( __( 'Please provide a valid email address.', 'woocommerce' ), 'error' );
		} elseif ( email_exists( $account_email ) && $account_email !== $current_user->user_email ) {
			wc_add_notice( __( 'This email address is already registered.', 'woocommerce' ), 'error' );
		}

		if ( ! empty( $pass1 ) && ! wp_check_password( $pass_cur, $current_user->user_pass, $current_user->ID ) ) {
			wc_add_notice( __( 'Your current password is incorrect.', 'woocommerce' ), 'error' );
			$save_pass = false;
		}

		if ( ! empty( $pass_cur ) && empty( $pass1 ) && empty( $pass2 ) ) {
			wc_add_notice( __( 'Please fill out all password fields.', 'woocommerce' ), 'error' );

			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass_cur ) ) {
			wc_add_notice( __( 'Please enter your current password.', 'woocommerce' ), 'error' );

			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass2 ) ) {
			wc_add_notice( __( 'Please re-enter your password.', 'woocommerce' ), 'error' );

			$save_pass = false;
		} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
			wc_add_notice( __( 'New passwords do not match.', 'woocommerce' ), 'error' );

			$save_pass = false;
		}

		if ( $pass1 && $save_pass ) {
			$user->user_pass = $pass1;
		}

		// Allow plugins to return their own errors.
		do_action_ref_array( 'woocommerce_save_account_details_errors', array( &$errors, &$user ) );

		if ( $errors->get_error_messages() ) {
			foreach ( $errors->get_error_messages() as $error ) {
				wc_add_notice( $error, 'error' );
			}
		}

		if ( wc_notice_count( 'error' ) === 0 ) {

			wp_update_user( $user ) ;

			wc_add_notice( __( 'Account details changed successfully.', 'woocommerce' ) );

			do_action( 'woocommerce_save_account_details', $user->ID );

			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}

	/**
	 * Process the checkout form.
	 */
	public static function checkout_action() {
		if ( isset( $_POST['woocommerce_checkout_place_order'] ) || isset( $_POST['woocommerce_checkout_update_totals'] ) ) {

			if ( WC()->cart->is_empty() ) {
				wp_redirect( wc_get_page_permalink( 'cart' ) );
				exit;
			}

			if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

			WC()->checkout()->process_checkout();
		}
	}

	/**
	 * Process the pay form.
	 */
	public static function pay_action() {
		global $wp;

		if ( isset( $_POST['woocommerce_pay'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-pay' ) ) {

			ob_start();

			// Pay for existing order
			$order_key  = $_GET['key'];
			$order_id   = absint( $wp->query_vars['order-pay'] );
			$order      = wc_get_order( $order_id );

			$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order );

			if ( $order->id == $order_id && $order->order_key == $order_key && $order->has_status( $valid_order_statuses ) ) {

				// Set customer location to order location
				if ( $order->billing_country ) {
					WC()->customer->set_country( $order->billing_country );
				}
				if ( $order->billing_state ) {
					WC()->customer->set_state( $order->billing_state );
				}
				if ( $order->billing_postcode ) {
					WC()->customer->set_postcode( $order->billing_postcode );
				}
				if ( $order->billing_city ) {
					WC()->customer->set_city( $order->billing_city );
				}

				// Update payment method
				if ( $order->needs_payment() ) {
					$payment_method = wc_clean( $_POST['payment_method'] );

					$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

					// Update meta
					update_post_meta( $order_id, '_payment_method', $payment_method );

					if ( isset( $available_gateways[ $payment_method ] ) ) {
						$payment_method_title = $available_gateways[ $payment_method ]->get_title();
					} else {
						$payment_method_title = '';
					}

					update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

					// Validate
					$available_gateways[ $payment_method ]->validate_fields();

					// Process
					if ( wc_notice_count( 'error' ) == 0 ) {

						$result = $available_gateways[ $payment_method ]->process_payment( $order_id );

						// Redirect to success/confirmation/payment page
						if ( 'success' == $result['result'] ) {
							wp_redirect( $result['redirect'] );
							exit;
						}

					}

				} else {
					// No payment was required for order
					$order->payment_complete();
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}

			}

		}
	}

	/**
	 * Process the add payment method form.
	 */
	public static function add_payment_method_action() {
		if ( isset( $_POST['woocommerce_add_payment_method'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-add-payment-method' ) ) {

			ob_start();

			$payment_method = wc_clean( $_POST['payment_method'] );

			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			// Validate
			$available_gateways[ $payment_method ]->validate_fields();

			// Process
			if ( wc_notice_count( 'wc_errors' ) == 0 ) {
				$result = $available_gateways[ $payment_method ]->add_payment_method();

				// Redirect to success/confirmation/payment page
				if ( $result['result'] == 'success' ) {
					wc_add_message( __( 'Payment method added.', 'woocommerce' ) );
					wp_redirect( $result['redirect'] );
					exit();
				}

			}

		}

	}

	/**
	 * Remove from cart/update.
	 */
	public static function update_cart_action() {

		// Add Discount
		if ( ! empty( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) ) {
			WC()->cart->add_discount( sanitize_text_field( $_POST['coupon_code'] ) );
		}

		// Remove Coupon Codes
		elseif ( isset( $_GET['remove_coupon'] ) ) {
			WC()->cart->remove_coupon( wc_clean( $_GET['remove_coupon'] ) );
		}

		// Remove from cart
		elseif ( ! empty( $_GET['remove_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-cart' ) ) {
			$cart_item_key = sanitize_text_field( $_GET['remove_item'] );

			if ( $cart_item = WC()->cart->get_cart_item( $cart_item_key ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );

				$product = wc_get_product( $cart_item['product_id'] );
				$undo    = WC()->cart->get_undo_url( $cart_item_key );

				wc_add_notice( sprintf( __( '%s removed. %sUndo?%s', 'woocommerce' ), apply_filters( 'woocommerce_cart_item_removed_title', $product ? $product->get_title() : __( 'Item', 'woocommerce' ), $cart_item ), '<a href="' . esc_url( $undo ) . '">', '</a>' ) );
			}

			$referer  = wp_get_referer() ? remove_query_arg( array( 'remove_item', 'add-to-cart', 'added-to-cart' ), add_query_arg( 'removed_item', '1', wp_get_referer() ) ) : WC()->cart->get_cart_url();
			wp_safe_redirect( $referer );
			exit;
		}

		// Undo Cart Item
		elseif ( ! empty( $_GET['undo_item'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-cart' ) ) {
			$cart_item_key = sanitize_text_field( $_GET['undo_item'] );

			WC()->cart->restore_cart_item( $cart_item_key );

			$referer  = wp_get_referer() ? remove_query_arg( array( 'undo_item', '_wpnonce' ), wp_get_referer() ) : WC()->cart->get_cart_url();
			wp_safe_redirect( $referer );
			exit;
		}

		// Update Cart - checks apply_coupon too because they are in the same form
		if ( ( ! empty( $_POST['apply_coupon'] ) || ! empty( $_POST['update_cart'] ) || ! empty( $_POST['proceed'] ) ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-cart' ) ) {

			$cart_updated = false;
			$cart_totals  = isset( $_POST['cart'] ) ? $_POST['cart'] : '';

			if ( ! WC()->cart->is_empty() && is_array( $cart_totals ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

					$_product = $values['data'];

					// Skip product if no updated quantity was posted
					if ( ! isset( $cart_totals[ $cart_item_key ] ) || ! isset( $cart_totals[ $cart_item_key ]['qty'] ) ) {
						continue;
					}

					// Sanitize
					$quantity = apply_filters( 'woocommerce_stock_amount_cart_item', wc_stock_amount( preg_replace( "/[^0-9\.]/", '', $cart_totals[ $cart_item_key ]['qty'] ) ), $cart_item_key );

					if ( '' === $quantity || $quantity == $values['quantity'] )
						continue;

					// Update cart validation
					$passed_validation 	= apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $values, $quantity );

					// is_sold_individually
					if ( $_product->is_sold_individually() && $quantity > 1 ) {
						wc_add_notice( sprintf( __( 'You can only have 1 %s in your cart.', 'woocommerce' ), $_product->get_title() ), 'error' );
						$passed_validation = false;
					}

					if ( $passed_validation ) {
						WC()->cart->set_quantity( $cart_item_key, $quantity, false );
						$cart_updated = true;
					}

				}
			}

			// Trigger action - let 3rd parties update the cart if they need to and update the $cart_updated variable
			$cart_updated = apply_filters( 'woocommerce_update_cart_action_cart_updated', $cart_updated );

			if ( $cart_updated ) {
				// Recalc our totals
				WC()->cart->calculate_totals();
			}

			if ( ! empty( $_POST['proceed'] ) ) {
				wp_safe_redirect( WC()->cart->get_checkout_url() );
				exit;
			} elseif ( $cart_updated ) {
				wc_add_notice( __( 'Cart updated.', 'woocommerce' ) );
				$referer = remove_query_arg( 'remove_coupon', ( wp_get_referer() ? wp_get_referer() : WC()->cart->get_cart_url() ) );
				wp_safe_redirect( $referer );
				exit;
			}
		}
	}

	/**
	 * Place a previous order again.
	 */
	public static function order_again() {

		// Nothing to do
		if ( ! isset( $_GET['order_again'] ) || ! is_user_logged_in() || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-order_again' ) ) {
			return;
		}

		// Clear current cart
		WC()->cart->empty_cart();

		// Load the previous order - Stop if the order does not exist
		$order = wc_get_order( absint( $_GET['order_again'] ) );

		if ( empty( $order->id ) ) {
			return;
		}

		if ( ! $order->has_status( 'completed' ) ) {
			return;
		}

		// Make sure the user is allowed to order again. By default it check if the
		// previous order belonged to the current user.
		if ( ! current_user_can( 'order_again', $order->id ) ) {
			return;
		}

		// Copy products from the order to the cart
		foreach ( $order->get_items() as $item ) {
			// Load all product info including variation data
			$product_id   = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item['product_id'] );
			$quantity     = (int) $item['qty'];
			$variation_id = (int) $item['variation_id'];
			$variations   = array();
			$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );

			foreach ( $item['item_meta'] as $meta_name => $meta_value ) {
				if ( taxonomy_is_product_attribute( $meta_name ) ) {
					$variations[ $meta_name ] = $meta_value[0];
				} elseif ( meta_is_product_attribute( $meta_name, $meta_value[0], $product_id ) ) {
					$variations[ $meta_name ] = $meta_value[0];
				}
			}

			// Add to cart validation
			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
				continue;
			}

			WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
		}

		do_action( 'woocommerce_ordered_again', $order->id );

		// Redirect to cart
		wc_add_notice( __( 'The cart has been filled with the items from your previous order.', 'woocommerce' ) );
		wp_safe_redirect( WC()->cart->get_cart_url() );
		exit;
	}

	/**
	 * Cancel a pending order.
	 */
	public static function cancel_order() {
		if ( isset( $_GET['cancel_order'] ) && isset( $_GET['order'] ) && isset( $_GET['order_id'] ) ) {

			$order_key        = $_GET['order'];
			$order_id         = absint( $_GET['order_id'] );
			$order            = wc_get_order( $order_id );
			$user_can_cancel  = current_user_can( 'cancel_order', $order_id );
			$order_can_cancel = $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_cancel', array( 'pending', 'failed' ) ) );
			$redirect         = $_GET['redirect'];

			if ( $order->has_status( 'cancelled' ) ) {
				// Already cancelled - take no action
			} elseif ( $user_can_cancel && $order_can_cancel && $order->id == $order_id && $order->order_key == $order_key && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'woocommerce-cancel_order' ) ) {

				// Cancel the order + restore stock
				$order->cancel_order( __('Order cancelled by customer.', 'woocommerce' ) );

				// Message
				wc_add_notice( apply_filters( 'woocommerce_order_cancelled_notice', __( 'Your order was cancelled.', 'woocommerce' ) ), apply_filters( 'woocommerce_order_cancelled_notice_type', 'notice' ) );

				do_action( 'woocommerce_cancelled_order', $order->id );

			} elseif ( $user_can_cancel && ! $order_can_cancel ) {
				wc_add_notice( __( 'Your order can no longer be cancelled. Please contact us if you need assistance.', 'woocommerce' ), 'error' );
			} else {
				wc_add_notice( __( 'Invalid order.', 'woocommerce' ), 'error' );
			}

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	 * Add to cart action
	 *
	 * Checks for a valid request, does validation (via hooks) and then redirects if valid.
	 *
	 * @param bool $url (default: false)
	 */
	public static function add_to_cart_action( $url = false ) {
		if ( empty( $_REQUEST['add-to-cart'] ) || ! is_numeric( $_REQUEST['add-to-cart'] ) ) {
			return;
		}

		$product_id          = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
		$was_added_to_cart   = false;
		$added_to_cart       = array();
		$adding_to_cart      = wc_get_product( $product_id );
		$add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->product_type, $adding_to_cart );

		// Check if the product is published
		if ( ! $adding_to_cart->is_purchasable() ) {
			wc_add_notice( __( 'Sorry, this product is unavailable.', 'woocommerce' ), 'error' );
			return;
		}

		// Variable product handling
		if ( 'variable' === $add_to_cart_handler ) {

			$variation_id       = empty( $_REQUEST['variation_id'] ) ? '' : absint( $_REQUEST['variation_id'] );
			$quantity           = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );
			$missing_attributes = array();
			$variations         = array();
			$attributes         = $adding_to_cart->get_attributes();
			$variation          = wc_get_product( $variation_id );

			// Verify all attributes
			foreach ( $attributes as $attribute ) {
				if ( ! $attribute['is_variation'] ) {
					continue;
				}

				$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );

				if ( isset( $_REQUEST[ $taxonomy ] ) ) {

					// Get value from post data
					if ( $attribute['is_taxonomy'] ) {
						// Don't use wc_clean as it destroys sanitized characters
						$value = sanitize_title( stripslashes( $_REQUEST[ $taxonomy ] ) );
					} else {
						$value = wc_clean( stripslashes( $_REQUEST[ $taxonomy ] ) );
					}

					// Get valid value from variation
					$valid_value = $variation->variation_data[ $taxonomy ];

					// Allow if valid
					if ( '' === $valid_value || $valid_value === $value ) {
						$variations[ $taxonomy ] = $value;
						continue;
					}

				} else {
					$missing_attributes[] = wc_attribute_label( $attribute['name'] );
				}
			}

			if ( $missing_attributes ) {
				wc_add_notice( sprintf( _n( '%s is a required field', '%s are required fields', sizeof( $missing_attributes ), 'woocommerce' ), wc_format_list_of_items( $missing_attributes ) ), 'error' );
				return;
			} elseif ( empty( $variation_id ) ) {
				wc_add_notice( __( 'Please choose product options&hellip;', 'woocommerce' ), 'error' );
				return;
			} else {
				// Add to cart validation
				$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

				if ( $passed_validation ) {
					if ( WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) !== false ) {
						wc_add_to_cart_message( $product_id );
						$was_added_to_cart = true;
						$added_to_cart[]   = $product_id;
					}
				}
			}

		// Grouped Products
		} elseif ( 'grouped' === $add_to_cart_handler ) {

			if ( ! empty( $_REQUEST['quantity'] ) && is_array( $_REQUEST['quantity'] ) ) {

				$quantity_set = false;

				foreach ( $_REQUEST['quantity'] as $item => $quantity ) {
					if ( $quantity <= 0 ) {
						continue;
					}

					$quantity_set = true;

					// Add to cart validation
					$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $item, $quantity );

					if ( $passed_validation ) {
						if ( WC()->cart->add_to_cart( $item, $quantity ) !== false ) {
							$was_added_to_cart = true;
							$added_to_cart[] = $item;
						}
					}
				}

				if ( $was_added_to_cart ) {
					wc_add_to_cart_message( $added_to_cart );
				}

				if ( ! $was_added_to_cart && ! $quantity_set ) {
					wc_add_notice( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ), 'error' );
					return;
				}

			} elseif ( $product_id ) {

				/* Link on product archives */
				wc_add_notice( __( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ), 'error' );
				return;

			}

		// Custom Handler
		} elseif ( has_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler ) ){

			do_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler, $url );
			return;

		// Simple Products
		} else {

			$quantity 			= empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );

			// Add to cart validation
			$passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

			if ( $passed_validation ) {
				// Add the product to the cart
				if ( WC()->cart->add_to_cart( $product_id, $quantity ) !== false ) {
					wc_add_to_cart_message( $product_id );
					$was_added_to_cart = true;
					$added_to_cart[] = $product_id;
				}
			}

		}

		// If we added the product to the cart we can now optionally do a redirect.
		if ( $was_added_to_cart && wc_notice_count( 'error' ) == 0 ) {

			$url = apply_filters( 'woocommerce_add_to_cart_redirect', $url );

			// If has custom URL redirect there
			if ( $url ) {
				wp_safe_redirect( $url );
				exit;
			}

			// Redirect to cart option
			elseif ( get_option('woocommerce_cart_redirect_after_add') == 'yes' ) {
				wp_safe_redirect( WC()->cart->get_cart_url() );
				exit;
			}

		}

	}

	/**
	 * Process the login form.
	 */
	public static function process_login() {
		if ( ! empty( $_POST['login'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-login' ) ) {

			try {
				$creds  = array();

				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $_POST['username'], $_POST['password'] );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . $validation_error->get_error_message() );
				}

				if ( empty( $_POST['username'] ) ) {
					throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'Username is required.', 'woocommerce' ) );
				}

				if ( empty( $_POST['password'] ) ) {
					throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'Password is required.', 'woocommerce' ) );
				}

				if ( is_email( $_POST['username'] ) && apply_filters( 'woocommerce_get_username_from_email', true ) ) {
					$user = get_user_by( 'email', $_POST['username'] );

					if ( isset( $user->user_login ) ) {
						$creds['user_login'] 	= $user->user_login;
					} else {
						throw new Exception( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . __( 'A user could not be found with this email address.', 'woocommerce' ) );
					}

				} else {
					$creds['user_login'] 	= $_POST['username'];
				}

				$creds['user_password'] = $_POST['password'];
				$creds['remember']      = isset( $_POST['rememberme'] );
				$secure_cookie          = is_ssl() ? true : false;
				$user                   = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), $secure_cookie );

				if ( is_wp_error( $user ) ) {
					$message = $user->get_error_message();
					$message = str_replace( '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', '<strong>' . esc_html( $_POST['username'] ) . '</strong>', $message );
					throw new Exception( $message );
				} else {

					if ( ! empty( $_POST['redirect'] ) ) {
						$redirect = $_POST['redirect'];
					} elseif ( wp_get_referer() ) {
						$redirect = wp_get_referer();
					} else {
						$redirect = wc_get_page_permalink( 'myaccount' );
					}

					// Feedback
					wc_add_notice( sprintf( __( 'You are now logged in as <strong>%s</strong>', 'woocommerce' ), $user->display_name ) );

					wp_redirect( apply_filters( 'woocommerce_login_redirect', $redirect, $user ) );
					exit;
				}

			} catch (Exception $e) {

				wc_add_notice( apply_filters('login_errors', $e->getMessage() ), 'error' );

			}
		}
	}

	/**
	 * Handle lost password form
	 */
	public static function process_lost_password() {
		if ( isset( $_POST['wc_reset_password'] ) && isset( $_POST['user_login'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'lost_password' ) ) {
			WC_Shortcode_My_Account::retrieve_password();
		}
	}

	/**
	 * Handle reset password form
	 */
	public static function process_reset_password() {
		$posted_fields = array( 'wc_reset_password', 'password_1', 'password_2', 'reset_key', 'reset_login', '_wpnonce' );

		foreach ( $posted_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return;
			}
			$posted_fields[ $field ] = $_POST[ $field ];
		}

		if ( ! wp_verify_nonce( $posted_fields['_wpnonce'], 'reset_password' ) ) {
			return;
		}

		$user = WC_Shortcode_My_Account::check_password_reset_key( $posted_fields['reset_key'], $posted_fields['reset_login'] );

		if ( $user instanceof WP_User ) {
			if ( empty( $posted_fields['password_1'] ) ) {
				wc_add_notice( __( 'Please enter your password.', 'woocommerce' ), 'error' );
			}

			if ( $posted_fields[ 'password_1' ] !== $posted_fields[ 'password_2' ] ) {
				wc_add_notice( __( 'Passwords do not match.', 'woocommerce' ), 'error' );
			}

			$errors = new WP_Error();

			do_action( 'validate_password_reset', $errors, $user );

			wc_add_wp_error_notices( $errors );

			if ( 0 === wc_notice_count( 'error' ) ) {
				WC_Shortcode_My_Account::reset_password( $user, $posted_fields['password_1'] );

				do_action( 'woocommerce_customer_reset_password', $user );

				wp_redirect( add_query_arg( 'reset', 'true', remove_query_arg( array( 'key', 'login' ) ) ) );
				exit;
			}
		}
	}

	/**
	 * Process the registration form.
	 */
	public static function process_registration() {
		if ( ! empty( $_POST['register'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-register' ) ) {
			$username = 'no' === get_option( 'woocommerce_registration_generate_username' ) ? $_POST['username'] : '';
			$password = 'no' === get_option( 'woocommerce_registration_generate_password' ) ? $_POST['password'] : '';
			$email    = $_POST['email'];

			try {
				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password, $email );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( $validation_error->get_error_message() );
				}

				// Anti-spam trap
				if ( ! empty( $_POST['email_2'] ) ) {
					throw new Exception( __( 'Anti-spam field was filled in.', 'woocommerce' ) );
				}

				$new_customer = wc_create_new_customer( sanitize_email( $email ), wc_clean( $username ), $password );

				if ( is_wp_error( $new_customer ) ) {
					throw new Exception( $new_customer->get_error_message() );
				}

				if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $new_customer ) ) {
					wc_set_customer_auth_cookie( $new_customer );
				}

				wp_safe_redirect( apply_filters( 'woocommerce_registration_redirect', wp_get_referer() ? wp_get_referer() : wc_get_page_permalink( 'myaccount' ) ) );
				exit;

			} catch ( Exception $e ) {
				wc_add_notice( '<strong>' . __( 'Error', 'woocommerce' ) . ':</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}
}

WC_Form_Handler::init();
