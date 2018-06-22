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
		'product_page_product_exporter',
		'product_page_product_importer',
		'edit-product',
		'product',
		'edit-shop_coupon',
		'shop_coupon',
		'edit-product_cat',
		'edit-product_tag',
		'profile',
		'user-edit',
	);

	foreach ( wc_get_order_types() as $type ) {
		$screen_ids[] = $type;
		$screen_ids[] = 'edit-' . $type;
	}

	if ( $attributes = wc_get_attribute_taxonomies() ) {
		foreach ( $attributes as $attribute ) {
			$screen_ids[] = 'edit-' . wc_attribute_taxonomy_name( $attribute->attribute_name );
		}
	}

	return apply_filters( 'woocommerce_screen_ids', $screen_ids );
}

/**
 * Create a page and store the ID in an option.
 *
 * @param mixed  $slug Slug for the new page
 * @param string $option Option name to store the page's ID
 * @param string $page_title (default: '') Title for the new page
 * @param string $page_content (default: '') Content for the new page
 * @param int    $post_parent (default: 0) Parent for the new page
 * @return int page ID
 */
function wc_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$option_value = get_option( $option );

	if ( $option_value > 0 && ( $page_object = get_post( $option_value ) ) ) {
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
			'ID'          => $page_id,
			'post_status' => 'publish',
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
			'comment_status' => 'closed',
		);
		$page_id   = wp_insert_post( $page_data );
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

	if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-wc-admin-settings.php';
	}

	WC_Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options
 * @param array $data
 */
function woocommerce_update_options( $options, $data = null ) {

	if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-wc-admin-settings.php';
	}

	WC_Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name
 * @param mixed $default
 * @return string
 */
function woocommerce_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-wc-admin-settings.php';
	}

	return WC_Admin_Settings::get_option( $option_name, $default );
}

/**
 * Save order items. Uses the CRUD.
 *
 * @since 2.2
 * @param int   $order_id Order ID
 * @param array $items Order items to save
 */
function wc_save_order_items( $order_id, $items ) {
	// Allow other plugins to check change in order items before they are saved.
	do_action( 'woocommerce_before_save_order_items', $order_id, $items );

	// Line items and fees.
	if ( isset( $items['order_item_id'] ) ) {
		$data_keys = array(
			'line_tax'             => array(),
			'line_subtotal_tax'    => array(),
			'order_item_name'      => null,
			'order_item_qty'       => null,
			'order_item_tax_class' => null,
			'line_total'           => null,
			'line_subtotal'        => null,
		);
		foreach ( $items['order_item_id'] as $item_id ) {
			if ( ! $item = WC_Order_Factory::get_order_item( absint( $item_id ) ) ) {
				continue;
			}

			$item_data = array();

			foreach ( $data_keys as $key => $default ) {
				$item_data[ $key ] = isset( $items[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
			}

			if ( '0' === $item_data['order_item_qty'] ) {
				$item->delete();
				continue;
			}

			$item->set_props(
				array(
					'name'      => $item_data['order_item_name'],
					'quantity'  => $item_data['order_item_qty'],
					'tax_class' => $item_data['order_item_tax_class'],
					'total'     => $item_data['line_total'],
					'subtotal'  => $item_data['line_subtotal'],
					'taxes'     => array(
						'total'    => $item_data['line_tax'],
						'subtotal' => $item_data['line_subtotal_tax'],
					),
				)
			);

			if ( 'fee' === $item->get_type() ) {
				$item->set_amount( $item_data['line_total'] );
			}

			if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {
				foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
					$meta_key   = substr( wp_unslash( $meta_key ), 0, 255 );
					$meta_value = isset( $items['meta_value'][ $item_id ][ $meta_id ] ) ? wp_unslash( $items['meta_value'][ $item_id ][ $meta_id ] ) : '';

					if ( '' === $meta_key && '' === $meta_value ) {
						if ( ! strstr( $meta_id, 'new-' ) ) {
							$item->delete_meta_data_by_mid( $meta_id );
						}
					} elseif ( strstr( $meta_id, 'new-' ) ) {
						$item->add_meta_data( $meta_key, $meta_value, false );
					} else {
						$item->update_meta_data( $meta_key, $meta_value, $meta_id );
					}
				}
			}

			$item->save();
		}
	}

	// Shipping Rows
	if ( isset( $items['shipping_method_id'] ) ) {
		$data_keys = array(
			'shipping_method'       => null,
			'shipping_method_title' => null,
			'shipping_cost'         => 0,
			'shipping_taxes'        => array(),
		);

		foreach ( $items['shipping_method_id'] as $item_id ) {
			if ( ! $item = WC_Order_Factory::get_order_item( absint( $item_id ) ) ) {
				continue;
			}

			$item_data = array();

			foreach ( $data_keys as $key => $default ) {
				$item_data[ $key ] = isset( $items[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items[ $key ][ $item_id ] ) ) : $default;
			}

			$item->set_props(
				array(
					'method_id'    => $item_data['shipping_method'],
					'method_title' => $item_data['shipping_method_title'],
					'total'        => $item_data['shipping_cost'],
					'taxes'        => array(
						'total' => $item_data['shipping_taxes'],
					),
				)
			);

			if ( isset( $items['meta_key'][ $item_id ], $items['meta_value'][ $item_id ] ) ) {
				foreach ( $items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
					$meta_value = isset( $items['meta_value'][ $item_id ][ $meta_id ] ) ? wp_unslash( $items['meta_value'][ $item_id ][ $meta_id ] ) : '';

					if ( '' === $meta_key && '' === $meta_value ) {
						if ( ! strstr( $meta_id, 'new-' ) ) {
							$item->delete_meta_data_by_mid( $meta_id );
						}
					} elseif ( strstr( $meta_id, 'new-' ) ) {
						$item->add_meta_data( $meta_key, $meta_value, false );
					} else {
						$item->update_meta_data( $meta_key, $meta_value, $meta_id );
					}
				}
			}

			$item->save();
		}
	}

	$order = wc_get_order( $order_id );
	$order->update_taxes();
	$order->calculate_totals( false );

	// Inform other plugins that the items have been saved
	do_action( 'woocommerce_saved_order_items', $order_id, $items );
}

/**
 * Get HTML for some action buttons. Used in list tables.
 *
 * @since 3.3.0
 * @param array $actions Actions to output.
 * @return string
 */
function wc_render_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="wc-action-button-group"><label>' . $action['group'] . '</label> <span class="wc-action-button-group__items">' . wc_render_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$actions_html .= sprintf( '<a class="button wc-action-button wc-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}
