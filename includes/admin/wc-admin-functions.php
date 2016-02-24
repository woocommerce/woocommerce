<?php
/**
 * WooCommerce Admin Functions
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Admin/Functions
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get all WooCommerce screen ids.
 *
 * @return array
 */
function wc_get_screen_ids() {

	$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );
	$screen_ids   = array(
		'toplevel_page_' . $wc_screen_id,
		$wc_screen_id . '_page_wc-reports',
		$wc_screen_id . '_page_wc-shipping',
		$wc_screen_id . '_page_wc-settings',
		$wc_screen_id . '_page_wc-status',
		$wc_screen_id . '_page_wc-addons',
		'toplevel_page_wc-reports',
		'product_page_product_attributes',
		'edit-product',
		'product',
		'edit-shop_coupon',
		'shop_coupon',
		'edit-product_cat',
		'edit-product_tag',
		'profile',
		'user-edit'
	);

	foreach ( wc_get_order_types() as $type ) {
		$screen_ids[] = $type;
		$screen_ids[] = 'edit-' . $type;
	}

	return apply_filters( 'woocommerce_screen_ids', $screen_ids );
}

/**
 * Create a page and store the ID in an option.
 *
 * @param mixed $slug Slug for the new page
 * @param string $option Option name to store the page's ID
 * @param string $page_title (default: '') Title for the new page
 * @param string $page_content (default: '') Content for the new page
 * @param int $post_parent (default: 0) Parent for the new page
 * @return int page ID
 */
function wc_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$option_value     = get_option( $option );

	if ( $option_value > 0 ) {
		$page_object = get_post( $option_value );

		if ( 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ) ) ) {
			// Valid page is already in place
			return $page_object->ID;
		}
	}

	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode)
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		// Search for an existing page with the specified page slug
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
	}

	$valid_page_found = apply_filters( 'woocommerce_create_page_id', $valid_page_found, $slug, $page_content );

	if ( $valid_page_found ) {
		if ( $option ) {
			update_option( $option, $valid_page_found );
		}
		return $valid_page_found;
	}

	// Search for a matching valid trashed page
	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode)
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		// Search for an existing page with the specified page slug
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
	}

	if ( $trashed_page_found ) {
		$page_id   = $trashed_page_found;
		$page_data = array(
			'ID'             => $page_id,
			'post_status'    => 'publish',
		);
	 	wp_update_post( $page_data );
	} else {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed'
		);
		$page_id = wp_insert_post( $page_data );
	}

	if ( $option ) {
		update_option( $option, $page_id );
	}

	return $page_id;
}

/**
 * Output admin fields.
 *
 * Loops though the woocommerce options array and outputs each field.
 *
 * @param array $options Opens array to output
 */
