<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce WC_AJAX
 *
 * AJAX Event Handler
 *
 * @class 		WC_AJAX
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_AJAX {

	/**
	 * Hook in methods
	 */
	public static function init() {

		// woocommerce_EVENT => nopriv
		$ajax_events = array(
			'get_refreshed_fragments'                          => true,
			'apply_coupon'                                     => true,
			'remove_coupon'                                    => true,
			'update_shipping_method'                           => true,
			'update_order_review'                              => true,
			'add_to_cart'                                      => true,
			'checkout'                                         => true,
			'feature_product'                                  => false,
			'mark_order_status'                                => false,
			'add_attribute'                                    => false,
			'add_new_attribute'                                => false,
			'remove_variation'                                 => false,
			'remove_variations'                                => false,
			'save_attributes'                                  => false,
			'add_variation'                                    => false,
			'link_all_variations'                              => false,
			'revoke_access_to_download'                        => false,
			'grant_access_to_download'                         => false,
			'get_customer_details'                             => false,
			'add_order_item'                                   => false,
			'add_order_fee'                                    => false,
			'add_order_shipping'                               => false,
			'add_order_tax'                                    => false,
			'remove_order_item'                                => false,
			'remove_order_tax'                                 => false,
			'reduce_order_item_stock'                          => false,
			'increase_order_item_stock'                        => false,
			'add_order_item_meta'                              => false,
			'remove_order_item_meta'                           => false,
			'calc_line_taxes'                                  => false,
			'save_order_items'                                 => false,
			'load_order_items'                                 => false,
			'add_order_note'                                   => false,
			'delete_order_note'                                => false,
			'json_search_products'                             => false,
			'json_search_products_and_variations'              => false,
			'json_search_downloadable_products_and_variations' => false,
			'json_search_customers'                            => false,
			'term_ordering'                                    => false,
			'product_ordering'                                 => false,
			'refund_line_items'                                => false,
			'delete_refund'                                    => false
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Get a refreshed cart fragment
	 */
	public static function get_refreshed_fragments() {

		// Get mini cart
		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		// Fragments and mini cart are returned
		$data = array(
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
				)
			),
			'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart() ? md5( json_encode( WC()->cart->get_cart() ) ) : '', WC()->cart->get_cart() )
		);

		wp_send_json( $data );

	}

	/**
	 * AJAX apply coupon on checkout page
	 */
	public static function apply_coupon() {

		check_ajax_referer( 'apply-coupon', 'security' );

		if ( ! empty( $_POST['coupon_code'] ) ) {
			WC()->cart->add_discount( sanitize_text_field( $_POST['coupon_code'] ) );
		} else {
			wc_add_notice( WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
		}

		wc_print_notices();

		die();
	}

	/**
	 * AJAX remove coupon on cart and checkout page
	 */
	public static function remove_coupon() {

		check_ajax_referer( 'remove-coupon', 'security' );

		$coupon = wc_clean( $_POST['coupon'] );

		if ( ! isset( $coupon ) || empty( $coupon ) ) {
			wc_add_notice( __( 'Sorry there was a problem removing this coupon.', 'woocommerce' ) );

		} else {

			WC()->cart->remove_coupon( $coupon );

			wc_add_notice( __( 'Coupon has been removed.', 'woocommerce' ) );
		}

		wc_print_notices();

		die();
	}

	/**
	 * AJAX update shipping method on cart page
	 */
	public static function update_shipping_method() {

		check_ajax_referer( 'update-shipping-method', 'security' );

		if ( ! defined('WOOCOMMERCE_CART') ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) {
			foreach ( $_POST['shipping_method'] as $i => $value ) {
				$chosen_shipping_methods[ $i ] = wc_clean( $value );
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

		WC()->cart->calculate_totals();

		woocommerce_cart_totals();

		die();
	}

	/**
	 * AJAX update order review on checkout
	 */
	public static function update_order_review() {
		ob_start();

		check_ajax_referer( 'update-order-review', 'security' );

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		if ( 0 == sizeof( WC()->cart->get_cart() ) ) {
			$data = array(
				'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array(
					'form.woocommerce-checkout' => '<div class="woocommerce-error">' . __( 'Sorry, your session has expired.', 'woocommerce' ) . ' <a href="' . home_url() . '" class="wc-backward">' . __( 'Return to homepage', 'woocommerce' ) . '</a></div>'
				) )
			);

			wp_send_json( $data );

			die();
		}

		do_action( 'woocommerce_checkout_update_order_review', $_POST['post_data'] );

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) {
			foreach ( $_POST['shipping_method'] as $i => $value ) {
				$chosen_shipping_methods[ $i ] = wc_clean( $value );
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		WC()->session->set( 'chosen_payment_method', empty( $_POST['payment_method'] ) ? '' : $_POST['payment_method'] );

		if ( isset( $_POST['country'] ) ) {
			WC()->customer->set_country( $_POST['country'] );
		}

		if ( isset( $_POST['state'] ) ) {
			WC()->customer->set_state( $_POST['state'] );
		}

		if ( isset( $_POST['postcode'] ) ) {
			WC()->customer->set_postcode( $_POST['postcode'] );
		}

		if ( isset( $_POST['city'] ) ) {
			WC()->customer->set_city( $_POST['city'] );
		}

		if ( isset( $_POST['address'] ) ) {
			WC()->customer->set_address( $_POST['address'] );
		}

		if ( isset( $_POST['address_2'] ) ) {
			WC()->customer->set_address_2( $_POST['address_2'] );
		}

		if ( wc_ship_to_billing_address_only() ) {

			if ( isset( $_POST['country'] ) ) {
				WC()->customer->set_shipping_country( $_POST['country'] );
			}

			if ( isset( $_POST['state'] ) ) {
				WC()->customer->set_shipping_state( $_POST['state'] );
			}

			if ( isset( $_POST['postcode'] ) ) {
				WC()->customer->set_shipping_postcode( $_POST['postcode'] );
			}

			if ( isset( $_POST['city'] ) ) {
				WC()->customer->set_shipping_city( $_POST['city'] );
			}

			if ( isset( $_POST['address'] ) ) {
				WC()->customer->set_shipping_address( $_POST['address'] );
			}

			if ( isset( $_POST['address_2'] ) ) {
				WC()->customer->set_shipping_address_2( $_POST['address_2'] );
			}
		} else {

			if ( isset( $_POST['s_country'] ) ) {
				WC()->customer->set_shipping_country( $_POST['s_country'] );
			}

			if ( isset( $_POST['s_state'] ) ) {
				WC()->customer->set_shipping_state( $_POST['s_state'] );
			}

			if ( isset( $_POST['s_postcode'] ) ) {
				WC()->customer->set_shipping_postcode( $_POST['s_postcode'] );
			}

			if ( isset( $_POST['s_city'] ) ) {
				WC()->customer->set_shipping_city( $_POST['s_city'] );
			}

			if ( isset( $_POST['s_address'] ) ) {
				WC()->customer->set_shipping_address( $_POST['s_address'] );
			}

			if ( isset( $_POST['s_address_2'] ) ) {
				WC()->customer->set_shipping_address_2( $_POST['s_address_2'] );
			}
		}

		WC()->cart->calculate_totals();

		// Get order review fragment
		ob_start();
		woocommerce_order_review();
		$woocommerce_order_review = ob_get_clean();

		// Get checkout payment fragment
		ob_start();
		woocommerce_checkout_payment();
		$woocommerce_checkout_payment = ob_get_clean();

		// Get messages if reload checkout is not true
		$messages = '';
		if ( ! isset( WC()->session->reload_checkout ) ) {
			ob_start();
			wc_print_notices();
			$messages = ob_get_clean();
		}

		$data = array(
			'result'    => empty( $messages ) ? 'success' : 'failure',
			'messages'  => $messages,
			'reload'    => isset( WC()->session->reload_checkout ) ? 'true' : 'false',
			'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array(
				'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
				'.woocommerce-checkout-payment'            => $woocommerce_checkout_payment
			) )
		);

		wp_send_json( $data );

		die();
	}

	/**
	 * AJAX add to cart
	 */
	public static function add_to_cart() {
		ob_start();

		$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
		$quantity          = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		$product_status    = get_post_status( $product_id );

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) && 'publish' === $product_status ) {

			do_action( 'woocommerce_ajax_added_to_cart', $product_id );

			if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
				wc_add_to_cart_message( $product_id );
			}

			// Return fragments
			self::get_refreshed_fragments();

		} else {

			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = array(
				'error'       => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id )
			);

			wp_send_json( $data );

		}

		die();
	}

	/**
	 * Process ajax checkout form
	 */
	public static function checkout() {
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		WC()->checkout()->process_checkout();

		die(0);
	}

	/**
	 * Feature a product from admin
	 */
	public static function feature_product() {
		if ( current_user_can( 'edit_products' ) && check_admin_referer( 'woocommerce-feature-product' ) ) {
			$product_id = absint( $_GET['product_id'] );

			if ( 'product' === get_post_type( $product_id ) ) {
				update_post_meta( $product_id, '_featured', get_post_meta( $product_id, '_featured', true ) === 'yes' ? 'no' : 'yes' );

				delete_transient( 'wc_featured_products' );
			}
		}

		wp_safe_redirect( wp_get_referer() ? remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) : admin_url( 'edit.php?post_type=shop_order' ) );
		die();
	}

	/**
	 * Mark an order with a status
	 */
	public static function mark_order_status() {
		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'woocommerce-mark-order-status' ) ) {
			$status   = sanitize_text_field( $_GET['status'] );
			$order_id = absint( $_GET['order_id'] );

			if ( wc_is_order_status( 'wc-' . $status ) && $order_id ) {
				$order = wc_get_order( $order_id );
				$order->update_status( $status );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		die();
	}

	/**
	 * Add an attribute row
	 */
	public static function add_attribute() {
		ob_start();

		check_ajax_referer( 'add-attribute', 'security' );

		global $wc_product_attributes;

		$thepostid     = 0;
		$taxonomy      = sanitize_text_field( $_POST['taxonomy'] );
		$i             = absint( $_POST['i'] );
		$position      = 0;
		$metabox_class = array();
		$attribute     = array(
			'name'         => $taxonomy,
			'value'        => '',
			'is_visible'   => 1,
			'is_variation' => 0,
			'is_taxonomy'  => $taxonomy ? 1 : 0
		);

		if ( $taxonomy ) {
			$attribute_taxonomy = $wc_product_attributes[ $taxonomy ];
			$metabox_class[]    = 'taxonomy';
			$metabox_class[]    = $taxonomy;
			$attribute_label    = wc_attribute_label( $taxonomy );
		} else {
			$attribute_label = '';
		}

		include( 'admin/meta-boxes/views/html-product-attribute.php' );
		die();
	}

	/**
	 * Add a new attribute via ajax function
	 */
	public static function add_new_attribute() {
		ob_start();

		check_ajax_referer( 'add-attribute', 'security' );

		$taxonomy = esc_attr( $_POST['taxonomy'] );
		$term     = stripslashes( $_POST['term'] );

		if ( taxonomy_exists( $taxonomy ) ) {

			$result = wp_insert_term( $term, $taxonomy );

			if ( is_wp_error( $result ) ) {
				wp_send_json( array(
					'error' => $result->get_error_message()
				) );
			} else {
				wp_send_json( array(
					'term_id' => $result['term_id'],
					'name'    => $term,
					'slug'    => sanitize_title( $term ),
				) );
			}
		}

		die();
	}

	/**
	 * Delete variations via ajax function
	 */
	public static function remove_variations() {
		check_ajax_referer( 'delete-variations', 'security' );

		$variation_ids = (array) $_POST['variation_ids'];

		foreach ( $variation_ids as $variation_id ) {
			$variation = get_post( $variation_id );

			if ( $variation && 'product_variation' == $variation->post_type ) {
				wp_delete_post( $variation_id );
			}
		}

		die();
	}

	/**
	 * Save attributes via ajax
	 */
	public static function save_attributes() {

		check_ajax_referer( 'save-attributes', 'security' );

		// Get post data
		parse_str( $_POST['data'], $data );
		$post_id = absint( $_POST['post_id'] );

		// Save Attributes
		$attributes = array();

		if ( isset( $data['attribute_names'] ) ) {

			$attribute_names  = array_map( 'stripslashes', $data['attribute_names'] );
			$attribute_values = isset( $data['attribute_values'] ) ? $data['attribute_values'] : array();

			if ( isset( $data['attribute_visibility'] ) ) {
				$attribute_visibility = $data['attribute_visibility'];
			}

			if ( isset( $data['attribute_variation'] ) ) {
				$attribute_variation = $data['attribute_variation'];
			}

			$attribute_is_taxonomy = $data['attribute_is_taxonomy'];
			$attribute_position    = $data['attribute_position'];
			$attribute_names_count = sizeof( $attribute_names );

			for ( $i = 0; $i < $attribute_names_count; $i++ ) {
				if ( ! $attribute_names[ $i ] ) {
					continue;
				}

				$is_visible   = isset( $attribute_visibility[ $i ] ) ? 1 : 0;
				$is_variation = isset( $attribute_variation[ $i ] ) ? 1 : 0;
				$is_taxonomy  = $attribute_is_taxonomy[ $i ] ? 1 : 0;

				if ( $is_taxonomy ) {

					if ( isset( $attribute_values[ $i ] ) ) {

						// Select based attributes - Format values (posted values are slugs)
						if ( is_array( $attribute_values[ $i ] ) ) {
							$values = array_map( 'sanitize_title', $attribute_values[ $i ] );

						// Text based attributes - Posted values are term names - don't change to slugs
						} else {
							$values = array_map( 'stripslashes', array_map( 'strip_tags', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) );
						}

						// Remove empty items in the array
						$values = array_filter( $values, 'strlen' );

					} else {
						$values = array();
					}

					// Update post terms
					if ( taxonomy_exists( $attribute_names[ $i ] ) ) {
						wp_set_object_terms( $post_id, $values, $attribute_names[ $i ] );
					}

					if ( $values ) {
						// Add attribute to array, but don't set values
						$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
							'name' 			=> wc_clean( $attribute_names[ $i ] ),
							'value' 		=> '',
							'position' 		=> $attribute_position[ $i ],
							'is_visible' 	=> $is_visible,
							'is_variation' 	=> $is_variation,
							'is_taxonomy' 	=> $is_taxonomy
						);
					}

				} elseif ( isset( $attribute_values[ $i ] ) ) {

					// Text based, separate by pipe
					$values = implode( ' ' . WC_DELIMITER . ' ', array_map( 'trim', array_map( 'wp_kses_post', array_map( 'stripslashes', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) ) ) );

					// Custom attribute - Add attribute to array and set the values
					$attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
						'name' 			=> wc_clean( $attribute_names[ $i ] ),
						'value' 		=> $values,
						'position' 		=> $attribute_position[ $i ],
						'is_visible' 	=> $is_visible,
						'is_variation' 	=> $is_variation,
						'is_taxonomy' 	=> $is_taxonomy
					);
				}

			 }
		}

		if ( ! function_exists( 'attributes_cmp' ) ) {
			function attributes_cmp( $a, $b ) {
				if ( $a['position'] == $b['position'] ) {
					return 0;
				}

				return ( $a['position'] < $b['position'] ) ? -1 : 1;
			}
		}
		uasort( $attributes, 'attributes_cmp' );

		update_post_meta( $post_id, '_product_attributes', $attributes );

		die();
	}

	/**
	 * Add variation via ajax function
	 */
	public static function add_variation() {

		check_ajax_referer( 'add-variation', 'security' );

		$post_id = intval( $_POST['post_id'] );
		$loop = intval( $_POST['loop'] );

		$variation = array(
			'post_title'   => 'Product #' . $post_id . ' Variation',
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post_id,
			'post_type'    => 'product_variation'
		);

		$variation_id = wp_insert_post( $variation );

		do_action( 'woocommerce_create_product_variation', $variation_id );

		if ( $variation_id ) {

			$variation_post_status = 'publish';
			$variation_data = get_post_meta( $variation_id );
			$variation_data['variation_post_id'] = $variation_id;

			// Get attributes
			$attributes = (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );

			// Get tax classes
			$tax_classes                 = WC_Tax::get_tax_classes();
			$tax_class_options           = array();
			$tax_class_options['parent'] =__( 'Same as parent', 'woocommerce' );
			$tax_class_options['']       = __( 'Standard', 'woocommerce' );

			if ( $tax_classes ) {
				foreach ( $tax_classes as $class ) {
					$tax_class_options[ sanitize_title( $class ) ] = $class;
				}
			}

			$backorder_options = array(
				'no'     => __( 'Do not allow', 'woocommerce' ),
				'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
				'yes'    => __( 'Allow', 'woocommerce' )
			);

			$stock_status_options = array(
				'instock'    => __( 'In stock', 'woocommerce' ),
				'outofstock' => __( 'Out of stock', 'woocommerce' )
			);

			// Get parent data
			$parent_data = array(
				'id'                   => $post_id,
				'attributes'           => $attributes,
				'tax_class_options'    => $tax_class_options,
				'sku'                  => get_post_meta( $post_id, '_sku', true ),
				'weight'               => get_post_meta( $post_id, '_weight', true ),
				'length'               => get_post_meta( $post_id, '_length', true ),
				'width'                => get_post_meta( $post_id, '_width', true ),
				'height'               => get_post_meta( $post_id, '_height', true ),
				'tax_class'            => get_post_meta( $post_id, '_tax_class', true ),
				'backorder_options'    => $backorder_options,
				'stock_status_options' => $stock_status_options
			);

			if ( ! $parent_data['weight'] ) {
				$parent_data['weight'] = '0.00';
			}

			if ( ! $parent_data['length'] ) {
				$parent_data['length'] = '0';
			}

			if ( ! $parent_data['width'] ) {
				$parent_data['width'] = '0';
			}

			if ( ! $parent_data['height'] ) {
				$parent_data['height'] = '0';
			}

			$_tax_class          = '';
			$_downloadable_files = '';
			$_stock_status       = '';
			$_backorders         = '';
			$image_id            = 0;
			$_thumbnail_id       = '';
			$variation           = get_post( $variation_id ); // Get the variation object

			include( 'admin/meta-boxes/views/html-variation-admin.php' );
		}

		die();
	}

	/**
	 * Link all variations via ajax function
	 */
	public static function link_all_variations() {

		if ( ! defined( 'WC_MAX_LINKED_VARIATIONS' ) ) {
			define( 'WC_MAX_LINKED_VARIATIONS', 49 );
		}

		check_ajax_referer( 'link-variations', 'security' );

		@set_time_limit(0);

		$post_id = intval( $_POST['post_id'] );

		if ( ! $post_id ) {
			die();
		}

		$variations = array();
		$_product   = wc_get_product( $post_id, array( 'product_type' => 'variable' ) );

		// Put variation attributes into an array
		foreach ( $_product->get_attributes() as $attribute ) {

			if ( ! $attribute['is_variation'] ) {
				continue;
			}

			$attribute_field_name = 'attribute_' . sanitize_title( $attribute['name'] );

			if ( $attribute['is_taxonomy'] ) {
				$options = wc_get_product_terms( $post_id, $attribute['name'], array( 'fields' => 'slugs' ) );
			} else {
				$options = explode( WC_DELIMITER, $attribute['value'] );
			}

			$options = array_map( 'sanitize_title', array_map( 'trim', $options ) );

			$variations[ $attribute_field_name ] = $options;
		}

		// Quit out if none were found
		if ( sizeof( $variations ) == 0 ) {
			die();
		}

		// Get existing variations so we don't create duplicates
		$available_variations = array();

		foreach( $_product->get_children() as $child_id ) {
			$child = $_product->get_child( $child_id );

			if ( ! empty( $child->variation_id ) ) {
				$available_variations[] = $child->get_variation_attributes();
			}
		}

		// Created posts will all have the following data
		$variation_post_data = array(
			'post_title'   => 'Product #' . $post_id . ' Variation',
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post_id,
			'post_type'    => 'product_variation'
		);

		// Now find all combinations and create posts
		if ( ! function_exists( 'array_cartesian' ) ) {

			/**
			 * @param array $input
			 * @return array
			 */
			function array_cartesian( $input ) {
				$result = array();

				while ( list( $key, $values ) = each( $input ) ) {
					// If a sub-array is empty, it doesn't affect the cartesian product
					if ( empty( $values ) ) {
						continue;
					}

					// Special case: seeding the product array with the values from the first sub-array
					if ( empty( $result ) ) {
						foreach ( $values as $value ) {
							$result[] = array( $key => $value );
						}
					}
					else {
						// Second and subsequent input sub-arrays work like this:
						//   1. In each existing array inside $product, add an item with
						//      key == $key and value == first item in input sub-array
						//   2. Then, for each remaining item in current input sub-array,
						//      add a copy of each existing array inside $product with
						//      key == $key and value == first item in current input sub-array

						// Store all items to be added to $product here; adding them on the spot
						// inside the foreach will result in an infinite loop
						$append = array();
						foreach ( $result as &$product ) {
							// Do step 1 above. array_shift is not the most efficient, but it
							// allows us to iterate over the rest of the items with a simple
							// foreach, making the code short and familiar.
							$product[ $key ] = array_shift( $values );

							// $product is by reference (that's why the key we added above
							// will appear in the end result), so make a copy of it here
							$copy = $product;

							// Do step 2 above.
							foreach ( $values as $item ) {
								$copy[ $key ] = $item;
								$append[] = $copy;
							}

							// Undo the side effecst of array_shift
							array_unshift( $values, $product[ $key ] );
						}

						// Out of the foreach, we can add to $results now
						$result = array_merge( $result, $append );
					}
				}

				return $result;
			}
		}

		$variation_ids       = array();
		$added               = 0;
		$possible_variations = array_cartesian( $variations );

		foreach ( $possible_variations as $variation ) {

			// Check if variation already exists
			if ( in_array( $variation, $available_variations ) ) {
				continue;
			}

			$variation_id = wp_insert_post( $variation_post_data );

			$variation_ids[] = $variation_id;

			foreach ( $variation as $key => $value ) {
				update_post_meta( $variation_id, $key, $value );
			}

			$added++;

			do_action( 'product_variation_linked', $variation_id );

			if ( $added > WC_MAX_LINKED_VARIATIONS ) {
				break;
			}
		}

		delete_transient( 'wc_product_children_ids_' . $post_id );

		echo $added;

		die();
	}

	/**
	 * Delete download permissions via ajax function
	 */
	public static function revoke_access_to_download() {

		check_ajax_referer( 'revoke-access', 'security' );

		global $wpdb;

		$download_id = $_POST['download_id'];
		$product_id  = intval( $_POST['product_id'] );
		$order_id    = intval( $_POST['order_id'] );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE order_id = %d AND product_id = %d AND download_id = %s;", $order_id, $product_id, $download_id ) );

		do_action( 'woocommerce_ajax_revoke_access_to_product_download', $download_id, $product_id, $order_id );

		die();
	}

	/**
	 * Grant download permissions via ajax function
	 */
	public static function grant_access_to_download() {

		check_ajax_referer( 'grant-access', 'security' );

		global $wpdb;

		$wpdb->hide_errors();

		$order_id     = intval( $_POST['order_id'] );
		$product_ids  = $_POST['product_ids'];
		$loop         = intval( $_POST['loop'] );
		$file_counter = 0;
		$order        = wc_get_order( $order_id );

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			$files   = $product->get_files();

			if ( ! $order->billing_email ) {
				die();
			}

			if ( $files ) {
				foreach ( $files as $download_id => $file ) {
					if ( $inserted_id = wc_downloadable_file_permission( $download_id, $product_id, $order ) ) {

						// insert complete - get inserted data
						$download = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE permission_id = %d", $inserted_id ) );

						$loop ++;
						$file_counter ++;

						if ( isset( $file['name'] ) ) {
							$file_count = $file['name'];
						} else {
							$file_count = sprintf( __( 'File %d', 'woocommerce' ), $file_counter );
						}
						include( 'admin/meta-boxes/views/html-order-download-permission.php' );
					}
				}
			}
		}

		die();
	}

	/**
	 * Get customer details via ajax
	 */
	public static function get_customer_details() {
		ob_start();

		check_ajax_referer( 'get-customer-details', 'security' );

		$user_id      = (int) trim(stripslashes($_POST['user_id']));
		$type_to_load = esc_attr(trim(stripslashes($_POST['type_to_load'])));

		$customer_data = array(
			$type_to_load . '_first_name' => get_user_meta( $user_id, $type_to_load . '_first_name', true ),
			$type_to_load . '_last_name'  => get_user_meta( $user_id, $type_to_load . '_last_name', true ),
			$type_to_load . '_company'    => get_user_meta( $user_id, $type_to_load . '_company', true ),
			$type_to_load . '_address_1'  => get_user_meta( $user_id, $type_to_load . '_address_1', true ),
			$type_to_load . '_address_2'  => get_user_meta( $user_id, $type_to_load . '_address_2', true ),
			$type_to_load . '_city'       => get_user_meta( $user_id, $type_to_load . '_city', true ),
			$type_to_load . '_postcode'   => get_user_meta( $user_id, $type_to_load . '_postcode', true ),
			$type_to_load . '_country'    => get_user_meta( $user_id, $type_to_load . '_country', true ),
			$type_to_load . '_state'      => get_user_meta( $user_id, $type_to_load . '_state', true ),
			$type_to_load . '_email'      => get_user_meta( $user_id, $type_to_load . '_email', true ),
			$type_to_load . '_phone'      => get_user_meta( $user_id, $type_to_load . '_phone', true ),
		);

		$customer_data = apply_filters( 'woocommerce_found_customer_details', $customer_data, $user_id, $type_to_load );

		wp_send_json( $customer_data );

	}

	/**
	 * Add order item via ajax
	 */
	public static function add_order_item() {
		check_ajax_referer( 'order-item', 'security' );

		$item_to_add = sanitize_text_field( $_POST['item_to_add'] );
		$order_id    = absint( $_POST['order_id'] );

		// Find the item
		if ( ! is_numeric( $item_to_add ) ) {
			die();
		}

		$post = get_post( $item_to_add );

		if ( ! $post || ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) ) {
			die();
		}

		$_product    = wc_get_product( $post->ID );
		$order       = wc_get_order( $order_id );
		$order_taxes = $order->get_taxes();
		$class       = 'new_row';

		// Set values
		$item = array();

		$item['product_id']        = $_product->id;
		$item['variation_id']      = isset( $_product->variation_id ) ? $_product->variation_id : '';
		$item['variation_data']    = $item['variation_id'] ? $_product->get_variation_attributes() : '';
		$item['name']              = $_product->get_title();
		$item['tax_class']         = $_product->get_tax_class();
		$item['qty']               = 1;
		$item['line_subtotal']     = wc_format_decimal( $_product->get_price_excluding_tax() );
		$item['line_subtotal_tax'] = '';
		$item['line_total']        = wc_format_decimal( $_product->get_price_excluding_tax() );
		$item['line_tax']          = '';

		// Add line item
		$item_id = wc_add_order_item( $order_id, array(
			'order_item_name' 		=> $item['name'],
			'order_item_type' 		=> 'line_item'
		) );

		// Add line item meta
		if ( $item_id ) {
			wc_add_order_item_meta( $item_id, '_qty', $item['qty'] );
			wc_add_order_item_meta( $item_id, '_tax_class', $item['tax_class'] );
			wc_add_order_item_meta( $item_id, '_product_id', $item['product_id'] );
			wc_add_order_item_meta( $item_id, '_variation_id', $item['variation_id'] );
			wc_add_order_item_meta( $item_id, '_line_subtotal', $item['line_subtotal'] );
			wc_add_order_item_meta( $item_id, '_line_subtotal_tax', $item['line_subtotal_tax'] );
			wc_add_order_item_meta( $item_id, '_line_total', $item['line_total'] );
			wc_add_order_item_meta( $item_id, '_line_tax', $item['line_tax'] );

			// Since 2.2
			wc_add_order_item_meta( $item_id, '_line_tax_data', array( 'total' => array(), 'subtotal' => array() ) );

			// Store variation data in meta
			if ( $item['variation_data'] && is_array( $item['variation_data'] ) ) {
				foreach ( $item['variation_data'] as $key => $value ) {
					wc_add_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value );
				}
			}

			do_action( 'woocommerce_ajax_add_order_item_meta', $item_id, $item );
		}

		$item          = apply_filters( 'woocommerce_ajax_order_item', $item, $item_id );

		include( 'admin/meta-boxes/views/html-order-item.php' );

		// Quit out
		die();
	}

	/**
	 * Add order fee via ajax
	 */
	public static function add_order_fee() {

		check_ajax_referer( 'order-item', 'security' );

		$order_id      = absint( $_POST['order_id'] );
		$order         = wc_get_order( $order_id );
		$order_taxes   = $order->get_taxes();
		$item          = array();

		// Add new fee
		$fee            = new stdClass();
		$fee->name      = '';
		$fee->tax_class = '';
		$fee->taxable   = $fee->tax_class !== '0';
		$fee->amount    = '';
		$fee->tax       = '';
		$fee->tax_data  = array();
		$item_id        = $order->add_fee( $fee );

		include( 'admin/meta-boxes/views/html-order-fee.php' );

		// Quit out
		die();
	}

	/**
	 * Add order shipping cost via ajax
	 */
	public static function add_order_shipping() {

		check_ajax_referer( 'order-item', 'security' );

		$order_id         = absint( $_POST['order_id'] );
		$order            = wc_get_order( $order_id );
		$order_taxes      = $order->get_taxes();
		$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
		$item             = array();

		// Add new shipping
		$shipping        = new stdClass();
		$shipping->label = '';
		$shipping->id    = '';
		$shipping->cost  = '';
		$shipping->taxes = array();
		$item_id         = $order->add_shipping( $shipping );

		include( 'admin/meta-boxes/views/html-order-shipping.php' );

		// Quit out
		die();
	}

	/**
	 * Add order tax column via ajax
	 */
	public static function add_order_tax() {
		global $wpdb;

		check_ajax_referer( 'order-item', 'security' );

		$order_id = absint( $_POST['order_id'] );
		$rate_id  = absint( $_POST['rate_id'] );
		$order    = wc_get_order( $order_id );
		$data     = get_post_meta( $order_id );

		// Add new tax
		$order->add_tax( $rate_id, 0, 0 );

		// Return HTML items
		include( 'admin/meta-boxes/views/html-order-items.php' );

		die();
	}

	/**
	 * Remove an order item
	 */
	public static function remove_order_item() {
		check_ajax_referer( 'order-item', 'security' );

		$order_item_ids = $_POST['order_item_ids'];

		if ( ! is_array( $order_item_ids ) && is_numeric( $order_item_ids ) ) {
			$order_item_ids = array( $order_item_ids );
		}

		if ( sizeof( $order_item_ids ) > 0 ) {
			foreach( $order_item_ids as $id ) {
				wc_delete_order_item( absint( $id ) );
			}
		}

		die();
	}

	/**
	 * Remove an order tax
	 */
	public static function remove_order_tax() {

		check_ajax_referer( 'order-item', 'security' );

		$order_id = absint( $_POST['order_id'] );
		$rate_id  = absint( $_POST['rate_id'] );

		wc_delete_order_item( $rate_id );

		// Return HTML items
		$order = wc_get_order( $order_id );
		$data  = get_post_meta( $order_id );
		include( 'admin/meta-boxes/views/html-order-items.php' );

		die();
	}

	/**
	 * Reduce order item stock
	 */
	public static function reduce_order_item_stock() {
		check_ajax_referer( 'order-item', 'security' );

		$order_id       = absint( $_POST['order_id'] );
		$order_item_ids = isset( $_POST['order_item_ids'] ) ? $_POST['order_item_ids'] : array();
		$order_item_qty = isset( $_POST['order_item_qty'] ) ? $_POST['order_item_qty'] : array();
		$order          = wc_get_order( $order_id );
		$order_items    = $order->get_items();
		$return         = array();

		if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) {

			foreach ( $order_items as $item_id => $order_item ) {

				// Only reduce checked items
				if ( ! in_array( $item_id, $order_item_ids ) ) {
					continue;
				}

				$_product = $order->get_product_from_item( $order_item );

				if ( $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) {
					$stock_change = apply_filters( 'woocommerce_reduce_order_stock_quantity', $order_item_qty[ $item_id ], $item_id );
					$new_stock    = $_product->reduce_stock( $stock_change );

					$return[] = sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $order_item['product_id'], $new_stock + $stock_change, $new_stock );
					$order->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $order_item['product_id'], $new_stock + $stock_change, $new_stock ) );
					$order->send_stock_notifications( $_product, $new_stock, $order_item_qty[ $item_id ] );
				}
			}

			do_action( 'woocommerce_reduce_order_stock', $order );

			if ( empty( $return ) ) {
				$return[] = __( 'No products had their stock reduced - they may not have stock management enabled.', 'woocommerce' );
			}

			echo implode( ', ', $return );
		}

		die();
	}

	/**
	 * Increase order item stock
	 */
	public static function increase_order_item_stock() {
		check_ajax_referer( 'order-item', 'security' );

		$order_id       = absint( $_POST['order_id'] );
		$order_item_ids = isset( $_POST['order_item_ids'] ) ? $_POST['order_item_ids'] : array();
		$order_item_qty = isset( $_POST['order_item_qty'] ) ? $_POST['order_item_qty'] : array();
		$order          = wc_get_order( $order_id );
		$order_items    = $order->get_items();
		$return         = array();

		if ( $order && ! empty( $order_items ) && sizeof( $order_item_ids ) > 0 ) {

			foreach ( $order_items as $item_id => $order_item ) {

				// Only reduce checked items
				if ( ! in_array( $item_id, $order_item_ids ) ) {
					continue;
				}

				$_product = $order->get_product_from_item( $order_item );

				if ( $_product->exists() && $_product->managing_stock() && isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) {

					$old_stock    = $_product->stock;
					$stock_change = apply_filters( 'woocommerce_restore_order_stock_quantity', $order_item_qty[ $item_id ], $item_id );
					$new_quantity = $_product->increase_stock( $stock_change );

					$return[] = sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity );
					$order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity ) );
				}
			}

			do_action( 'woocommerce_restore_order_stock', $order );

			if ( empty( $return ) ) {
				$return[] = __( 'No products had their stock increased - they may not have stock management enabled.', 'woocommerce' );
			}

			echo implode( ', ', $return );
		}

		die();
	}

	/**
	 * Add some meta to a line item
	 */
	public static function add_order_item_meta() {
		check_ajax_referer( 'order-item', 'security' );

		$meta_id = wc_add_order_item_meta( absint( $_POST['order_item_id'] ), __( 'Name', 'woocommerce' ), __( 'Value', 'woocommerce' ) );

		if ( $meta_id ) {
			echo '<tr data-meta_id="' . esc_attr( $meta_id ) . '"><td><input type="text" name="meta_key[' . $meta_id . ']" /><textarea name="meta_value[' . $meta_id . ']"></textarea></td><td width="1%"><button class="remove_order_item_meta button">&times;</button></td></tr>';
		}

		die();
	}

	/**
	 * Remove meta from a line item
	 */
	public static function remove_order_item_meta() {
		global $wpdb;

		check_ajax_referer( 'order-item', 'security' );

		$meta_id = absint( $_POST['meta_id'] );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_id = %d", $meta_id ) );

		die();
	}

	/**
	 * Calc line tax
	 */
	public static function calc_line_taxes() {
		global $wpdb;

		check_ajax_referer( 'calc-totals', 'security' );

		$tax            = new WC_Tax();
		$order_id       = absint( $_POST['order_id'] );
		$items          = array();
		$country        = strtoupper( esc_attr( $_POST['country'] ) );
		$state          = strtoupper( esc_attr( $_POST['state'] ) );
		$postcode       = strtoupper( esc_attr( $_POST['postcode'] ) );
		$city           = sanitize_title( esc_attr( $_POST['city'] ) );
		$order          = wc_get_order( $order_id );
		$taxes          = array();
		$shipping_taxes = array();

		// Parse the jQuery serialized items
		parse_str( $_POST['items'], $items );

		// Prevent undefined warnings
		if ( ! isset( $items['line_tax'] ) ) {
			$items['line_tax'] = array();
		}
		if ( ! isset( $items['line_subtotal_tax'] ) ) {
			$items['line_subtotal_tax'] = array();
		}
		$items['order_taxes'] = array();

		// Action
		$items = apply_filters( 'woocommerce_ajax_calc_line_taxes', $items, $order_id, $country, $_POST );

		// Get items and fees taxes
		if ( isset( $items['order_item_id'] ) ) {
			$line_total = $line_subtotal = $order_item_tax_class = array();

			foreach ( $items['order_item_id'] as $item_id ) {
				$item_id                          = absint( $item_id );
				$line_total[ $item_id ]           = isset( $items['line_total'][ $item_id ] ) ? wc_format_decimal( $items['line_total'][ $item_id ] ) : 0;
				$line_subtotal[ $item_id ]        = isset( $items['line_subtotal'][ $item_id ] ) ? wc_format_decimal( $items['line_subtotal'][ $item_id ] ) : $line_total[ $item_id ];
				$order_item_tax_class[ $item_id ] = isset( $items['order_item_tax_class'][ $item_id ] ) ? sanitize_text_field( $items['order_item_tax_class'][ $item_id ] ) : '';
				$product_id                       = $order->get_item_meta( $item_id, '_product_id', true );

				// Get product details
				if ( get_post_type( $product_id ) == 'product' ) {
					$_product        = wc_get_product( $product_id );
					$item_tax_status = $_product->get_tax_status();
				} else {
					$item_tax_status = 'taxable';
				}

				if ( '0' !== $order_item_tax_class[ $item_id ] && 'taxable' === $item_tax_status ) {
					$tax_rates = WC_Tax::find_rates( array(
						'country'   => $country,
						'state'     => $state,
						'postcode'  => $postcode,
						'city'      => $city,
						'tax_class' => $order_item_tax_class[ $item_id ]
					) );

					$line_taxes          = WC_Tax::calc_tax( $line_total[ $item_id ], $tax_rates, false );
					$line_subtotal_taxes = WC_Tax::calc_tax( $line_subtotal[ $item_id ], $tax_rates, false );

					// Set the new line_tax
					foreach ( $line_taxes as $_tax_id => $_tax_value ) {
						$items['line_tax'][ $item_id ][ $_tax_id ] = $_tax_value;
					}

					// Set the new line_subtotal_tax
					foreach ( $line_subtotal_taxes as $_tax_id => $_tax_value ) {
						$items['line_subtotal_tax'][ $item_id ][ $_tax_id ] = $_tax_value;
					}

					// Sum the item taxes
					foreach ( array_keys( $taxes + $line_taxes ) as $key ) {
						$taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
					}
				}
			}
		}

		// Get shipping taxes
		if ( isset( $items['shipping_method_id'] ) ) {
			$matched_tax_rates = array();

			$tax_rates = WC_Tax::find_rates( array(
				'country'   => $country,
				'state'     => $state,
				'postcode'  => $postcode,
				'city'      => $city,
				'tax_class' => ''
			) );

			if ( $tax_rates ) {
				foreach ( $tax_rates as $key => $rate ) {
					if ( isset( $rate['shipping'] ) && 'yes' == $rate['shipping'] ) {
						$matched_tax_rates[ $key ] = $rate;
					}
				}
			}

			$shipping_cost = $shipping_taxes = array();

			foreach ( $items['shipping_method_id'] as $item_id ) {
				$item_id                   = absint( $item_id );
				$shipping_cost[ $item_id ] = isset( $items['shipping_cost'][ $item_id ] ) ? wc_format_decimal( $items['shipping_cost'][ $item_id ] ) : 0;
				$_shipping_taxes           = WC_Tax::calc_shipping_tax( $shipping_cost[ $item_id ], $matched_tax_rates );

				// Set the new shipping_taxes
				foreach ( $_shipping_taxes as $_tax_id => $_tax_value ) {
					$items['shipping_taxes'][ $item_id ][ $_tax_id ] = $_tax_value;

					$shipping_taxes[ $_tax_id ] = isset( $shipping_taxes[ $_tax_id ] ) ? $shipping_taxes[ $_tax_id ] + $_tax_value : $_tax_value;
				}
			}
		}

		// Remove old tax rows
		$order->remove_order_items( 'tax' );

		// Add tax rows
		foreach ( array_keys( $taxes + $shipping_taxes ) as $tax_rate_id ) {
			$order->add_tax( $tax_rate_id, isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ] : 0, isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] : 0 );
		}

		// Create the new order_taxes
		foreach ( $order->get_taxes() as $tax_id => $tax_item ) {
			$items['order_taxes'][ $tax_id ] = absint( $tax_item['rate_id'] );
		}

		// Save order items
		wc_save_order_items( $order_id, $items );

		// Return HTML items
		$order = wc_get_order( $order_id );
		$data  = get_post_meta( $order_id );
		include( 'admin/meta-boxes/views/html-order-items.php' );

		die();
	}

	/**
	 * Save order items via ajax
	 */
	public static function save_order_items() {
		check_ajax_referer( 'order-item', 'security' );

		if ( isset( $_POST['order_id'] ) && isset( $_POST['items'] ) ) {
			$order_id = absint( $_POST['order_id'] );

			// Parse the jQuery serialized items
			$items = array();
			parse_str( $_POST['items'], $items );

			// Save order items
			wc_save_order_items( $order_id, $items );

			// Return HTML items
			$order = wc_get_order( $order_id );
			$data  = get_post_meta( $order_id );
			include( 'admin/meta-boxes/views/html-order-items.php' );
		}

		die();
	}

	/**
	 * Load order items via ajax
	 */
	public static function load_order_items() {
		check_ajax_referer( 'order-item', 'security' );

		// Return HTML items
		$order_id = absint( $_POST['order_id'] );
		$order    = wc_get_order( $order_id );
		$data     = get_post_meta( $order_id );
		include( 'admin/meta-boxes/views/html-order-items.php' );

		die();
	}

	/**
	 * Add order note via ajax
	 */
	public static function add_order_note() {

		check_ajax_referer( 'add-order-note', 'security' );

		$post_id   = (int) $_POST['post_id'];
		$note      = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );
		$note_type = $_POST['note_type'];

		$is_customer_note = $note_type == 'customer' ? 1 : 0;

		if ( $post_id > 0 ) {
			$order      = wc_get_order( $post_id );
			$comment_id = $order->add_order_note( $note, $is_customer_note );

			echo '<li rel="' . esc_attr( $comment_id ) . '" class="note ';
			if ( $is_customer_note ) {
				echo 'customer-note';
			}
			echo '"><div class="note_content">';
			echo wpautop( wptexturize( $note ) );
			echo '</div><p class="meta"><a href="#" class="delete_note">'.__( 'Delete note', 'woocommerce' ).'</a></p>';
			echo '</li>';
		}

		// Quit out
		die();
	}

	/**
	 * Delete order note via ajax
	 */
	public static function delete_order_note() {

		check_ajax_referer( 'delete-order-note', 'security' );

		$note_id = (int) $_POST['note_id'];

		if ( $note_id > 0 ) {
			wp_delete_comment( $note_id );
		}

		// Quit out
		die();
	}

	/**
	 * Search for products and echo json
	 *
	 * @param string $x (default: '')
	 * @param string $post_types (default: array('product'))
	 */
	public static function json_search_products( $x = '', $post_types = array('product') ) {
		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		$term = (string) wc_clean( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		if ( is_numeric( $term ) ) {

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__in'       => array(0, $term),
				'fields'         => 'ids'
			);

			$args2 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $term,
				'fields'         => 'ids'
			);

			$args3 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_sku',
						'value'   => $term,
						'compare' => 'LIKE'
					)
				),
				'fields'         => 'ids'
			);

			$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) );

		} else {

			$args = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids'
			);

			$args2 = array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
					'key'     => '_sku',
					'value'   => $term,
					'compare' => 'LIKE'
					)
				),
				'fields'         => 'ids'
			);

			$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );

		}

		$found_products = array();

		if ( $posts ) {
			foreach ( $posts as $post ) {
				$product = wc_get_product( $post );

				$found_products[ $post ] = $product->get_formatted_name();
			}
		}

		$found_products = apply_filters( 'woocommerce_json_search_found_products', $found_products );

		wp_send_json( $found_products );

	}

	/**
	 * Search for product variations and return json
	 *
	 * @access public
	 * @return void
	 * @see WC_AJAX::json_search_products()
	 */
	public static function json_search_products_and_variations() {
		self::json_search_products( '', array('product', 'product_variation') );
	}

	/**
	 * Search for customers and return json
	 */
	public static function json_search_customers() {
		ob_start();

		check_ajax_referer( 'search-customers', 'security' );

		$term = wc_clean( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		$found_customers = array();

		add_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

		$customers_query = new WP_User_Query( apply_filters( 'woocommerce_json_search_customers_query', array(
			'fields'         => 'all',
			'orderby'        => 'display_name',
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' )
		) ) );

		remove_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

		$customers = $customers_query->get_results();

		if ( $customers ) {
			foreach ( $customers as $customer ) {
				$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')';
			}
		}

		wp_send_json( $found_customers );

	}

	/**
	 * Search for downloadable product variations and return json
	 *
	 * @access public
	 * @return void
	 * @see WC_AJAX::json_search_products()
	 */
	public static function json_search_downloadable_products_and_variations() {
		ob_start();

		$term = (string) wc_clean( stripslashes( $_GET['term'] ) );

		$args = array(
			'post_type'      => array( 'product', 'product_variation' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'order'          => 'ASC',
			'orderby'        => 'parent title',
			'meta_query'     => array(
				array(
					'key'   => '_downloadable',
					'value' => 'yes'
				)
			),
			's'              => $term
		);

		$posts = get_posts( $args );
		$found_products = array();

		if ( $posts ) {
			foreach ( $posts as $post ) {
				$product = wc_get_product( $post->ID );
				$found_products[ $post->ID ] = $product->get_formatted_name();
			}
		}

		wp_send_json( $found_products );

	}

	/**
	 * When searching using the WP_User_Query, search names (user meta) too
	 * @param  object $query
	 * @return object
	 */
	public static function json_search_customer_name( $query ) {
		global $wpdb;

		$term = wc_clean( stripslashes( $_GET['term'] ) );
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			$term = $wpdb->esc_like( $term );
		} else {
			$term = like_escape( $term );
		}

		$query->query_from  .= " INNER JOIN {$wpdb->usermeta} AS user_name ON {$wpdb->users}.ID = user_name.user_id AND ( user_name.meta_key = 'first_name' OR user_name.meta_key = 'last_name' ) ";
		$query->query_where .= $wpdb->prepare( " OR user_name.meta_value LIKE %s ", '%' . $term . '%' );
	}

	/**
	 * Ajax request handling for categories ordering
	 */
	public static function term_ordering() {
		$id       = (int) $_POST['id'];
		$next_id  = isset( $_POST['nextid'] ) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
		$taxonomy = isset( $_POST['thetaxonomy'] ) ? esc_attr( $_POST['thetaxonomy'] ) : null;
		$term     = get_term_by('id', $id, $taxonomy);

		if ( ! $id || ! $term || ! $taxonomy ) {
			die(0);
		}

		wc_reorder_terms( $term, $next_id, $taxonomy );

		$children = get_terms( $taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0" );

		if ( $term && sizeof( $children ) ) {
			echo 'children';
			die();
		}
	}

	/**
	 * Ajax request handling for product ordering
	 *
	 * Based on Simple Page Ordering by 10up (http://wordpress.org/extend/plugins/simple-page-ordering/)
	 */
	public static function product_ordering() {
		global $wpdb;

		ob_start();

		// check permissions again and make sure we have what we need
		if ( ! current_user_can('edit_products') || empty( $_POST['id'] ) || ( ! isset( $_POST['previd'] ) && ! isset( $_POST['nextid'] ) ) ) {
			die(-1);
		}

		// real post?
		if ( ! $post = get_post( $_POST['id'] ) ) {
			die(-1);
		}

		$previd  = isset( $_POST['previd'] ) ? $_POST['previd'] : false;
		$nextid  = isset( $_POST['nextid'] ) ? $_POST['nextid'] : false;
		$new_pos = array(); // store new positions for ajax

		$siblings = $wpdb->get_results( $wpdb->prepare('
			SELECT ID, menu_order FROM %1$s AS posts
			WHERE 	posts.post_type 	= \'product\'
			AND 	posts.post_status 	IN ( \'publish\', \'pending\', \'draft\', \'future\', \'private\' )
			AND 	posts.ID			NOT IN (%2$d)
			ORDER BY posts.menu_order ASC, posts.ID DESC
		', $wpdb->posts, $post->ID) );

		$menu_order = 0;

		foreach ( $siblings as $sibling ) {

			// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
			if ( $nextid == $sibling->ID ) {
				$wpdb->update(
					$wpdb->posts,
					array(
						'menu_order' => $menu_order
					),
					array( 'ID' => $post->ID ),
					array( '%d' ),
					array( '%d' )
				);
				$new_pos[ $post->ID ] = $menu_order;
				$menu_order++;
			}

			// if repositioned post has been set, and new items are already in the right order, we can stop
			if ( isset( $new_pos[ $post->ID ] ) && $sibling->menu_order >= $menu_order ) {
				break;
			}

			// set the menu order of the current sibling and increment the menu order
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order' => $menu_order
				),
				array( 'ID' => $sibling->ID ),
				array( '%d' ),
				array( '%d' )
			);
			$new_pos[ $sibling->ID ] = $menu_order;
			$menu_order++;

			if ( ! $nextid && $previd == $sibling->ID ) {
				$wpdb->update(
					$wpdb->posts,
					array(
						'menu_order' => $menu_order
					),
					array( 'ID' => $post->ID ),
					array( '%d' ),
					array( '%d' )
				);
				$new_pos[$post->ID] = $menu_order;
				$menu_order++;
			}

		}

		do_action( 'woocommerce_after_product_ordering' );

		wp_send_json( $new_pos );
	}

	/**
	 * Handle a refund via the edit order screen
	 */
	public static function refund_line_items() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );

		$order_id               = absint( $_POST['order_id'] );
		$refund_amount          = wc_format_decimal( sanitize_text_field( $_POST['refund_amount'] ) );
		$refund_reason          = sanitize_text_field( $_POST['refund_reason'] );
		$line_item_qtys         = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_qtys'] ) ), true );
		$line_item_totals       = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_totals'] ) ), true );
		$line_item_tax_totals   = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_tax_totals'] ) ), true );
		$api_refund             = $_POST['api_refund'] === 'true' ? true : false;
		$restock_refunded_items = $_POST['restock_refunded_items'] === 'true' ? true : false;
		$refund                 = false;
		$response_data          = array();

		try {
			// Validate that the refund can occur
			$order       = wc_get_order( $order_id );
			$order_items = $order->get_items();
			$max_refund  = $order->get_total() - $order->get_total_refunded();

			if ( ! $refund_amount || $max_refund < $refund_amount ) {
				throw new exception( __( 'Invalid refund amount', 'woocommerce' ) );
			}

			// Prepare line items which we are refunding
			$line_items = array();
			$item_ids   = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals ) ) );

			foreach ( $item_ids as $item_id ) {
				$line_items[ $item_id ] = array( 'qty' => 0, 'refund_total' => 0, 'refund_tax' => array() );
			}
			foreach ( $line_item_qtys as $item_id => $qty ) {
				$line_items[ $item_id ]['qty'] = max( $qty, 0 );

				if ( $restock_refunded_items && $qty && isset( $order_items[ $item_id ] ) ) {
					$order_item = $order_items[ $item_id ];
					$_product   = $order->get_product_from_item( $order_item );

					if ( $_product && $_product->exists() && $_product->managing_stock() ) {
						$old_stock    = $_product->stock;
						$new_quantity = $_product->increase_stock( $qty );

						$order->add_order_note( sprintf( __( 'Item #%s stock increased from %s to %s.', 'woocommerce' ), $order_item['product_id'], $old_stock, $new_quantity ) );

						do_action( 'woocommerce_restock_refunded_item', $_product->id, $old_stock, $new_quantity, $order );
					}
				}
			}
			foreach ( $line_item_totals as $item_id => $total ) {
				$line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
			}
			foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
				$line_items[ $item_id ]['refund_tax'] = array_map( 'wc_format_decimal', $tax_totals );
			}

			// Create the refund object
			$refund = wc_create_refund( array(
				'amount'     => $refund_amount,
				'reason'     => $refund_reason,
				'order_id'   => $order_id,
				'line_items' => $line_items
			) );

			if ( is_wp_error( $refund ) ) {
				throw new Exception( $refund->get_error_message() );
			}

			// Refund via API
			if ( $api_refund ) {
				if ( WC()->payment_gateways() ) {
					$payment_gateways = WC()->payment_gateways->payment_gateways();
				}
				if ( isset( $payment_gateways[ $order->payment_method ] ) && $payment_gateways[ $order->payment_method ]->supports( 'refunds' ) ) {
					$result = $payment_gateways[ $order->payment_method ]->process_refund( $order_id, $refund_amount, $refund_reason );

					do_action( 'woocommerce_refund_processed', $refund, $result );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					} elseif ( ! $result ) {
						throw new Exception( __( 'Refund failed', 'woocommerce' ) );
					}
				}
			}

			if ( $refund_amount == $max_refund ) {
				$order->update_status( 'refunded' );
				$response_data['status'] = 'fully_refunded';
			}

			do_action( 'woocommerce_order_refunded', $order_id, $refund->id );

			// Clear transients
			wc_delete_shop_order_transients( $order_id );

			wp_send_json_success( $response_data );

		} catch ( Exception $e ) {
			if ( $refund && is_a( $refund, 'WC_Order_Refund' ) ) {
				wp_delete_post( $refund->id, true );
			}

			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Delete a refund
	 */
	public static function delete_refund() {
		check_ajax_referer( 'order-item', 'security' );

		$refund_id = absint( $_POST['refund_id'] );

		if ( $refund_id && 'shop_order_refund' === get_post_type( $refund_id ) ) {
			wc_delete_shop_order_transients( wp_get_post_parent_id( $refund_id ) );
			wp_delete_post( $refund_id );
		}

		die();
	}
}

WC_AJAX::init();