function woocommerce_admin_fields( $options ) {

	if ( ! class_exists( 'WC_Admin_Settings' ) ) {
		include 'class-wc-admin-settings.php';
	}

	WC_Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options
 */
function woocommerce_update_options( $options ) {

	if ( ! class_exists( 'WC_Admin_Settings' ) ) {
		include 'class-wc-admin-settings.php';
	}

	WC_Admin_Settings::save_fields( $options );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name
 * @param mixed $default
 * @return string
 */
function woocommerce_settings_get_option( $option_name, $default = '' ) {
	if ( ! class_exists( 'WC_Admin_Settings' ) ) {
		include 'class-wc-admin-settings.php';
	}

	return WC_Admin_Settings::get_option( $option_name, $default );
}

/**
 * Save order items.
 *
 * @since 2.2
 * @param int $order_id Order ID
 * @param array $items Order items to save
 */
function wc_save_order_items( $order_id, $items ) {
	global $wpdb;

	$items = wc_clean( wp_unslash( $items ) );

	if ( isset( $items['order_item_id'] ) ) {
		$line_total = $line_subtotal = $line_tax = $line_subtotal_tax = array();

		foreach ( $items['order_item_id'] as $item_id ) {
			$item_id = absint( $item_id );

			if ( ! $item_id ) {
				continue;
			}

			$item    = WC_Order_Factory::get_order_item( $item_id );

			if ( isset( $items['order_item_name'][ $item_id ] ) ) {
				$item->set_name( $items['order_item_name'][ $item_id ] );
			}

			if ( isset( $items['order_item_qty'][ $item_id ] ) ) {
				$item->set_qty( $items['order_item_qty'][ $item_id ] );
			}

			if ( isset( $items['order_item_tax_class'][ $item_id ] ) ) {
				$item->set_tax_class( $items['order_item_tax_class'][ $item_id ] );
			}

			// Get values. Subtotals might not exist, in which case copy value from total field
			$line_total[ $item_id ]        = isset( $items['line_total'][ $item_id ] ) ? $items['line_total'][ $item_id ] : 0;
			$line_subtotal[ $item_id ]     = isset( $items['line_subtotal'][ $item_id ] ) ? $items['line_subtotal'][ $item_id ] : $line_total[ $item_id ];
			$line_tax[ $item_id ]          = isset( $items['line_tax'][ $item_id ] ) ? $items['line_tax'][ $item_id ] : array();
			$line_subtotal_tax[ $item_id ] = isset( $items['line_subtotal_tax'][ $item_id ] ) ? $items['line_subtotal_tax'][ $item_id ] : $line_tax[ $item_id ];

			// Format taxes
			$line_taxes          = array_map( 'wc_format_decimal', $line_tax[ $item_id ] );
			$line_subtotal_taxes = array_map( 'wc_format_decimal', $line_subtotal_tax[ $item_id ] );

			// Format meta data
			$meta_data = array();
			if ( isset( $items['meta_key'][ $item_id ] ) ) {
				foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
					$meta_data[ $meta_id ] = array(
						'key'   => $meta_key,
						'value' => $items['meta_value'][ $item_id ][ $meta_id ]
					);
				}
			}

			$item->set_meta_data( $meta_data );
			$item->set_subtotal( $line_subtotal[ $item_id ] );
			$item->set_subtotal_tax( array_sum( $line_subtotal_taxes ) );
			$item->set_total( $line_total[ $item_id ] );
			$item->set_total_tax( array_sum( $line_taxes ) );
			$item->set_taxes( array( 'total' => $line_taxes, 'subtotal' => $line_subtotal_taxes ) );
			$item->save();
		}
	}

	// Shipping Rows
	if ( isset( $items['shipping_method_id'] ) ) {
		foreach ( $items['shipping_method_id'] as $item_id ) {
			$item_id = absint( $item_id );
			$item    = WC_Order_Factory::get_order_item( $item_id );

			if ( isset( $items['shipping_method'][ $item_id ] ) ) {
				$item->set_method_id( $items['shipping_method'][ $item_id ] );
			}

			if ( isset( $items['shipping_method_title'][ $item_id ] ) ) {
				$item->set_method_title( $items['shipping_method_title'][ $item_id ] );
			}

			if ( isset( $items['shipping_cost'][ $item_id ] ) ) {
				$item->set_total( $items['shipping_cost'][ $item_id ] );
			}

			if ( isset( $items['shipping_taxes'][ $item_id ] ) ) {
				$item->set_taxes( array( 'taxes' => $items['shipping_taxes'][ $item_id ] ) );
			}

			// Format meta data
			$meta_data = array();
			if ( isset( $items['meta_key'][ $item_id ] ) ) {
				foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
					$meta_data[ $meta_id ] = array(
						'key'   => $meta_key,
						'value' => $items['meta_value'][ $item_id ][ $meta_id ]
					);
				}
			}

			$item->set_meta_data( $meta_data );
			$item->save();
		}
	}

	// Update order totals
	$order = wc_get_order( $order_id );
	$order->calculate_totals();
	$order->save();

	// inform other plugins that the items have been saved
	do_action( 'woocommerce_saved_order_items', $order_id, $items );
}

/**
 * Display a WooCommerce help tip.
 *
 * @since  2.5.0
 *
 * @param  string $tip        Help tip text
 * @param  bool   $allow_html Allow sanitized HTML if true or escape
 * @return string
 */
function wc_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = wc_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
}
